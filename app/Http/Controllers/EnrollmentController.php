<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Services\BackgroundCheck\InvestigationRunner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EnrollmentController extends Controller
{
    /**
     * Muestra la página de términos y condiciones al investigado.
     * Ruta pública: GET /enroll/{token}
     */
    public function show(string $token)
    {
        $subject = Subject::where('enrollment_token', $token)->first();

        if (!$subject) {
            return view('enrollment.error', [
                'titulo'  => 'Enlace no válido',
                'mensaje' => 'Este enlace de verificación no existe o ya no es válido. Por favor contacta a quien te lo compartió.',
            ]);
        }

        if ($subject->enrollment_completed_at) {
            return view('enrollment.error', [
                'titulo'  => 'Proceso completado',
                'mensaje' => 'Tu información ya fue enviada correctamente. No es necesario hacer nada más.',
                'icono'   => 'check',
            ]);
        }

        if ($subject->enrollment_expires_at && $subject->enrollment_expires_at->isPast()) {
            return view('enrollment.error', [
                'titulo'  => 'Enlace expirado',
                'mensaje' => 'Este enlace venció. Por favor solicita uno nuevo a quien te lo compartió.',
                'icono'   => 'clock',
            ]);
        }

        $tenant = $subject->project->tenant;
        $termsText = $tenant->getEnrollmentTermsText();

        return view('enrollment.show', compact('subject', 'termsText', 'token'));
    }

    /**
     * Registra la aceptación de los T&C del investigado.
     * Ruta pública: POST /enroll/{token}/accept-tc
     */
    public function acceptTerms(string $token, Request $request)
    {
        $subject = $this->findActiveSubject($token);
        if (!$subject) {
            abort(403, 'Enlace no válido o expirado.');
        }

        $subject->update([
            'enrollment_tc_accepted_at' => now(),
            'enrollment_ip'             => $request->ip(),
        ]);

        return response()->json(['status' => 'ok']);
    }

    /**
     * Recibe y guarda las imágenes del investigado (INE frente, reverso, selfie).
     * Ruta pública: POST /enroll/{token}/upload
     */
    public function upload(string $token, Request $request)
    {
        $subject = $this->findActiveSubject($token);

        if (!$subject) {
            return response()->json([
                'error' => 'Enlace no válido, expirado o proceso ya completado.'
            ], 403);
        }

        // Verificar que el investigado aceptó los T&C antes de subir
        if (!$subject->enrollment_tc_accepted_at) {
            return response()->json([
                'error' => 'Debes aceptar los Términos y Condiciones antes de continuar.'
            ], 422);
        }

        $request->validate([
            'ine_front' => 'required|image|mimes:jpg,jpeg,png,webp|max:8192',
            'ine_back'  => 'required|image|mimes:jpg,jpeg,png,webp|max:8192',
            'selfie'    => 'required|image|mimes:jpg,jpeg,png,webp|max:8192',
        ], [
            'ine_front.required' => 'La foto del frente del INE es obligatoria.',
            'ine_back.required'  => 'La foto del reverso del INE es obligatoria.',
            'selfie.required'    => 'La selfie es obligatoria para verificar tu identidad.',
        ]);

        // Guardar INE Frente (reemplazar si ya existía)
        if ($subject->ine_front_path) {
            Storage::delete($subject->ine_front_path);
        }
        $ineFrontPath = $request->file('ine_front')->store('ine_documents');

        // Guardar INE Reverso
        if ($subject->ine_back_path) {
            Storage::delete($subject->ine_back_path);
        }
        $ineBackPath = $request->file('ine_back')->store('ine_documents');

        // Guardar Selfie
        if ($subject->selfie_path) {
            Storage::delete($subject->selfie_path);
        }
        $selfiePath = $request->file('selfie')->store('enrollment_selfies');

        // Actualizar sujeto con las rutas y marcar como completado
        $subject->update([
            'ine_front_path'          => $ineFrontPath,
            'ine_back_path'           => $ineBackPath,
            'selfie_path'             => $selfiePath,
            'enrollment_completed_at' => now(),
            'enrollment_ip'           => $request->ip(),
        ]);

        // Disparar jobs OCR de forma asíncrona
        try {
            $runner = new InvestigationRunner();
            $runner->runSingle($subject, 'ine_frente');
            $runner->runSingle($subject, 'ine_reverso');
            $runner->runSingle($subject, 'selfie');
        } catch (\Throwable $e) {
            Log::warning("Enrollment: No se pudieron disparar los jobs OCR para sujeto {$subject->id}: " . $e->getMessage());
        }

        // Registrar en bitácora de actividad
        activity()
            ->performedOn($subject)
            ->withProperties(['ip' => $request->ip()])
            ->log("Enrolamiento completado por el investigado. Se subieron INE Frente, INE Reverso y Selfie.");

        return response()->json(['status' => 'ok', 'redirect' => route('enroll.done', $token)]);
    }

    /**
     * Pantalla de confirmación de enrolamiento completado.
     * Ruta pública: GET /enroll/{token}/done
     */
    public function done(string $token)
    {
        // Mostramos la pantalla de éxito sin exponer datos del sujeto
        return view('enrollment.done');
    }

    /**
     * Busca y valida que el token pertenezca a un sujeto con enrolamiento activo.
     */
    private function findActiveSubject(string $token): ?Subject
    {
        $subject = Subject::where('enrollment_token', $token)->first();

        if (!$subject) return null;
        if ($subject->enrollment_completed_at) return null;
        if ($subject->enrollment_expires_at && $subject->enrollment_expires_at->isPast()) return null;

        return $subject;
    }
}
