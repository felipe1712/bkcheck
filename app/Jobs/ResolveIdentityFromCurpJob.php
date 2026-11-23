<?php

namespace App\Jobs;

use App\Models\SourceQuery;
use App\Models\Subject;
use App\Services\BackgroundCheck\CurpToRfcService;
use App\Services\BackgroundCheck\InvestigationRunner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Fase 1 de Investigación (Salvaguarda CURP -> RFC):
 *
 * Cuando un sujeto se registra únicamente con CURP (o sin RFC/nombre completo),
 * este Job resuelve primero su identidad oficial en RENAPO, calcula su RFC con
 * homoclave, lo valida ante el SAT para evitar falsos positivos y luego despacha
 * la Fase 2 con el resto de conectores.
 */
class ResolveIdentityFromCurpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $subjectId;
    public int $userId;
    public ?string $ipAddress;

    public int $tries = 2;
    public int $backoff = 3;

    public function __construct(int $subjectId, int $userId, ?string $ipAddress = null)
    {
        $this->subjectId = $subjectId;
        $this->userId    = $userId;
        $this->ipAddress = $ipAddress;
    }

    public function handle(CurpToRfcService $curpToRfcService): void
    {
        $subject = Subject::withoutGlobalScopes()->with('project.tenant')->find($this->subjectId);
        if (!$subject || empty($subject->curp)) {
            return;
        }

        $tenant = $subject->project?->tenant;
        if (!$tenant) {
            return;
        }

        $originalName = $subject->name_or_company;

        // 1. Ejecutar consulta CURP / RENAPO
        $curpQuery = $this->createFreshQuery($tenant->id, $subject->id, 'curp');
        try {
            (new ProcessConnectorQuery($curpQuery, $this->userId, $this->ipAddress))->handle();
        } catch (\Throwable $e) {
            Log::warning("ResolveIdentityFromCurpJob: falló consulta CURP para sujeto {$subject->id}: " . $e->getMessage());
        }

        $curpQuery->refresh()->load('result');
        $curpData = $curpQuery->result?->processed_data ?? [];

        $nombre  = trim((string) ($curpData['nombre'] ?? $subject->first_name ?? ''));
        $paterno = trim((string) ($curpData['primer_apellido'] ?? $subject->father_surname ?? ''));
        $materno = trim((string) ($curpData['segundo_apellido'] ?? $subject->mother_surname ?? ''));
        $fechaNac = $curpData['fecha_nacimiento'] ?? $curpToRfcService->extractBirthDateFromCurp($subject->curp);

        $renapoFullName = trim(preg_replace('/\s+/', ' ', "{$nombre} {$paterno} {$materno}"));

        if ($nombre !== '') {
            $subject->first_name = $nombre;
        }
        if ($paterno !== '') {
            $subject->father_surname = $paterno;
        }
        if ($materno !== '') {
            $subject->mother_surname = $materno;
        }
        if (!empty($fechaNac)) {
            $subject->birth_date = $fechaNac;
        }

        if ($renapoFullName !== '' && (empty($subject->name_or_company) || str_starts_with($subject->name_or_company, 'Por resolver'))) {
            $subject->name_or_company = $renapoFullName;
        }

        // 2. Calcular RFC si no se proporcionó manualmente
        if (empty($subject->rfc) && $nombre !== '' && $paterno !== '' && !empty($fechaNac)) {
            $calculatedRfc = $curpToRfcService->calculate($nombre, $paterno, $materno, $fechaNac);
            $subject->rfc = $calculatedRfc;
            $subject->rfc_source = 'calculated_from_curp';
            Log::info("ResolveIdentityFromCurpJob: RFC calculado [{$calculatedRfc}] para sujeto {$subject->id}");
        }

        $subject->save();

        // 3. Validar el RFC (calculado o manual) ante SAT
        $satRfcValid = false;
        if (!empty($subject->rfc)) {
            $rfcQuery = $this->createFreshQuery($tenant->id, $subject->id, 'rfc');
            try {
                (new ProcessConnectorQuery($rfcQuery, $this->userId, $this->ipAddress))->handle();
                $rfcQuery->refresh()->load('result');
                $rfcData = $rfcQuery->result?->processed_data ?? [];
                $satRfcValid = (bool) ($rfcData['valido'] ?? false);
            } catch (\Throwable $e) {
                Log::warning("ResolveIdentityFromCurpJob: falló validación RFC para sujeto {$subject->id}: " . $e->getMessage());
            }
        }

        // 4. Calcular score de confianza de identidad (anti-falsos positivos)
        $subject->identity_confidence_score = $curpToRfcService->evaluateIdentityConfidence(
            $renapoFullName ?: ($subject->name_or_company ?? ''),
            $originalName,
            $satRfcValid
        );
        $subject->save();

        // 5. Despachar Fase 2 (resto de conectores excluyendo curp y rfc ya ejecutados)
        (new InvestigationRunner())->dispatchPhaseTwo($subject->fresh(), $this->userId, $this->ipAddress, ['curp', 'rfc']);
    }

    private function createFreshQuery(int $tenantId, int $subjectId, string $sourceType): SourceQuery
    {
        SourceQuery::withoutGlobalScopes()
            ->where('subject_id', $subjectId)
            ->where('source_type', $sourceType)
            ->delete();

        return SourceQuery::create([
            'tenant_id'   => $tenantId,
            'subject_id'  => $subjectId,
            'source_type' => $sourceType,
            'status'      => 'pending',
        ]);
    }
}
