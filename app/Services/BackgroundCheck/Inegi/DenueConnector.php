<?php

namespace App\Services\BackgroundCheck\Inegi;

use App\Services\BackgroundCheck\BaseSourceConnector;
use App\Models\Subject;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Conector DENUE — Directorio Estadístico Nacional de Unidades Económicas
 * (INEGI).
 *
 * Consulta el directorio oficial de empresas de México sin costo alguno.
 * Retorna datos como: razón social, actividad económica SCIAN, tamaño por
 * personal ocupado, domicilio fiscal, y estado del establecimiento.
 *
 * Requiere un token gratuito de INEGI:
 *   https://www.inegi.org.mx/app/desarrolladores/generatoken/Usuarios/token_Verify
 *
 * Con INEGI_DENUE_MOCK=true (default) usa datos simulados — no requiere token.
 *
 * Aplica a personas morales (empresas) con nombre registrado.
 * También aplica a personas físicas con actividad empresarial (RFC de persona física).
 *
 * Fuente: $0 / consulta — API pública de datos abiertos INEGI.
 */
class DenueConnector extends BaseSourceConnector
{
    protected string $baseUrl  = 'https://www.inegi.org.mx/app/api/denue/v1/consulta';
    protected string $token;
    protected bool   $isMock;
    protected array  $lastLog  = [];

    // Máximo de resultados a procesar (evita respuestas masivas)
    protected int $maxResultados = 5;

    public function __construct()
    {
        $this->token  = config('background_check.denue.token', '');
        $this->isMock = config('background_check.denue.mock', true);
    }

    public function getIdentifier(): string
    {
        return 'denue';
    }

    public function getName(): string
    {
        return 'Directorio Empresarial DENUE (INEGI)';
    }

    public function getMinTierLevel(): int
    {
        return 4;
    }

    /**
     * Aplica a todas las empresas (persona_moral).
     * También aplica a personas físicas con RFC (pueden tener actividad empresarial).
     */
    public function appliesTo(Subject $subject): bool
    {
        return !empty($subject->name_or_company);
    }

    public function getLastLog(): array
    {
        return $this->lastLog;
    }

    public function execute(Subject $subject): array
    {
        if ($this->isMock) {
            Log::info("DenueConnector: modo mock para sujeto {$subject->id}");
            $mockData = $this->mockResponse($subject);

            $this->lastLog = [
                'url'      => $this->baseUrl . '/Buscar/{nombre}/0/0/0/MOCK_TOKEN',
                'method'   => 'GET',
                'params'   => ['nombre' => $subject->name_or_company],
                'response' => ['status' => 200, 'body' => $mockData],
            ];

            return $mockData;
        }

        return $this->callDenue($subject);
    }

    // ── API Real ──────────────────────────────────────────────────────────────

    private function callDenue(Subject $subject): array
    {
        if (empty($this->token)) {
            throw new \Exception('Token INEGI DENUE no configurado. Regístralo en INEGI_DENUE_TOKEN.');
        }

        // Limpiar y codificar el nombre para la URL
        $nombre = urlencode(trim($subject->name_or_company));

        // Buscar sin filtro geográfico (lat/lng = 0, distancia = 0)
        $url = "{$this->baseUrl}/Buscar/{$nombre}/0/0/0/{$this->token}";

        $this->lastLog = [
            'url'    => str_replace($this->token, '***TOKEN***', $url),
            'method' => 'GET',
            'params' => ['nombre' => $subject->name_or_company],
        ];

        try {
            $response = Http::timeout(20)->get($url);

            $this->lastLog['response'] = [
                'status' => $response->status(),
                'body'   => $response->json() ?? $response->body(),
            ];

            if ($response->failed()) {
                throw new \Exception("DENUE API error {$response->status()}: " . $response->body());
            }

            $data = $response->json();

            // Si retorna null o array vacío
            if (empty($data)) {
                return $this->respuestaVacia($subject->name_or_company, 'No se encontraron registros en el DENUE.');
            }

            return $this->normalizar($data, $subject->name_or_company);

        } catch (\Throwable $e) {
            Log::error("DenueConnector: error — " . $e->getMessage());
            if (!isset($this->lastLog['response'])) {
                $this->lastLog['response'] = ['status' => 0, 'body' => $e->getMessage()];
            }
            throw $e;
        }
    }

    // ── Normalización ─────────────────────────────────────────────────────────

