<?php

namespace App\Services\BackgroundCheck;

use App\Models\Subject;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Conector de Presencia en Línea / Huella Digital (OSINT).
 *
 * Combina Sherlock (búsqueda por username en +300 plataformas) y
 * Social Analyzer (búsqueda por nombre con correlación de perfiles)
 * mediante un microservicio Python independiente en osint-service/app.py.
 *
 * Gobernanza (DECISIONS.md §10):
 *  - Solo handles/URLs públicos — sin volcados de perfil completo.
 *  - Plataformas sensibles excluidas por el wrapper Python.
 *  - Toda ejecución queda en la bitácora de actividad.
 *  - Base legal: interés legítimo (no consentimiento del investigado).
 *  - Disclaimer obligatorio en UI y PDF.
 */
class PresenciaEnLineaConnector extends BaseSourceConnector
{
    protected array $lastLog = [];

    public function getIdentifier(): string
    {
        return 'presencia_en_linea';
    }

    public function getName(): string
    {
        return 'Presencia en Línea y OSINT';
    }

    public function getMinTierLevel(): int
    {
        return 2;
    }

    /**
     * Aplica a todos los sujetos cuando el servicio OSINT está habilitado.
     * Si OSINT_SERVICE_ENABLED=false, siempre aplica pero usa mock data.
     */
    public function appliesTo(Subject $subject): bool
    {
        // Requiere al menos nombre o username para buscar
        return !empty($subject->name_or_company);
    }

    /**
     * Retorna el log de la última petición (para auditoría en raw_payload).
     */
    public function getLastLog(): array
    {
        return $this->lastLog;
    }

    /**
     * Ejecuta la búsqueda OSINT. En mock devuelve datos simulados.
     */
    public function execute(Subject $subject): array
    {
        $isMock = !config('background_check.osint.enabled', false);

        if ($isMock) {
            Log::info("PresenciaEnLinea: modo mock para sujeto {$subject->id}");
            $mockData = $this->getMockResponse($subject);
            $this->lastLog = [
                'request'  => ['mode' => 'mock', 'subject_id' => $subject->id],
                'response' => $mockData,
            ];
            return $mockData;
        }

        return $this->callOsintService($subject);
    }

    /**
     * Llama al microservicio Python OSINT.
     */
    private function callOsintService(Subject $subject): array
    {
        $serviceUrl = config('background_check.osint.service_url', 'http://127.0.0.1:5001');
        $secret     = config('background_check.osint.secret', 'bkcheck-osint-dev-secret');

        $payload = [
            'nombre'   => $subject->name_or_company,
            'username' => $subject->username ?? '',
        ];

        $this->lastLog['request'] = [
            'url'     => $serviceUrl . '/osint/search',
            'payload' => $payload,
        ];

        try {
            // Verificar salud del servicio primero
            $health = Http::timeout(3)->get($serviceUrl . '/health');
            if (!$health->successful()) {
                throw new \RuntimeException('El servicio OSINT no está disponible.');
            }

            $response = Http::timeout(120)
                ->withHeaders(['X-OSINT-Secret' => $secret])
                ->post($serviceUrl . '/osint/search', $payload);

            $data = $response->json();
            $this->lastLog['response'] = $data;

            if (!$response->successful()) {
                throw new \RuntimeException($data['error'] ?? 'Error del servicio OSINT.');
            }

            return $this->normalizar($data);

        } catch (\Throwable $e) {
            Log::error("PresenciaEnLinea: error llamando al microservicio — " . $e->getMessage());
            $this->lastLog['error'] = $e->getMessage();

            // Degradación elegante: retorna vacío en lugar de fallar la investigación
            return $this->respuestaVacia("Servicio OSINT no disponible: " . $e->getMessage());
        }
    }

    /**
     * Normaliza la respuesta del microservicio a la estructura del expediente.
     */
    private function normalizar(array $data): array
    {
        return [
            'username_buscado'          => $data['username_buscado'] ?? null,
            'nombre_buscado'            => $data['nombre_buscado'] ?? null,
            'plataformas_encontradas'   => $data['plataformas_encontradas'] ?? [],
            'perfiles_correlacionados'  => $data['perfiles_correlacionados'] ?? [],
            'total_coincidencias'       => $data['total_coincidencias'] ?? 0,
            'nivel_exposicion'          => $data['nivel_exposicion'] ?? 'ninguno',
            'disclaimer'                => $data['disclaimer'] ?? $this->getDisclaimer(),
            'fuentes_ejecutadas'        => ['sherlock', 'social_analyzer'],
        ];
    }

    private function respuestaVacia(string $motivo = ''): array
    {
        return [
            'username_buscado'         => null,
            'nombre_buscado'           => null,
            'plataformas_encontradas'  => [],
            'perfiles_correlacionados' => [],
            'total_coincidencias'      => 0,
            'nivel_exposicion'         => 'ninguno',
            'disclaimer'               => $this->getDisclaimer(),
            'fuentes_ejecutadas'       => [],
            'error'                    => $motivo ?: null,
        ];
    }

    private function getMockResponse(Subject $subject): array
    {
        $nombre = $subject->name_or_company;
        return [
            'username_buscado'         => $subject->username ?? 'mock_user',
            'nombre_buscado'           => $nombre,
            'plataformas_encontradas'  => [
                ['plataforma' => 'Twitter/X',   'url' => 'https://twitter.com/mock_user',             'fuente' => 'sherlock',        'confianza' => 'alta'],
                ['plataforma' => 'GitHub',       'url' => 'https://github.com/mock_user',              'fuente' => 'sherlock',        'confianza' => 'alta'],
                ['plataforma' => 'Instagram',    'url' => 'https://instagram.com/mock_user',           'fuente' => 'sherlock',        'confianza' => 'alta'],
            ],
            'perfiles_correlacionados' => [
                ['plataforma' => 'LinkedIn', 'url' => 'https://linkedin.com/in/mock-user', 'fuente' => 'social_analyzer', 'confianza' => 'media', 'nombre_detectado' => $nombre],
            ],
            'total_coincidencias'      => 4,
            'nivel_exposicion'         => 'bajo',
            'disclaimer'               => $this->getDisclaimer(),
            'fuentes_ejecutadas'       => ['sherlock (mock)', 'social_analyzer (mock)'],
        ];
    }

    private function getDisclaimer(): string
    {
        return 'Esta información proviene de fuentes públicas y es de carácter indicativo. '
             . 'Requiere revisión humana antes de utilizarse en cualquier decisión. '
             . 'No se capturan ni incluyen atributos de carácter sensible.';
    }
}
