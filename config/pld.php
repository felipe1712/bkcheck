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
        'lista_nominal'         => ['nombre' => 'Lista Nominal INE',                     'categoria' => 'IDENTIDAD',    'criticidad' => 'ALTA',  'activo' => true],
        'ine_vs_selfie'         => ['nombre' => 'Biometría Facial (INE vs Selfie)',      'categoria' => 'IDENTIDAD',    'criticidad' => 'ALTA',  'activo' => true],
        'selfie'                => ['nombre' => 'Prueba de Vida Biométrica (Liveness)',  'categoria' => 'IDENTIDAD',    'criticidad' => 'ALTA',  'activo' => true],
        'curp'                  => ['nombre' => 'Validación CURP / RENAPO',              'categoria' => 'IDENTIDAD',    'criticidad' => 'ALTA',  'activo' => true],
        'rfc'                   => ['nombre' => 'Validación RFC (SAT)',                  'categoria' => 'IDENTIDAD',    'criticidad' => 'ALTA',  'activo' => true],
        'ine_frente'            => ['nombre' => 'OCR Credencial INE Frente',             'categoria' => 'IDENTIDAD',    'criticidad' => 'MEDIA', 'activo' => true],
        'ine_reverso'           => ['nombre' => 'OCR Credencial INE Reverso',            'categoria' => 'IDENTIDAD',    'criticidad' => 'MEDIA', 'activo' => true],
        'comprobante_domicilio' => ['nombre' => 'OCR Comprobante de Domicilio',          'categoria' => 'IDENTIDAD',    'criticidad' => 'BAJA',  'activo' => true],
        'sanciones'             => ['nombre' => 'Listas PLD / OFAC / ONU / UE / PEPs',   'categoria' => 'PLD',          'criticidad' => 'ALTA',  'activo' => true],
        'sat_listas'            => ['nombre' => 'SAT Listas 69 y 69-B (EFOS/EDOS)',      'categoria' => 'PLD',          'criticidad' => 'ALTA',  'activo' => true],
        'siger'                 => ['nombre' => 'Registro Público de Comercio (SIGER)',  'categoria' => 'CONSISTENCIA', 'criticidad' => 'MEDIA', 'activo' => true],
        'nss_imss'              => ['nombre' => 'Historial Laboral NSS / IMSS',          'categoria' => 'CONSISTENCIA', 'criticidad' => 'BAJA',  'activo' => true],
        'denue'                 => ['nombre' => 'Directorio Empresarial DENUE (INEGI)',  'categoria' => 'CONSISTENCIA', 'criticidad' => 'BAJA',  'activo' => true],
        'litigios'              => ['nombre' => 'Antecedentes Judiciales y Litigios',    'categoria' => 'REPUTACIONAL', 'criticidad' => 'ALTA',  'activo' => true],
        'csd'                   => ['nombre' => 'Certificados CSD y e-Firma (SAT)',      'categoria' => 'REPUTACIONAL', 'criticidad' => 'MEDIA', 'activo' => true],
        'score_crediticio'      => ['nombre' => 'Buró de Crédito / Historial Crediticio','categoria' => 'REPUTACIONAL', 'criticidad' => 'MEDIA', 'activo' => true],
        'identidad_digital'     => ['nombre' => 'Perfil Digital y Enriquecimiento',      'categoria' => 'REPUTACIONAL', 'criticidad' => 'BAJA',  'activo' => true],
        'presencia_en_linea'    => ['nombre' => 'Presencia en Línea / Huella OSINT',     'categoria' => 'REPUTACIONAL', 'criticidad' => 'BAJA',  'activo' => true],
        'marcas'                => ['nombre' => 'Propiedad Industrial (IMPI)',           'categoria' => 'REPUTACIONAL', 'criticidad' => 'BAJA',  'activo' => true],
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
                'lista_nominal_inactiva' => ['label' => 'Lista Nominal INE = NO VIGENTE / INACTIVA / no coincide', 'efecto_tipo' => 'TOPE',      'valor' => 60, 'activo' => true],
                'biometria_menor_90'     => ['label' => 'Biometría facial < 90% de certeza',                       'efecto_tipo' => 'TOPE',      'valor' => 50, 'activo' => true],
                'biometria_no_coincide'  => ['label' => 'Biometría facial = NO COINCIDE',                          'efecto_tipo' => 'TOPE',      'valor' => 0,  'activo' => true],
                'curp_invalido'          => ['label' => 'CURP inválido ante RENAPO',                               'efecto_tipo' => 'TOPE',      'valor' => 30, 'activo' => true],
                'nombre_no_coincide'     => ['label' => 'Nombre no coincide entre INE / CURP / RFC',               'efecto_tipo' => 'TOPE',      'valor' => 70, 'activo' => true],
                'ocr_ilegible'           => ['label' => 'OCR ilegible o incompleto (pasa a no concluyente)',       'efecto_tipo' => 'DESCUENTO', 'valor' => 20, 'activo' => true],
            ],
        ],
        'PLD' => [
            'peso_porcentual' => 30,
            'triggers_config' => [
                'sanciones_ofac_onu_ue_lpb'  => ['label' => 'Coincidencia en OFAC / ONU / UE / LPB CNBV-UIF', 'efecto_tipo' => 'TOPE', 'valor' => 0,  'activo' => true],
                'registrado_pep'             => ['label' => 'Registrado como PEP (activa EDD)',               'efecto_tipo' => 'TOPE', 'valor' => 40, 'activo' => true],
                'lista_69b'                  => ['label' => 'Lista 69-B (EFOS/EDOS) con coincidencia',        'efecto_tipo' => 'TOPE', 'valor' => 0,  'activo' => true],
                'lista_69'                   => ['label' => 'Lista 69 con coincidencia',                      'efecto_tipo' => 'TOPE', 'valor' => 50, 'activo' => true],
                'fuente_sanciones_pendiente' => ['label' => 'Fuente de sanciones/PEP no respondió (pendiente)','efecto_tipo' => 'TOPE', 'valor' => 89, 'activo' => true],
            ],
        ],
        'CONSISTENCIA' => [
            'peso_porcentual' => 15,
            'triggers_config' => [
                'nombre_difiere'           => ['label' => 'Nombre completo difiere entre 2+ fuentes oficiales', 'efecto_tipo' => 'TOPE', 'valor' => 50, 'activo' => true],
                'fecha_nacimiento_difiere' => ['label' => 'Fecha de nacimiento difiere entre fuentes',          'efecto_tipo' => 'TOPE', 'valor' => 50, 'activo' => true],
                'rfc_curp_estructura'      => ['label' => 'RFC no coincide estructuralmente con CURP',          'efecto_tipo' => 'TOPE', 'valor' => 60, 'activo' => true],
            ],
        ],
        'REPUTACIONAL' => [
            'peso_porcentual' => 15,
            'triggers_config' => [
                'senales_adversas' => ['label' => 'Señales adversas explícitas en el JSON (nota roja, litigio, sanción pública)', 'efecto_tipo' => 'TOPE',      'valor' => 40, 'activo' => true],
                'csd_revocado'     => ['label' => 'Certificados CSD/e-firma revocados o cancelados',                              'efecto_tipo' => 'DESCUENTO', 'valor' => 10, 'activo' => true],
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