    /**
     * Convierte el array crudo de DENUE en estructura interna limpia.
     * La API retorna un array de establecimientos encontrados.
     */
    private function normalizar(array $data, string $nombreBuscado): array
    {
        $establecimientos = [];

        // La API puede retornar un array directo o dentro de una clave
        $items = isset($data[0]) ? $data : ($data['establecimientos'] ?? $data['results'] ?? $data);

        foreach (array_slice($items, 0, $this->maxResultados) as $item) {
            if (!is_array($item)) continue;

            $establecimientos[] = [
                'id_denue'         => $item['id']            ?? $item['Id']           ?? null,
                'nombre_estab'     => $item['nom_estab']     ?? $item['NomEstab']     ?? null,
                'razon_social'     => $item['raz_social']    ?? $item['RazSocial']    ?? null,
                'codigo_act'       => $item['codigo_act']    ?? $item['CodigoAct']    ?? null,
                'actividad'        => $item['nombre_act']    ?? $item['NombreAct']    ?? null,
                'personal_ocupado' => $item['per_ocu']       ?? $item['PerOcu']       ?? null,
                'tipo_unidad'      => $item['tipUniEco']     ?? $item['TipUniEco']    ?? null,
                'telefono'         => $item['telefono']      ?? $item['Telefono']     ?? null,
                'sitio_web'        => $item['www']           ?? $item['Www']          ?? null,
                'correo'           => $item['correoelec']    ?? $item['Correoelec']   ?? null,
                'calle'            => $item['calle']         ?? $item['Calle']        ?? null,
                'num_exterior'     => $item['num_ext']       ?? $item['NumExt']       ?? null,
                'colonia'          => $item['colonia']       ?? $item['Colonia']      ?? null,
                'municipio'        => $item['municipio']     ?? $item['Municipio']    ?? null,
                'entidad'          => $item['entidad']       ?? $item['Entidad']      ?? null,
                'codigo_postal'    => $item['cp']            ?? $item['Cp']           ?? null,
                'latitud'          => $item['latitud']       ?? $item['Latitud']      ?? null,
                'longitud'         => $item['longitud']      ?? $item['Longitud']     ?? null,
            ];
        }

        return [
            'nombre_buscado'    => $nombreBuscado,
            'total_encontrados' => count($items),
            'total_mostrados'   => count($establecimientos),
            'establecimientos'  => $establecimientos,
            'fuente'            => 'INEGI DENUE',
            'url_fuente'        => 'https://www.inegi.org.mx/app/mapa/denue/',
        ];
    }

    private function respuestaVacia(string $nombre, string $mensaje = ''): array
    {
        return [
            'nombre_buscado'    => $nombre,
            'total_encontrados' => 0,
            'total_mostrados'   => 0,
            'establecimientos'  => [],
            'fuente'            => 'INEGI DENUE',
            'url_fuente'        => 'https://www.inegi.org.mx/app/mapa/denue/',
            'mensaje'           => $mensaje ?: 'Sin resultados en el DENUE.',
        ];
    }

    // ── Mock ──────────────────────────────────────────────────────────────────

    private function mockResponse(Subject $subject): array
    {
        $nombre = $subject->name_or_company;
        $esMoral = $subject->tipo === 'persona_moral';

        $establecimientos = [];

        if ($esMoral) {
            // Mock más completo para persona moral
            $establecimientos = [
                [
                    'id_denue'         => '456789123',
                    'nombre_estab'     => strtoupper($nombre),
                    'razon_social'     => strtoupper($nombre) . ' S.A. DE C.V.',
                    'codigo_act'       => '461110',
                    'actividad'        => 'Comercio al por menor en tiendas de abarrotes, ultramarinos y misceláneas',
                    'personal_ocupado' => '11 a 30 personas',
                    'tipo_unidad'      => 'Establecimiento',
                    'telefono'         => '55-1234-5678',
                    'sitio_web'        => null,
                    'correo'           => null,
                    'calle'            => 'Av. Insurgentes Sur',
                    'num_exterior'     => '1602',
                    'colonia'          => 'Crédito Constructor',
                    'municipio'        => 'Benito Juárez',
                    'entidad'          => 'Ciudad de México',
                    'codigo_postal'    => '03940',
                    'latitud'          => '19.377600',
                    'longitud'         => '-99.177200',
                ],
                [
                    'id_denue'         => '456789124',
                    'nombre_estab'     => strtoupper($nombre) . ' SUCURSAL NORTE',
                    'razon_social'     => strtoupper($nombre) . ' S.A. DE C.V.',
                    'codigo_act'       => '461110',
                    'actividad'        => 'Comercio al por menor en tiendas de abarrotes, ultramarinos y misceláneas',
                    'personal_ocupado' => '6 a 10 personas',
                    'tipo_unidad'      => 'Establecimiento',
                    'telefono'         => '55-8765-4321',
                    'sitio_web'        => null,
                    'correo'           => null,
                    'calle'            => 'Blvd. Manuel Ávila Camacho',
                    'num_exterior'     => '88',
                    'colonia'          => 'Polanco',
                    'municipio'        => 'Miguel Hidalgo',
                    'entidad'          => 'Ciudad de México',
                    'codigo_postal'    => '11510',
                    'latitud'          => '19.431900',
                    'longitud'         => '-99.214400',
                ],
            ];
        } else {
            // Persona física con actividad empresarial
            $establecimientos = [
                [
                    'id_denue'         => '789123456',
                    'nombre_estab'     => strtoupper($nombre),
                    'razon_social'     => strtoupper($nombre),
                    'codigo_act'       => '812210',
                    'actividad'        => 'Servicios de consultoría en administración',
                    'personal_ocupado' => '0 a 5 personas',
                    'tipo_unidad'      => 'Establecimiento',
                    'telefono'         => null,
                    'sitio_web'        => null,
                    'correo'           => null,
                    'calle'            => 'Calle de la Reforma',
                    'num_exterior'     => '210',
                    'colonia'          => 'Centro Histórico',
                    'municipio'        => 'Cuauhtémoc',
                    'entidad'          => 'Ciudad de México',
                    'codigo_postal'    => '06010',
                    'latitud'          => '19.433200',
                    'longitud'         => '-99.135700',
                ],
            ];
        }

        return [
            'nombre_buscado'    => $nombre,
            'total_encontrados' => count($establecimientos),
            'total_mostrados'   => count($establecimientos),
            'establecimientos'  => $establecimientos,
            'fuente'            => 'INEGI DENUE',
            'url_fuente'        => 'https://www.inegi.org.mx/app/mapa/denue/',
            'mensaje'           => '[MOCK] Datos del DENUE simulados. Activar con INEGI_DENUE_MOCK=false.',
        ];
    }
}
