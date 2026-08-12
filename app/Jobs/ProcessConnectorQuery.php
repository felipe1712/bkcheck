<?php

namespace App\Jobs;

use App\Models\SourceQuery;
use App\Models\SourceResult;
use App\Models\AuditLog;
use App\Models\ApiUsage;
use App\Services\BackgroundCheck\Nufi\NufiRfcConnector;
use App\Services\BackgroundCheck\Nufi\NufiCsdConnector;
use App\Services\BackgroundCheck\Nufi\NufiSigerConnector;
use App\Services\BackgroundCheck\Nufi\NufiSatListasConnector;
use App\Services\BackgroundCheck\Nufi\NufiMarcasConnector;
use App\Services\BackgroundCheck\Nufi\NufiIneFrenteConnector;
use App\Services\BackgroundCheck\Nufi\NufiIneReversoConnector;
use App\Services\BackgroundCheck\Nufi\NufiListaNominalConnector;
use App\Services\BackgroundCheck\Nufi\NufiIneVsSelfieConnector;
use App\Services\BackgroundCheck\Nufi\NufiSancionesConnector;
use App\Services\BackgroundCheck\Nufi\NufiLitigiosConnector;
use App\Services\BackgroundCheck\Nufi\NufiSelfieConnector;
use App\Services\BackgroundCheck\Nufi\NufiCurpConnector;
use App\Services\BackgroundCheck\Nufi\NufiDomicilioConnector;
use App\Services\BackgroundCheck\Nufi\NufiNssConnector;
use App\Services\BackgroundCheck\Nufi\NufiFinancialConnector;
use App\Services\BackgroundCheck\Nufi\NufiIdentidadDigitalConnector;
use App\Services\BackgroundCheck\PresenciaEnLineaConnector;
use App\Services\BackgroundCheck\Inegi\DenueConnector;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessConnectorQuery implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public SourceQuery $sourceQuery;
    public int $userId;
    public ?string $ipAddress;

    public int $tries = 3;
    public int $backoff = 2;

    /**
     * Create a new job instance.
     */
    public function __construct(SourceQuery $sourceQuery, int $userId, ?string $ipAddress = null)
    {
        $this->sourceQuery = $sourceQuery;
        $this->userId = $userId;
        $this->ipAddress = $ipAddress;
    }

    /**
     * Helper to evaluate 3-state classification from payload data.
     */
    public static function evaluateQueryState(string $type, array $payload): string
    {
        switch ($type) {
            case 'sat_listas':
                if (!empty($payload['en_lista_69b'])) return 'CONFIRMADO_NEGATIVO';
                break;

            case 'sanciones':
                $hits = $payload['hits'] ?? [];
                $validHits = array_filter($hits, fn($h) => !empty($h['nombre_encontrado']) && strtoupper($h['nombre_encontrado']) !== 'N/A');
                if (!empty($payload['encontrado']) && count($validHits) > 0) return 'CONFIRMADO_NEGATIVO';
                break;

            case 'litigios':
                if (!empty($payload['tiene_juicios'])) return 'CONFIRMADO_NEGATIVO';
                break;

            case 'ine_vs_selfie':
                if (isset($payload['coincide_rostro']) && !$payload['coincide_rostro']) return 'CONFIRMADO_NEGATIVO';
                break;

            case 'lista_nominal':
                if (isset($payload['valida']) && !$payload['valida']) return 'CONFIRMADO_NEGATIVO';
                break;

            case 'csd':
                if (isset($payload['valido']) && !$payload['valido']) return 'CONFIRMADO_NEGATIVO';
                break;

            case 'rfc':
                if (isset($payload['valido']) && !$payload['valido']) return 'CONFIRMADO_NEGATIVO';
                break;
        }

        return 'CONFIRMADO_POSITIVO';
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Update query status to processing
        $this->sourceQuery->update([
            'status' => 'processing',
            'retry_count' => $this->attempts(),
        ]);

        try {
            $subject = $this->sourceQuery->subject;
            $type = $this->sourceQuery->source_type;

            // Instantiate corresponding connector
            $connector = match ($type) {
                'rfc'              => new NufiRfcConnector(),
                'csd'              => new NufiCsdConnector(),
                'siger'            => new NufiSigerConnector(),
                'sat_listas'       => new NufiSatListasConnector(),
                'marcas'           => new NufiMarcasConnector(),
                'ine_frente'       => new NufiIneFrenteConnector(),
                'ine_reverso'      => new NufiIneReversoConnector(),
                'lista_nominal'    => new NufiListaNominalConnector(),
                'ine_vs_selfie'    => new NufiIneVsSelfieConnector(),
                'sanciones'        => new NufiSancionesConnector(),
                'litigios'         => new NufiLitigiosConnector(),
                'selfie'               => new NufiSelfieConnector(),
                'curp'                 => new NufiCurpConnector(),
                'comprobante_domicilio'=> new NufiDomicilioConnector(),
                'nss_imss'             => new NufiNssConnector(),
                'score_crediticio'     => new NufiFinancialConnector(),
                'identidad_digital'    => new NufiIdentidadDigitalConnector(),
                'presencia_en_linea'   => new PresenciaEnLineaConnector(),
                'denue'                => new DenueConnector(),
                default => throw new \Exception("Conector no soportado: {$type}"),
            };

            // Execute the API query
            try {
                $payload = $connector->execute($subject);

                // Save raw API response payload along with request log
                SourceResult::create([
                    'source_query_id' => $this->sourceQuery->id,
                    'raw_payload' => $connector->getLastLog(),
                    'processed_data' => $payload,
                ]);

                // Mark query as completed successfully and evaluate 3-state
                $status = 'completed';
                if ($type === 'csd' && isset($payload['uuid'])) {
                    $status = 'processing';
                } elseif (isset($payload['status']) && in_array(strtolower($payload['status']), ['processing', 'pending'])) {
                    $status = 'processing';
                }
                
                $estadoEvaluado = self::evaluateQueryState($type, $payload);

                $this->sourceQuery->update([
                    'status' => $status,
                    'estado_evaluado' => $estadoEvaluado,
                ]);
            } catch (\Throwable $e) {
                // Save log even if it failed
                SourceResult::create([
                    'source_query_id' => $this->sourceQuery->id,
                    'raw_payload' => $connector->getLastLog(),
                    'processed_data' => [],
                ]);
                throw $e;
            }

            // 1. Log to immutable AuditLog (outside Spatie activity log)
            AuditLog::create([
                'tenant_id' => $this->sourceQuery->tenant_id,
                'user_id' => $this->userId,
                'subject_name' => $subject->name_or_company,
                'subject_rfc' => $subject->rfc,
                'fuente' => $connector->getName(),
                'ip_address' => $this->ipAddress ?? '127.0.0.1',
                'created_at' => now(),
            ]);

            // 2. Track & increment tenant API monthly usage counters
            $period = now()->format('Y-m');
            $cost = config("background_check.costs.{$type}", 0.00);
            $price = config("background_check.prices.{$type}", 0.00);

            // Bypass tenant scopes for background job execution to avoid missing Auth::user() context issues
            $usage = ApiUsage::withoutGlobalScopes()
                ->firstOrCreate([
                    'tenant_id' => $this->sourceQuery->tenant_id,
                    'user_id' => $this->userId,
                    'servicio' => $type,
                    'periodo' => $period,
                ], [
                    'conteo' => 0,
                    'costo_estimado' => 0.00,
                    'ingreso_estimado' => 0.00,
                ]);

            $usage->conteo += 1;
            $usage->costo_estimado += $cost;
            $usage->ingreso_estimado += $price;
            $usage->save();

        } catch (\Throwable $e) {
            Log::error("Error procesando query ID {$this->sourceQuery->id}: " . $e->getMessage());
            
            $this->sourceQuery->update([
                'status' => 'failed',
                'estado_evaluado' => 'NO_CONCLUYENTE',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
