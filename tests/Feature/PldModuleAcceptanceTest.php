<?php

namespace Tests\Feature;

use App\Models\ExpedientePldSujeto;
use App\Models\PldEvento;
use App\Models\PldFuenteCatalogo;
use App\Models\PldScoringPolicy;
use App\Models\Project;
use App\Models\Subject;
use App\Models\Tenant;
use App\Services\Pld\PldCryptoService;
use App\Services\Pld\PLDExpedienteFormatterService;
use App\Services\Pld\PLDScoringService;
use App\Services\Pld\PLDVersioningService;
use App\Services\Pld\PLDWebhookDispatcherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PldModuleAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Project $project;
    protected Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name'                     => 'Sujeto Obligado Alfa S.A. de C.V.',
            'limite_consultas_mensual' => 500,
            'activo'                   => true,
        ]);

        $this->project = Project::withoutGlobalScopes()->create([
            'tenant_id'   => $this->tenant->id,
            'name'        => 'Onboarding PLD 2026',
            'description' => 'Evaluación EBR de clientes',
        ]);

        $this->subject = Subject::withoutGlobalScopes()->create([
            'tenant_id'           => $this->tenant->id,
            'project_id'          => $this->project->id,
            'tipo'                => 'persona_fisica',
            'name_or_company'     => 'JUAN CARLOS PEREZ LOPEZ',
            'rfc'                 => 'PELJ850315AB1',
            'curp'                => 'PELJ850315HDFRPL09',
            'address'             => 'AV REFORMA 222, CUAUHTEMOC, CDMX, CP 06600',
            'consent_granted'     => true,
            'consent_date'        => now(),
            'consent_legal_basis' => 'Consentimiento expreso alta_cliente con reconsulta periódica',
            'finalidad_clave'     => 'alta_cliente',
            'actividad_economica' => 'Servicios de consultoría',
            'nacionalidad'        => 'MEXICANA',
            'fuentes_ingreso'     => 'Honorarios profesionales',
        ]);
    }

    /**
     * Helper: Fuentes base sin coincidencias adversas (Nivel BAJO, 100%).
     */
    protected function baseCleanSources(string $fechaIso = '2026-10-28T10:00:00-06:00'): array
    {
        return [
            [
                'fuente'          => 'lista_nominal',
                'status_http'     => 200,
                'fecha_consulta'  => $fechaIso,
                'resultado_crudo' => [
                    'valida' => true,
                    'activa' => true,
                    'estado' => 'Vigente en Lista Nominal',
                ],
            ],
            [
                'fuente'          => 'ine_vs_selfie',
                'status_http'     => 200,
                'fecha_consulta'  => $fechaIso,
                'resultado_crudo' => [
                    'coincide_rostro'     => true,
                    'certeza_porcentaje'  => '98.5%',
                ],
            ],
            [
                'fuente'          => 'curp',
                'status_http'     => 200,
                'fecha_consulta'  => $fechaIso,
                'resultado_crudo' => [
                    'valida'           => true,
                    'estatus_curp'     => 'AN',
                    'curp'             => 'PELJ850315HDFRPL09',
                    'nombre_completo'  => 'JUAN CARLOS PEREZ LOPEZ',
                    'fecha_nacimiento' => '15/03/1985',
                    'nacionalidad'     => 'MEXICANA',
                ],
            ],
            [
                'fuente'          => 'rfc',
                'status_http'     => 200,
                'fecha_consulta'  => $fechaIso,
                'resultado_crudo' => [
                    'valido'       => true,
                    'situacion'    => 'ACTIVO',
                    'tipo_persona' => 'FISICA',
                ],
            ],
            [
                'fuente'          => 'sanciones',
                'status_http'     => 200,
                'fecha_consulta'  => $fechaIso,
                'resultado_crudo' => [
                    'encontrado' => false,
                    'total_hits' => 0,
                    'hits'       => [],
                ],
            ],
            [
                'fuente'          => 'sat_listas',
                'status_http'     => 200,
                'fecha_consulta'  => $fechaIso,
                'resultado_crudo' => [
                    'en_lista_69'  => false,
                    'en_lista_69b' => false,
                    'estatus'      => 'Sin registros adversos',
                ],
            ],
            [
                'fuente'          => 'litigios',
                'status_http'     => 200,
                'fecha_consulta'  => $fechaIso,
                'resultado_crudo' => [
                    'tiene_juicios' => false,
                    'total_juicios' => 0,
                    'juicios'       => [],
                ],
            ],
        ];
    }

    /**
     * Prueba de Aceptación #1 (Doc 2 §7):
     * Una reconsulta sin cambios crea versión nueva con `cambios: false` y `hash_previo` correcto.
     */
    public function test_1_reconsulta_sin_cambios_crea_version_nueva_con_cambios_false_y_hash_previo_correcto(): void
    {
        /** @var PLDVersioningService $versioning */
        $versioning = app(PLDVersioningService::class);

        $v1 = $versioning->generateVersion(
            $this->subject,
            'INICIAL',
            $this->baseCleanSources('2026-10-28T10:00:00-06:00'),
            null,
            '2026-10-28T10:00:00-06:00'
        );

        $v2 = $versioning->generateVersion(
            $this->subject,
            'REEVALUACION_PERIODICA',
            $this->baseCleanSources('2027-04-28T10:00:00-06:00'),
            null,
            '2027-04-28T10:00:00-06:00'
        );

        $this->assertSame(1, $v1->version);
        $this->assertSame(2, $v2->version);
        $this->assertNull($v1->hash_previo);
        $this->assertSame($v1->hash_version, $v2->hash_previo);

        $v2Payload = json_decode($v2->payload_canonico, true);
        $this->assertFalse($v2Payload['cambios']['cambios']);
        $this->assertEmpty($v2Payload['cambios']['items']);
        $this->assertSame($v1->folio, $v2Payload['cambios']['version_previa']);
        $this->assertSame($v2->folio, $v2Payload['cambios']['version_actual']);
    }

    /**
     * Prueba de Aceptación #2 (Doc 2 §7):
     * Alterar un campo de una versión guardada hace fallar la verificación de hash JCS (RFC 8785).
     */
    public function test_2_alterar_un_campo_de_version_guardada_hace_fallar_verificacion_de_hash(): void
    {
        $responseGen = $this->withHeaders(['X-Tenant-Api-Key' => $this->tenant->api_key])
            ->postJson("/api/personas/{$this->subject->id}/expedientes", [
                'tipo_ejecucion' => 'INICIAL',
                'fuentes'        => $this->baseCleanSources(),
            ]);

        $responseGen->assertStatus(201);
        $expedienteOriginal = $responseGen->json();

        // Verificar intacto -> debe ser válido (200)
        $resOk = $this->withHeaders(['X-Tenant-Api-Key' => $this->tenant->api_key])
            ->postJson('/api/expedientes/verificar', ['expediente' => $expedienteOriginal]);
        $resOk->assertStatus(200)->assertJson(['valido' => true]);

        // Alterar un campo del expediente (ej. cambiar score_global de 100 a 99)
        $expedienteAlterado = $expedienteOriginal;
        $expedienteAlterado['score_global'] = 99;

        $resFail = $this->withHeaders(['X-Tenant-Api-Key' => $this->tenant->api_key])
            ->postJson('/api/expedientes/verificar', ['expediente' => $expedienteAlterado]);

        $resFail->assertStatus(422)->assertJson([
            'valido'                => false,
            'coincide_con_recibido' => false,
            'coincide_con_bd'       => false,
        ]);
    }

    /**
     * Prueba de Aceptación #3 (Doc 2 §7):
     * Una fuente caída en reconsulta arrastra su resultado previo como `NO_RECONSULTADA`
     * y NO convierte un PEP previo en "sin coincidencia".
     */
    public function test_3_fuente_caida_en_reconsulta_arrastra_resultado_previo_y_preserva_pep(): void
    {
        /** @var PLDVersioningService $versioning */
        $versioning = app(PLDVersioningService::class);

        // v1: Sujeto tiene coincidencia PEP en la fuente `sanciones`
        $sourcesV1 = $this->baseCleanSources('2026-10-28T10:00:00-06:00');
        foreach ($sourcesV1 as &$s) {
            if ($s['fuente'] === 'sanciones') {
                $s['resultado_crudo'] = [
                    'encontrado' => true,
                    'total_hits' => 1,
                    'hits'       => [
                        [
                            'lista'             => 'PEP México - Función Pública',
                            'nombre_encontrado' => 'JUAN CARLOS PEREZ LOPEZ',
                            'tipo'              => 'PEP',
                            'cargo'             => 'Director General Adjunto',
                        ],
                    ],
                ];
            }
        }
        unset($s);

        $v1 = $versioning->generateVersion($this->subject, 'INICIAL', $sourcesV1, null, '2026-10-28T10:00:00-06:00');
        $expV1 = json_decode($v1->payload_canonico, true);
        $this->assertSame('REGISTRADO COMO PEP', $expV1['pep']['estatus']);
        $this->assertSame('ALTO', $expV1['nivel_riesgo_persona']['nivel']);

        // v2 (Reconsulta): La fuente `sanciones` cae con HTTP 500 y cuerpo vacío
        $sourcesV2 = $this->baseCleanSources('2027-01-28T10:00:00-06:00');
        foreach ($sourcesV2 as &$s) {
            if ($s['fuente'] === 'sanciones') {
                $s['status_http']     = 500;
                $s['resultado_crudo'] = [];
            }
        }
        unset($s);

        $v2 = $versioning->generateVersion($this->subject, 'REEVALUACION_PERIODICA', $sourcesV2, null, '2027-01-28T10:00:00-06:00');
        $expV2 = json_decode($v2->payload_canonico, true);

        // El hallazgo positivo previo de PEP DEBE permanecer activo y NO volverse "sin coincidencia"
        $this->assertSame('REGISTRADO COMO PEP', $expV2['pep']['estatus']);
        $this->assertSame('ALTO', $expV2['nivel_riesgo_persona']['nivel']);
        $this->assertTrue($expV2['edd']['recomendada']);

        // Y la fuente debe aparecer marcada como NO_RECONSULTADA con la fecha de su última consulta exitosa
        $pendienteSanciones = collect($expV2['fuentes_pendientes'])->firstWhere('fuente', 'sanciones');
        $this->assertNotNull($pendienteSanciones);
        $this->assertSame('NO_RECONSULTADA', $pendienteSanciones['estado']);
        $this->assertSame('2026-10-28T10:00:00-06:00', $pendienteSanciones['ultima_consulta_exitosa']);
    }

    /**
     * Prueba de Aceptación #4 y #5 (Doc 2 §7):
     * - Un hallazgo nuevo de PEP genera `HALLAZGO_CRITICO` firmado con HMAC y sin datos personales en el mensaje.
     * - El webhook reintentado no duplica efectos (idempotencia por `evento_id`).
     */
    public function test_4_y_5_hallazgo_pep_genera_webhook_hmac_sin_pii_e_idempotente_en_reintentos(): void
    {
        $capturedRequests = [];
        Http::fake(function ($request) use (&$capturedRequests) {
            $capturedRequests[] = $request;
            return Http::response(['received' => true], 200);
        });

        // Configurar endpoint de webhook para el tenant
        $webhookSecret = 'whsec_super_secreto_tenant_alfa_2026';
        $this->withHeaders(['X-Tenant-Api-Key' => $this->tenant->api_key])
            ->postJson('/api/webhooks', [
                'url'               => 'https://cliente-pld.example.com/webhooks/avalid',
                'secreto'           => $webhookSecret,
                'eventos_suscritos' => ['HALLAZGO_CRITICO', 'NIVEL_CAMBIO'],
            ])->assertStatus(201);

        /** @var PLDVersioningService $versioning */
        $versioning = app(PLDVersioningService::class);

        // v1: Sin coincidencias
        $versioning->generateVersion($this->subject, 'INICIAL', $this->baseCleanSources('2026-10-28T10:00:00-06:00'));
        $this->assertCount(0, $capturedRequests);

        // v2: Aparece PEP nuevo
        $sourcesPep = $this->baseCleanSources('2027-04-28T10:00:00-06:00');
        foreach ($sourcesPep as &$s) {
            if ($s['fuente'] === 'sanciones') {
                $s['resultado_crudo'] = [
                    'encontrado' => true,
                    'total_hits' => 1,
                    'hits'       => [
                        [
                            'lista'             => 'PEP Nacional',
                            'nombre_encontrado' => 'JUAN CARLOS PEREZ LOPEZ',
                            'tipo'              => 'PEP',
                        ],
                    ],
                ];
            }
        }
        unset($s);

        $v2 = $versioning->generateVersion($this->subject, 'REEVALUACION_PERIODICA', $sourcesPep);

        $this->assertGreaterThanOrEqual(1, count($capturedRequests));
        $firstReq = $capturedRequests[0];
        $rawBody = $firstReq->body();
        $jsonBody = json_decode($rawBody, true);

        // Verificar que es HALLAZGO_CRITICO con el formato exacto de Doc 2 §3.2
        $this->assertSame('HALLAZGO_CRITICO', $jsonBody['tipo']);
        $this->assertSame($v2->folio, $jsonBody['folio']);
        $this->assertSame('BAJO', $jsonBody['nivel_previo']);
        $this->assertSame('ALTO', $jsonBody['nivel_actual']);
        $this->assertStringStartsWith('evt_', $jsonBody['evento_id']);
        $this->assertStringStartsWith('ref_', $jsonBody['persona_ref']);

        // Verificar CERO datos personales y CERO detalle del hallazgo en el webhook
        $this->assertStringNotContainsString('JUAN CARLOS', $rawBody);
        $this->assertStringNotContainsString('PEREZ LOPEZ', $rawBody);
        $this->assertStringNotContainsString('PELJ850315AB1', $rawBody);
        $this->assertStringNotContainsString('PELJ850315HDFRPL09', $rawBody);
        $this->assertStringNotContainsString('REFORMA 222', $rawBody);

        // Verificar firma HMAC-SHA256 con ventana de 5 minutos
        $tsHeader  = (int) $firstReq->header('X-AvalID-Timestamp')[0];
        $sigHeader = (string) $firstReq->header('X-AvalID-Signature')[0];
        /** @var PldCryptoService $crypto */
        $crypto = app(PldCryptoService::class);
        $this->assertTrue($crypto->verifyWebhookSignature($rawBody, $webhookSecret, $tsHeader, $sigHeader));

        // Prueba #5: Reintentar la entrega del mismo PldEvento NO vuelve a disparar HTTP ni duplica efectos
        $countBeforeRetry = count($capturedRequests);
        $eventoModel = PldEvento::where('evento_id', $jsonBody['evento_id'])->firstOrFail();
        $this->assertNotNull($eventoModel->entregado_en);

        /** @var PLDWebhookDispatcherService $dispatcher */
        $dispatcher = app(PLDWebhookDispatcherService::class);
        $dispatcher->deliverEvent($eventoModel);

        $this->assertCount($countBeforeRetry, $capturedRequests);
    }

    /**
     * Prueba de Aceptación #6 (Doc 2 §7):
     * Una baja detiene la reconsulta y no se ejecuta ninguna más.
     */
    public function test_6_baja_detiene_la_reconsulta_y_bloquea_ejecuciones_posteriores(): void
    {
        // 1. Alta de monitoreo
        $this->withHeaders(['X-Tenant-Api-Key' => $this->tenant->api_key])
            ->postJson("/api/personas/{$this->subject->id}/monitoreo", [
                'finalidad'          => 'alta_cliente',
                'consentimiento_ref' => 'CONS-2026-001',
                'intervalo_meses'    => 6,
                'ejecutar_inicial'   => true,
                'fuentes'            => $this->baseCleanSources(),
            ])->assertStatus(201);

        // 2. Baja del monitoreo
        $this->withHeaders(['X-Tenant-Api-Key' => $this->tenant->api_key])
            ->deleteJson("/api/personas/{$this->subject->id}/monitoreo", [
                'motivo' => 'relacion_terminada',
            ])->assertStatus(200)
              ->assertJson(['estado' => 'BAJA', 'proxima_reevaluacion' => null]);

        // 3. Intentar ejecutar una reconsulta posterior debe ser rechazado (422) y no crear v2
        $resReconsulta = $this->withHeaders(['X-Tenant-Api-Key' => $this->tenant->api_key])
            ->postJson("/api/personas/{$this->subject->id}/expedientes", [
                'tipo_ejecucion' => 'REEVALUACION_PERIODICA',
                'fuentes'        => $this->baseCleanSources(),
            ]);

        $resReconsulta->assertStatus(422);

        $list = $this->withHeaders(['X-Tenant-Api-Key' => $this->tenant->api_key])
            ->getJson("/api/personas/{$this->subject->id}/expedientes")
            ->json();

        $this->assertSame(1, $list['total']);
    }

    /**
     * Prueba de Aceptación #7 (Doc 2 §7 + Regla Madre):
     * Ningún mensaje, PDF/texto ni JSON contiene referencias a avisos ("aviso de 24 horas", "presentar aviso", "requerimiento de la UIF")
     * ni frases prohibidas de ausencia ("sin antecedentes", "está limpio", "expediente limpio", "sin sentencia").
     */
    public function test_7_ningun_mensaje_texto_ni_json_contiene_referencias_a_avisos_ni_frases_prohibidas(): void
    {
        /** @var PLDVersioningService $versioning */
        $versioning = app(PLDVersioningService::class);

        // Caso con hallazgos graves (69-B Definitivo + PEP + Litigio Penal)
        $sourcesGrave = $this->baseCleanSources();
        foreach ($sourcesGrave as &$s) {
            if ($s['fuente'] === 'sat_listas') {
                $s['resultado_crudo'] = [
                    'en_lista_69'  => true,
                    'en_lista_69b' => true,
                    'estatus'      => 'Definitivo Art. 69-B CFF',
                    'detalles'     => [['supuesto' => 'EFOS Definitivo']],
                ];
            }
            if ($s['fuente'] === 'sanciones') {
                $s['resultado_crudo'] = [
                    'encontrado' => true,
                    'total_hits' => 1,
                    'hits'       => [
                        [
                            'lista'             => 'OFAC SDN List',
                            'nombre_encontrado' => 'JUAN CARLOS PEREZ LOPEZ',
                            'tipo'              => 'SANCION',
                        ],
                    ],
                ];
            }
        }
        unset($s);

        $version = $versioning->generateVersion($this->subject, 'INICIAL', $sourcesGrave);

        $jsonText = $version->payload_canonico;
        $formattedText = $version->expediente_formateado_texto;

        // Validar con el gate estricto que ni el JSON ni el texto formateado contienen frases prohibidas
        PLDScoringService::assertNoForbiddenPhrases($jsonText);
        PLDScoringService::assertNoForbiddenPhrases($formattedText);

        $this->assertStringNotContainsStringIgnoringCase('aviso de 24 horas', $jsonText);
        $this->assertStringNotContainsStringIgnoringCase('presentar aviso', $jsonText);
        $this->assertStringNotContainsStringIgnoringCase('sin antecedentes', $jsonText);
        $this->assertStringNotContainsStringIgnoringCase('expediente limpio', $jsonText);
        $this->assertStringNotContainsStringIgnoringCase('sin sentencia', $jsonText);

        // Y verificar que el gate bloquea activamente si alguien intenta pasar una frase prohibida
        $this->expectException(\DomainException::class);
        PLDScoringService::assertNoForbiddenPhrases('El sujeto debe presentar aviso de 24 horas ante la autoridad.');
    }

    /**
     * Prueba de Aceptación #8 (Doc 2 §7):
     * El diff entre v1 y v3 coincide con la suma acumulativa de los diffs v1→v2 y v2→v3.
     */
    public function test_8_diff_entre_v1_y_v3_coincide_con_suma_de_diffs_v1_v2_y_v2_v3(): void
    {
        /** @var PLDVersioningService $versioning */
        $versioning = app(PLDVersioningService::class);

        // v1: Base sin hallazgos
        $v1 = $versioning->generateVersion(
            $this->subject,
            'INICIAL',
            $this->baseCleanSources('2026-01-10T10:00:00-06:00'),
            null,
            '2026-01-10T10:00:00-06:00'
        );

        // v2: Cambio #1 -> cambia domicilio del sujeto y aparece litigio mercantil en `litigios`
        $this->subject->address = 'AV INSURGENTES SUR 1602, CREDITO CONSTRUCTOR, CDMX';
        $this->subject->save();

        $sourcesV2 = $this->baseCleanSources('2026-07-10T10:00:00-06:00');
        foreach ($sourcesV2 as &$s) {
            if ($s['fuente'] === 'litigios') {
                $s['resultado_crudo'] = [
                    'tiene_juicios' => true,
                    'total_juicios' => 1,
                    'juicios'       => [
                        ['materia' => 'MERCANTIL', 'expediente' => '104/2026', 'juzgado' => 'Juzgado 4o Civil'],
                    ],
                ];
            }
        }
        unset($s);

        $v2 = $versioning->generateVersion(
            $this->subject,
            'REEVALUACION_PERIODICA',
            $sourcesV2,
            null,
            '2026-07-10T10:00:00-06:00'
        );

        // v3: Cambio #2 -> conservando el estado de v2, además aparece PEP en `sanciones`
        $sourcesV3 = $sourcesV2;
        foreach ($sourcesV3 as &$s) {
            $s['fecha_consulta'] = '2027-01-10T10:00:00-06:00';
            if ($s['fuente'] === 'sanciones') {
                $s['resultado_crudo'] = [
                    'encontrado' => true,
                    'total_hits' => 1,
                    'hits'       => [
                        ['lista' => 'PEP Federal', 'nombre_encontrado' => 'JUAN CARLOS PEREZ LOPEZ', 'tipo' => 'PEP'],
                    ],
                ];
            }
        }
        unset($s);

        $v3 = $versioning->generateVersion(
            $this->subject,
            'REEVALUACION_PERIODICA',
            $sourcesV3,
            null,
            '2027-01-10T10:00:00-06:00'
        );

        $diff12 = $versioning->diffBetweenFolios($v2->folio, $v1->folio);
        $diff23 = $versioning->diffBetweenFolios($v3->folio, $v2->folio);
        $diff13 = $versioning->diffBetweenFolios($v3->folio, $v1->folio);

        // Componer(diff12, diff23) por clave `campo`: antes = primer `antes`, despues = último `despues`
        $composed = [];
        foreach ([$diff12['items'], $diff23['items']] as $itemList) {
            foreach ($itemList as $item) {
                $c = $item['campo'];
                if (!isset($composed[$c])) {
                    $composed[$c] = [
                        'campo'   => $c,
                        'antes'   => $item['antes'],
                        'despues' => $item['despues'],
                    ];
                } else {
                    $composed[$c]['despues'] = $item['despues'];
                }
            }
        }
        // Filtrar campos donde antes === despues
        $composed = array_filter($composed, fn ($x) => $x['antes'] !== $x['despues']);

        $direct13 = [];
        foreach ($diff13['items'] as $item) {
            $direct13[$item['campo']] = [
                'campo'   => $item['campo'],
                'antes'   => $item['antes'],
                'despues' => $item['despues'],
            ];
        }

        $this->assertEquals($composed, $direct13);
    }

    /**
     * Prueba M2 y M5:
     * - Cifrado AES-256-GCM en reposo + Blind Index HMAC-SHA256 (`rfc_bindex`).
     * - Prelación de Beneficiario Controlador en Persona Moral (RCG Art. 23 Quinquies).
     * - 3 fallos consecutivos de una fuente emiten `FUENTE_CAIDA_PERSISTENTE`.
     */
    public function test_9_cifrado_aes256_gcm_blind_index_y_fuente_caida_persistente_tras_3_ejecuciones(): void
    {
        /** @var PLDVersioningService $versioning */
        $versioning = app(PLDVersioningService::class);
        /** @var PldCryptoService $crypto */
        $crypto = app(PldCryptoService::class);

        $sourcesWithFallenRfc = $this->baseCleanSources();
        foreach ($sourcesWithFallenRfc as &$s) {
            if ($s['fuente'] === 'rfc') {
                $s['status_http']     = 503;
                $s['resultado_crudo'] = [];
            }
        }
        unset($s);

        // Ejecución 1, 2 y 3 con la fuente `rfc` caída (HTTP 503)
        $versioning->generateVersion($this->subject, 'INICIAL', $sourcesWithFallenRfc);
        $versioning->generateVersion($this->subject, 'REEVALUACION_PERIODICA', $sourcesWithFallenRfc);
        $versioning->generateVersion($this->subject, 'REEVALUACION_PERIODICA', $sourcesWithFallenRfc);

        // Verificar que en la 3ra caída consecutiva se emitió FUENTE_CAIDA_PERSISTENTE
        $eventoCaida = PldEvento::where('subject_id', $this->subject->id)
            ->where('tipo', 'FUENTE_CAIDA_PERSISTENTE')
            ->first();
        $this->assertNotNull($eventoCaida);

        // Verificar Cifrado AES-256-GCM y búsqueda por Blind Index HMAC-SHA256 sin descifrar la tabla
        $bindex = $crypto->blindIndex('PELJ850315AB1');
        $found = ExpedientePldSujeto::where('rfc_bindex', $bindex)->first();

        $this->assertNotNull($found);
        $this->assertNotSame('PELJ850315AB1', $found->rfc_cifrado);
        $this->assertStringNotContainsString('PELJ850315AB1', $found->rfc_cifrado);
        $this->assertSame('PELJ850315AB1', $crypto->decryptIdentifier($found->rfc_cifrado));
    }

    /**
     * Prueba M3, M4, M5:
     * - Topes duros (Biometría NO COINCIDE -> Identidad tope 0 -> Nivel ALTO).
     * - Persona Moral: Prelación obligatoria de Beneficiario Controlador (RCG Art. 23 Quinquies).
     * - Selección de Personal (RCG Art. 39 Bis 2): Corroboración sin afirmar "sin sentencia".
     */
    public function test_10_ebr_persona_moral_beneficiario_controlador_seleccion_personal_y_topes_duros(): void
    {
        /** @var PLDVersioningService $versioning */
        $versioning = app(PLDVersioningService::class);

        // 1. Persona Moral con accionista >= 25% en SIGER (Prelación Fracción I)
        $pm = Subject::withoutGlobalScopes()->create([
            'tenant_id'       => $this->tenant->id,
            'project_id'      => $this->project->id,
            'tipo'            => 'persona_moral',
            'name_or_company' => 'COMERCIALIZADORA DEL SURESTE SA DE CV',
            'rfc'             => 'CSU190520XY9',
            'consent_granted' => true,
            'consent_date'    => now(),
            'finalidad_clave' => 'alta_cliente',
        ]);

        $pmSources = [
            [
                'fuente'          => 'rfc',
                'status_http'     => 200,
                'fecha_consulta'  => '2026-10-28T10:00:00-06:00',
                'resultado_crudo' => [
                    'valido'       => true,
                    'situacion'    => 'ACTIVO',
                    'tipo_persona' => 'MORAL',
                ],
            ],
            [
                'fuente'          => 'siger',
                'status_http'     => 200,
                'fecha_consulta'  => '2026-10-28T10:00:00-06:00',
                'resultado_crudo' => [
                    'resultados' => [
                        [
                            'denominacion'       => 'COMERCIALIZADORA DEL SURESTE SA DE CV',
                            'fecha_constitucion' => '20/05/2019',
                            'objeto_social'      => 'Comercio al por mayor',
                            'socios'             => [
                                ['nombre' => 'ROBERTO GOMEZ DIAZ', 'participacion' => '60%'],
                                ['nombre' => 'ANA LOPEZ PEREZ', 'participacion' => '20%'],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'fuente'          => 'sanciones',
                'status_http'     => 200,
                'fecha_consulta'  => '2026-10-28T10:00:00-06:00',
                'resultado_crudo' => ['encontrado' => false, 'hits' => []],
            ],
            [
                'fuente'          => 'sat_listas',
                'status_http'     => 200,
                'fecha_consulta'  => '2026-10-28T10:00:00-06:00',
                'resultado_crudo' => ['en_lista_69' => false, 'en_lista_69b' => false],
            ],
        ];

        $vPm = $versioning->generateVersion($pm, 'INICIAL', $pmSources, null, '2026-10-28T10:00:00-06:00');
        $expPm = json_decode($vPm->payload_canonico, true);

        $this->assertSame('Evaluado', $expPm['beneficiario_controlador']['estado']);
        $this->assertStringContainsString('Fracción I', $expPm['beneficiario_controlador']['prelacion_aplicada']);
        $this->assertCount(1, $expPm['beneficiario_controlador']['beneficiarios']);
        $this->assertSame('ROBERTO GOMEZ DIAZ', $expPm['beneficiario_controlador']['beneficiarios'][0]['nombre']);

        // 2. Tope duro por Biometría NO COINCIDE -> Identidad 0%, Nivel ALTO, Intervalo 3 meses
        $sourcesBioFail = $this->baseCleanSources('2026-10-28T10:00:00-06:00');
        foreach ($sourcesBioFail as &$s) {
            if ($s['fuente'] === 'ine_vs_selfie') {
                $s['resultado_crudo'] = [
                    'coincide_rostro'    => false,
                    'certeza_porcentaje' => '41.2%',
                ];
            }
        }
        unset($s);

        $this->subject->finalidad_clave = 'seleccion_personal';
        $this->subject->save();

        $vBio = $versioning->generateVersion($this->subject, 'INICIAL', $sourcesBioFail, null, '2026-10-28T10:00:00-06:00');
        $expBio = json_decode($vBio->payload_canonico, true);

        $this->assertSame(0, $expBio['scores_categoria']['IDENTIDAD']['score']);
        $this->assertSame('ALTO', $expBio['nivel_riesgo_persona']['nivel']);
        $this->assertSame('REVISION_MANUAL', $expBio['recomendacion']);
        $this->assertSame(3, $expBio['intervalo_reevaluacion_meses']);
        $this->assertNotNull($expBio['corroboracion_personal']);
        PLDScoringService::assertNoForbiddenPhrases(json_encode($expBio['corroboracion_personal'], JSON_UNESCAPED_UNICODE));
    }

    /**
     * Prueba UI: Todas las pantallas web del módulo PLD para Cliente (Tenant) y SuperAdmin cargan con HTTP 200.
     */
    public function test_11_pantallas_web_tenant_y_superadmin_pld_cargan_con_200(): void
    {
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'tenant_admin', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        $tenantAdmin = \App\Models\User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'activo'    => true,
            'avatar'    => 'avatar-1.jpg',
        ]);
        $tenantAdmin->assignRole('tenant_admin');

        $superAdmin = \App\Models\User::factory()->create([
            'tenant_id' => null,
            'activo'    => true,
            'avatar'    => 'avatar-1.jpg',
        ]);
        $superAdmin->assignRole('super_admin');

        /** @var PLDVersioningService $versioning */
        $versioning = app(PLDVersioningService::class);
        $versioning->generateVersion($this->subject, 'INICIAL', $this->baseCleanSources());

        $this->actingAs($tenantAdmin)
            ->get(route('tenant.pld.expedientes.index'))
            ->assertStatus(200)
            ->assertSee($this->subject->name_or_company);

        $this->actingAs($tenantAdmin)
            ->get(route('tenant.pld.expedientes.show', $this->subject->id))
            ->assertStatus(200)
            ->assertSee('NIVEL DE RIESGO PERSONA');

        $this->actingAs($tenantAdmin)
            ->get(route('tenant.pld.policy'))
            ->assertStatus(200)
            ->assertSee('NORMA FIJA');

        $this->actingAs($superAdmin)
            ->get(route('superadmin.pld-catalogo.index'))
            ->assertStatus(200)
            ->assertSee('lista_nominal');
    }
}
