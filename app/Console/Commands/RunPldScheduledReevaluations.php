<?php

namespace App\Console\Commands;

use App\Models\ExpedientePldSujeto;
use App\Models\PldEvento;
use App\Models\PldSuscripcion;
use App\Services\Pld\PLDVersioningService;
use App\Services\Pld\PLDWebhookDispatcherService;
use Illuminate\Console\Command;

class RunPldScheduledReevaluations extends Command
{
    protected $signature = 'pld:run-scheduled-reevaluations {--notify-overdue-only : Solo emitir REEVALUACION_VENCIDA sin ejecutar conectores}';

    protected $description = 'Ejecuta las reevaluaciones periódicas PLD/EBR vencidas (3/6 meses o anual de identidad) y reintenta webhooks pendientes.';

    public function handle(
        PLDVersioningService $versioning,
        PLDWebhookDispatcherService $dispatcher
    ): int {
        $dueSubscriptions = PldSuscripcion::with('subject')
            ->where('estado', 'ACTIVA')
            ->where('consentimiento_vigente', true)
            ->whereNotNull('proxima_reevaluacion')
            ->where('proxima_reevaluacion', '<=', now())
            ->get();

        $processed = 0;

        foreach ($dueSubscriptions as $sub) {
            $subject = $sub->subject;
            if (!$subject || !$subject->consent_granted) {
                continue;
            }

            $expCabecera = ExpedientePldSujeto::where('tenant_id', $sub->tenant_id)
                ->where('subject_id', $subject->id)
                ->first();

            if ($this->option('notify-overdue-only')) {
                if ($expCabecera) {
                    $dispatcher->emitOverdueReevaluationEvent(
                        $subject,
                        $expCabecera->folio_vigente ?? PLDVersioningService::formatFolio($subject->id, 1),
                        $expCabecera->nivel_riesgo_persona ?? 'MEDIO',
                        $sub->suscripcion_id
                    );
                    $processed++;
                }
                continue;
            }

            // Determinar si corresponde REEVALUACION_ANUAL_IDENTIDAD (RCG Art. 21) o REEVALUACION_PERIODICA
            $tipo = 'REEVALUACION_PERIODICA';
            if ($sub->ultima_reevaluacion_identidad && $sub->ultima_reevaluacion_identidad->diffInMonths(now()) >= 12) {
                $tipo = 'REEVALUACION_ANUAL_IDENTIDAD';
            }

            try {
                $versioning->generateVersion($subject, $tipo);
                $processed++;
            } catch (\Throwable $e) {
                $this->error("Error reevaluando sujeto #{$subject->id}: " . $e->getMessage());
                if ($expCabecera) {
                    $dispatcher->emitOverdueReevaluationEvent(
                        $subject,
                        $expCabecera->folio_vigente ?? PLDVersioningService::formatFolio($subject->id, 1),
                        $expCabecera->nivel_riesgo_persona ?? 'MEDIO',
                        $sub->suscripcion_id
                    );
                }
            }
        }

        // Reintentar eventos de webhook pendientes con menos de 5 intentos
        $pendingEvents = PldEvento::whereNull('entregado_en')
            ->where('intentos', '<', 5)
            ->get();

        foreach ($pendingEvents as $evt) {
            $dispatcher->deliverEvent($evt);
        }

        $this->info("Suscripciones PLD procesadas: {$processed}. Webhooks pendientes reintentados: {$pendingEvents->count()}.");
        return self::SUCCESS;
    }
}
