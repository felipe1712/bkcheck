<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cifrado AES-256-GCM y Búsqueda Ciega HMAC-SHA256 (M2)
    |--------------------------------------------------------------------------
    | DEUDA TÉCNICA DOCUMENTADA (MVP):
    | Las llaves maestras residen en variables de entorno (.env) para el MVP.
    | Antes de escalar a alto volumen, sustituir el proveedor de llaves en
    | App\Services\Pld\PldCryptoService por AWS KMS / HashiCorp Vault sin
    | modificar los modelos ni controladores.
    */
    'encryption_key' => env('PLD_ENCRYPTION_KEY', env('APP_KEY', 'base64:AvalIDPldDefaultEncryptionKey32BytesMin!!')),
    'bindex_key'     => env('PLD_BINDEX_KEY', 'avalid-pld-blind-index-hmac-separate-key-2026'),

    /*
    |--------------------------------------------------------------------------
    | Catálogo Global por Defecto de Fuentes (M0 / M1 — super_admin)
    |--------------------------------------------------------------------------
    | Taxonomía global de plataforma: fuente_key -> categoria -> criticidad.
    | Una fuente que no exista o no tenga categoría/criticidad en este catálogo
    | NO se procesa: se marca "fuente no catalogada, requiere clasificación manual".
    */
    'catalogo_default' => [
        'lista_nominal'         => ['nombre' => 'Lista Nominal INE',                      'categoria' => 'IDENTIDAD',    'criticidad' => 'ALTA',  'activo' => true],
        'ine_vs_selfie'         => ['nombre' => 'Biometría Facial (INE vs Selfie)',       'categoria' => 'IDENTIDAD',    'criticidad' => 'ALTA',  'activo' => true],
        'selfie'                => ['nombre' => 'Prueba de Vida Biométrica (Liveness)',   'categoria' => 'IDENTIDAD',    'criticidad' => 'ALTA',  'activo' => true],
        'curp'                  => ['nombre' => 'Validación CURP / RENAPO',               'categoria' => 'IDENTIDAD',    'criticidad' => 'ALTA',  'activo' => true],
        'rfc'                   => ['nombre' => 'Validación RFC (SAT)',                   'categoria' => 'IDENTIDAD',    'criticidad' => 'ALTA',  'activo' => true],
        'ine_frente'            => ['nombre' => 'OCR Credencial INE Frente',              'categoria' => 'IDENTIDAD',    'criticidad' => 'MEDIA', 'activo' => true],
        'ine_reverso'           => ['nombre' => 'OCR Credencial INE Reverso',             'categoria' => 'IDENTIDAD',    'criticidad' => 'MEDIA', 'activo' => true],
        'comprobante_domicilio' => ['nombre' => 'OCR Comprobante de Domicilio',           'categoria' => 'IDENTIDAD',    'criticidad' => 'BAJA',  'activo' => true],
        'sanciones'             => ['nombre' => 'Listas PLD / OFAC / ONU / UE / PEPs',    'categoria' => 'PLD',          'criticidad' => 'ALTA',  'activo' => true],
        'sat_listas'            => ['nombre' => 'SAT Listas 69 y 69-B (EFOS/EDOS)',       'categoria' => 'PLD',          'criticidad' => 'ALTA',  'activo' => true],
        'siger'                 => ['nombre' => 'Registro Público de Comercio (SIGER)',   'categoria' => 'CONSISTENCIA', 'criticidad' => 'MEDIA', 'activo' => true],
        'nss_imss'              => ['nombre' => 'Historial Laboral NSS / IMSS',           'categoria' => 'CONSISTENCIA', 'criticidad' => 'BAJA',  'activo' => true],
        'denue'                 => ['nombre' => 'Directorio Empresarial DENUE (INEGI)',   'categoria' => 'CONSISTENCIA', 'criticidad' => 'BAJA',  'activo' => true],
        'litigios'              => ['nombre' => 'Antecedentes Judiciales y Litigios',     'categoria' => 'REPUTACIONAL', 'criticidad' => 'ALTA',  'activo' => true],
        'csd'                   => ['nombre' => 'Certificados CSD y e-Firma (SAT)',       'categoria' => 'REPUTACIONAL', 'criticidad' => 'MEDIA', 'activo' => true],
        'score_crediticio'      => ['nombre' => 'Buró de Crédito / Historial Crediticio', 'categoria' => 'REPUTACIONAL', 'criticidad' => 'MEDIA', 'activo' => true],
        'identidad_digital'     => ['nombre' => 'Perfil Digital y Enriquecimiento',       'categoria' => 'REPUTACIONAL', 'criticidad' => 'BAJA',  'activo' => true],
        'presencia_en_linea'    => ['nombre' => 'Presencia en Línea / Huella OSINT',      'categoria' => 'REPUTACIONAL', 'criticidad' => 'BAJA',  'activo' => true],
        'marcas'                => ['nombre' => 'Propiedad Industrial (IMPI)',            'categoria' => 'REPUTACIONAL', 'criticidad' => 'BAJA',  'activo' => true],
    ],

    /*
    |--------------------------------------------------------------------------
    | Política de Puntuación por Defecto de AvalID (Doc 3 §4 — Por Tenant)
    |--------------------------------------------------------------------------
    | Se siembra para cada Tenant. El tenant_admin puede ajustar pesos, topes e
    | intervalos de política, mientras que las reglas de norma permanecen fijas.
    */
    'policy_defaults' => [
        'IDENTIDAD' => [
            'peso_porcentual' => 40,
            'triggers_config' => [
                'lista_nominal_inactiva' => [
                    'nombre'      => 'Credencial INE No Vigente en Lista Nominal',
                    'fuente'      => 'Lista Nominal INE',
                    'label'       => 'Se activa cuando el padrón electoral del INE reporta la credencial como no vigente, inactiva o sin coincidencia de CIC/OCR.',
                    'efecto_tipo' => 'TOPE',
                    'valor'       => 60,
                    'activo'      => true,
                ],
                'biometria_menor_90' => [
                    'nombre'      => 'Similitud Biométrica Facial Inferior al 90%',
                    'fuente'      => 'Biometría Facial (INE vs Selfie)',
                    'label'       => 'Se activa cuando el rostro coincide pero el porcentaje de certeza biométrica es menor al 90%, requiriendo validación visual.',
                    'efecto_tipo' => 'TOPE',
                    'valor'       => 50,
                    'activo'      => true,
                ],
                'biometria_no_coincide' => [
                    'nombre'      => 'Biometría Facial No Coincide',
                    'fuente'      => 'Biometría Facial (INE vs Selfie)',
                    'label'       => 'Alerta crítica por no coincidencia entre la fotografía de la credencial INE y la captura facial con prueba de vida. Clasifica automáticamente en Nivel ALTO.',
                    'efecto_tipo' => 'TOPE',
                    'valor'       => 0,
                    'activo'      => true,
                ],
                'curp_invalido' => [
                    'nombre'      => 'CURP No Válida o en Estatus de Baja en RENAPO',
                    'fuente'      => 'Validación CURP / RENAPO',
                    'label'       => 'Se activa si RENAPO reporta la CURP como inexistente o con estatus de baja (defunción o baja administrativa). Clasifica en Nivel ALTO.',
                    'efecto_tipo' => 'TOPE',
                    'valor'       => 30,
                    'activo'      => true,
                ],
                'nombre_no_coincide' => [
                    'nombre'      => 'Discrepancia de Nombre en Fuentes de Identidad',
                    'fuente'      => 'Cruce INE / CURP / RFC',
                    'label'       => 'Se activa cuando el nombre normalizado del titular no coincide exactamente entre su identificación oficial, RENAPO y el SAT.',
                    'efecto_tipo' => 'TOPE',
                    'valor'       => 70,
                    'activo'      => true,
                ],
                'ocr_ilegible' => [
                    'nombre'      => 'Lectura OCR de Identificación Incompleta o Ilegible',
                    'fuente'      => 'OCR Credencial INE Frente y Reverso',
                    'label'       => 'Descuenta puntos y clasifica la captura como pendiente cuando la imagen impide extraer clave de elector, CIC u OCR.',
                    'efecto_tipo' => 'DESCUENTO',
                    'valor'       => 20,
                    'activo'      => true,
                ],
            ],
        ],
        'PLD' => [
            'peso_porcentual' => 30,
            'triggers_config' => [
                'sanciones_ofac_onu_ue_lpb' => [
                    'nombre'      => 'Coincidencia en Listas de Sanciones (OFAC, ONU, UE, LPB)',
                    'fuente'      => 'Listas Internacionales y Nacionales PLD',
                    'label'       => 'Hallazgo bloqueante en listas restrictivas internacionales o Lista de Personas Bloqueadas. Fija recomendación NO PROCEDER y Nivel ALTO.',
                    'efecto_tipo' => 'TOPE',
                    'valor'       => 0,
                    'activo'      => true,
                ],
                'registrado_pep' => [
                    'nombre'      => 'Persona Políticamente Expuesta (PEP) Confirmada',
                    'fuente'      => 'Padrón de PEPs Nacionales e Internacionales',
                    'label'       => 'Identifica cargos públicos actuales o previos. Activa obligatoriamente Debida Diligencia Reforzada (EDD), Nivel ALTO y reevaluación a 3 meses.',
                    'efecto_tipo' => 'TOPE',
                    'valor'       => 40,
                    'activo'      => true,
                ],
                'lista_69b' => [
                    'nombre'      => 'Coincidencia en Listado Art. 69-B del SAT (EFOS / EDOS)',
                    'fuente'      => 'SAT Listas Art. 69-B (Operaciones Inexistentes)',
                    'label'       => 'Contribuyente publicado como presunto o definitivo en facturación de operaciones simuladas. Fija recomendación NO PROCEDER y Nivel ALTO.',
                    'efecto_tipo' => 'TOPE',
                    'valor'       => 0,
                    'activo'      => true,
                ],
                'lista_69' => [
                    'nombre'      => 'Coincidencia en Listado Art. 69 del SAT (Incumplidos)',
                    'fuente'      => 'SAT Listas Art. 69 (Créditos Fiscales / No Localizados)',
                    'label'       => 'Se activa cuando el contribuyente registra créditos fiscales firmes, exigibles, cancelados o estatus de no localizado ante el SAT.',
                    'efecto_tipo' => 'TOPE',
                    'valor'       => 50,
                    'activo'      => true,
                ],
                'fuente_sanciones_pendiente' => [
                    'nombre'      => 'Fuente Crítica de Listas PLD o SAT Sin Respuesta',
                    'fuente'      => 'Listas PLD y SAT 69 / 69-B',
                    'label'       => 'Impide otorgar calificación de 100% cuando una fuente crítica de cumplimiento no pudo ser verificada por intermitencia o error HTTP.',
                    'efecto_tipo' => 'TOPE',
                    'valor'       => 89,
                    'activo'      => true,
                ],
            ],
        ],
        'CONSISTENCIA' => [
            'peso_porcentual' => 15,
            'triggers_config' => [
                'nombre_difiere' => [
                    'nombre'      => 'Nombre o Razón Social Difiere entre Dos o Más Fuentes',
                    'fuente'      => 'Verificación Cruzada (INE, CURP, RFC, IMSS)',
                    'label'       => 'Penaliza la categoría de Consistencia cuando existen variaciones en nombres o apellidos entre distintas bases gubernamentales.',
                    'efecto_tipo' => 'TOPE',
                    'valor'       => 50,
                    'activo'      => true,
                ],
                'fecha_nacimiento_difiere' => [
                    'nombre'      => 'Fecha de Nacimiento Discrepante entre Fuentes Oficiales',
                    'fuente'      => 'Verificación Cruzada (RENAPO vs Credencial INE)',
                    'label'       => 'Se activa cuando la fecha de nacimiento reportada por RENAPO no coincide con la extraída de la identificación oficial.',
                    'efecto_tipo' => 'TOPE',
                    'valor'       => 50,
                    'activo'      => true,
                ],
                'rfc_curp_estructura' => [
                    'nombre'      => 'Inconsistencia Estructural entre Prefijo RFC y CURP',
                    'fuente'      => 'Cruce Estructural SAT vs RENAPO',
                    'label'       => 'En personas físicas, verifica que los primeros 10 caracteres del RFC correspondan exactamente a los primeros 10 caracteres de la CURP.',
                    'efecto_tipo' => 'TOPE',
                    'valor'       => 60,
                    'activo'      => true,
                ],
            ],
        ],
        'REPUTACIONAL' => [
            'peso_porcentual' => 15,
            'triggers_config' => [
                'senales_adversas' => [
                    'nombre'      => 'Expedientes Judiciales o Señales Adversas Confirmadas',
                    'fuente'      => 'Boletín Judicial Nacional y Fuentes Públicas',
                    'label'       => 'Se activa cuando existen demandas o expedientes judiciales (mercantiles, civiles, laborales o penales) localizados a nombre del sujeto.',
                    'efecto_tipo' => 'TOPE',
                    'valor'       => 40,
                    'activo'      => true,
                ],
                'csd_revocado' => [
                    'nombre'      => 'Certificados de Sello Digital (CSD) o e.firma Revocados',
                    'fuente'      => 'Certificados CSD y e.firma (SAT)',
                    'label'       => 'Aplica una deducción directa cuando se detectan certificados fiscales revocados o cancelados en el historial del contribuyente.',
                    'efecto_tipo' => 'DESCUENTO',
                    'valor'       => 10,
                    'activo'      => true,
                ],
            ],
        ],
    ],

    'intervalos_default' => [
        'bajo_medio_meses' => 6, // Norma (RCG Art. 23 Bis 1): al menos cada 6 meses
        'alto_meses'       => 3, // Política de AvalID (editable por tenant, <= 6 meses)
    ],

    /*
    |--------------------------------------------------------------------------
    | Textos Normativos Fijos (Inmutables — Prohibido Regenerar)
    |--------------------------------------------------------------------------
    */
    'textos_fijos' => [
        'factores_transaccionales' => 'Factores transaccionales: no evaluados por AvalID. Los determina el sujeto obligado con su propio Perfil Transaccional.',
        'descargo_base'            => 'Este expediente ha sido generado de manera automatizada a partir de los resultados técnicos devueltos por las fuentes consultadas en la fecha y hora indicadas. La información aquí contenida tiene fines estrictamente informativos y de debida diligencia documental.',
        'descargo_linea_ebr'       => 'El Nivel de Riesgo de Persona es un insumo informativo elaborado con fuentes y fecha indicadas. No constituye el Grado de Riesgo del sujeto obligado conforme a su metodología (RCG, Cap. III Bis), no incluye factores transaccionales y no sustituye su debida diligencia ni su dictamen de cumplimiento.',
    ],
];
