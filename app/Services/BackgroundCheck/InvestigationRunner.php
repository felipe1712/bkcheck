<?php

namespace App\Services\BackgroundCheck;

use App\Models\Subject;
use App\Models\SourceQuery;
use App\Models\ApiUsage;
use App\Jobs\ProcessConnectorQuery;
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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class InvestigationRunner
{
    /**
     * Get list of all available connectors.
     *
     * @return array<\App\Services\BackgroundCheck\Contracts\SourceConnector>
     */
    public function getConnectors(): array
    {
        return [
            new NufiCurpConnector(),
            new NufiRfcConnector(),
            new NufiCsdConnector(),
            new NufiSigerConnector(),
            new NufiSatListasConnector(),
            new NufiMarcasConnector(),
            new NufiIneFrenteConnector(),
            new NufiIneReversoConnector(),
            new NufiListaNominalConnector(),
            new NufiIneVsSelfieConnector(),
            new NufiSancionesConnector(),
            new NufiLitigiosConnector(),
            new NufiSelfieConnector(),
            new NufiDomicilioConnector(),
            new NufiNssConnector(),
            new NufiFinancialConnector(),
            new NufiIdentidadDigitalConnector(),
            new PresenciaEnLineaConnector(),
            new DenueConnector(),
        ];
    }

    /**
     * Run background check investigation for a given subject.
     *
     * @param Subject $subject
     * @throws \Exception
     */
    public function run(Subject $subject): void
    {
        $tenant = $subject->project->tenant;
        if (!$tenant) {
            throw new \Exception("El sujeto no está asociado a un Tenant válido.");
        }

        // 1. Determine which connectors apply to this subject and tier level
        $subjectTier = $subject->tier_level ?? 4;
        $applicableConnectors = [];
        foreach ($this->getConnectors() as $connector) {
            $minTier = method_exists($connector, 'getMinTierLevel') ? $connector->getMinTierLevel() : 1;
            if ($minTier <= $subjectTier && $connector->appliesTo($subject)) {
                $applicableConnectors[] = $connector;
            }
        }

        if (empty($applicableConnectors)) {
            throw new \Exception("No hay conectores aplicables para este sujeto.");
        }

        $requestedCount = count($applicableConnectors);

        // 2. Validate tenant monthly query limit/quota
        $period = now()->format('Y-m');
        $currentUsage = ApiUsage::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('periodo', $period)
            ->sum('conteo');

        $limit = $tenant->limite_consultas_mensual;

        if (($currentUsage + $requestedCount) > $limit) {
            throw new \Exception("Se ha excedido el límite mensual de consultas para su organización. Límite: {$limit}, Consumo Actual: {$currentUsage}, Requerido: {$requestedCount}.");
        }

        // Get current user and IP address details
        $userId = Auth::id() ?? $tenant->users()->first()?->id ?? 1;
        $ipAddress = request()->ip();

        // 3. Dispatch queries
        foreach ($applicableConnectors as $connector) {
            // Check if there's an existing query for this subject and type, delete it so we always display the latest execution
            SourceQuery::withoutGlobalScopes()
                ->where('subject_id', $subject->id)
                ->where('source_type', $connector->getIdentifier())
                ->delete();

            // Create new query record
            $sourceQuery = SourceQuery::create([
                'tenant_id' => $tenant->id,
                'subject_id' => $subject->id,
                'source_type' => $connector->getIdentifier(),
                'status' => 'pending',
            ]);

            // Dispatch job to the queue
            ProcessConnectorQuery::dispatch($sourceQuery, $userId, $ipAddress);
        }

        Log::info("Investigación iniciada para sujeto {$subject->id} por el usuario {$userId} con {$requestedCount} consultas despachadas.");
    }

    /**
     * Run a single background check query for a given subject.
     *
     * @param Subject $subject
     * @param string $sourceType
     * @throws \Exception
     */
    public function runSingle(Subject $subject, string $sourceType): void
    {
        $tenant = $subject->project->tenant;
        if (!$tenant) {
            throw new \Exception("El sujeto no está asociado a un Tenant válido.");
        }

        // Find the connector matching the source type
        $targetConnector = null;
        foreach ($this->getConnectors() as $connector) {
            if ($connector->getIdentifier() === $sourceType) {
                $targetConnector = $connector;
                break;
            }
        }

        if (!$targetConnector) {
            throw new \Exception("El conector especificado no existe o no está registrado.");
        }

        if (!$targetConnector->appliesTo($subject)) {
            throw new \Exception("El conector '{$sourceType}' no aplica para este tipo de sujeto.");
        }

        // 2. Validate tenant monthly query limit/quota
        $period = now()->format('Y-m');
        $currentUsage = ApiUsage::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('periodo', $period)
            ->sum('conteo');

        $limit = $tenant->limite_consultas_mensual;

        if (($currentUsage + 1) > $limit) {
            throw new \Exception("Se ha excedido el límite mensual de consultas para su organización. Límite: {$limit}, Consumo Actual: {$currentUsage}, Requerido: 1.");
        }

        // Get current user and IP address details
        $userId = Auth::id() ?? $tenant->users()->first()?->id ?? 1;
        $ipAddress = request()->ip();

        // 3. Dispatch the query
        // Delete previous query/result so we always display the latest execution
        SourceQuery::withoutGlobalScopes()
            ->where('subject_id', $subject->id)
            ->where('source_type', $targetConnector->getIdentifier())
            ->delete();

        // Create new query record
        $sourceQuery = SourceQuery::create([
            'tenant_id' => $tenant->id,
            'subject_id' => $subject->id,
            'source_type' => $targetConnector->getIdentifier(),
            'status' => 'pending',
        ]);

        // Dispatch job synchronously for immediate single-source queries
        ProcessConnectorQuery::dispatchSync($sourceQuery, $userId, $ipAddress);

        Log::info("Consulta individual '{$sourceType}' iniciada para sujeto {$subject->id} por el usuario {$userId}.");
    }
}
