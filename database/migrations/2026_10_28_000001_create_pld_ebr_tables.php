<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 0. Agregar api_key a tenants y campos opcionales de contexto PLD a subjects
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'api_key')) {
                $table->string('api_key', 80)->nullable()->unique()->after('activo');
            }
        });

        Schema::table('subjects', function (Blueprint $table) {
            if (!Schema::hasColumn('subjects', 'nacionalidad')) {
                $table->string('nacionalidad')->nullable()->after('nivel_producto');
            }
            if (!Schema::hasColumn('subjects', 'entidad_nacimiento')) {
                $table->string('entidad_nacimiento')->nullable()->after('nacionalidad');
            }
            if (!Schema::hasColumn('subjects', 'actividad_economica')) {
                $table->string('actividad_economica')->nullable()->after('entidad_nacimiento');
            }
            if (!Schema::hasColumn('subjects', 'fuentes_ingreso')) {
                $table->string('fuentes_ingreso')->nullable()->after('actividad_economica');
            }
            if (!Schema::hasColumn('subjects', 'perfil_transaccional')) {
                $table->text('perfil_transaccional')->nullable()->after('fuentes_ingreso');
            }
            if (!Schema::hasColumn('subjects', 'origen_destino_recursos')) {
                $table->text('origen_destino_recursos')->nullable()->after('perfil_transaccional');
            }
            if (!Schema::hasColumn('subjects', 'declaracion_personal')) {
                $table->json('declaracion_personal')->nullable()->after('origen_destino_recursos');
            }
            if (!Schema::hasColumn('subjects', 'estructura_accionaria')) {
                $table->json('estructura_accionaria')->nullable()->after('declaracion_personal');
            }
            if (!Schema::hasColumn('subjects', 'jurisdiccion_riesgo')) {
                $table->boolean('jurisdiccion_riesgo')->nullable()->after('estructura_accionaria');
            }
        });

        // 1. Catálogo Global de Fuentes PLD (super_admin)
        Schema::create('pld_fuente_catalogo', function (Blueprint $table) {
            $table->id();
            $table->string('fuente_key', 64)->unique();
            $table->string('nombre');
            $table->string('categoria', 32); // IDENTIDAD | PLD | CONSISTENCIA | REPUTACIONAL
            $table->string('criticidad', 16); // ALTA | MEDIA | BAJA
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // 2. Política de Puntuación por Tenant (M1)
        Schema::create('pld_scoring_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('categoria', 32); // IDENTIDAD | PLD | CONSISTENCIA | REPUTACIONAL
            $table->unsignedInteger('peso_porcentual')->default(25);
            $table->json('triggers_config');
            $table->unsignedInteger('intervalo_reevaluacion_bajo_medio_meses')->default(6);
            $table->unsignedInteger('intervalo_reevaluacion_alto_meses')->default(3);
            $table->timestamps();

            $table->unique(['tenant_id', 'categoria']);
        });

        // 3. Expedientes PLD de Sujetos (M2, M3, M4, M5)
        Schema::create('expedientes_pld_sujetos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->text('rfc_cifrado'); // AES-256-GCM (nonce 96b + tag 128b + ciphertext)
            $table->string('rfc_bindex', 64)->index(); // HMAC-SHA256 para búsqueda exacta sin descifrar
            $table->string('tipo_persona', 16); // FISICA | MORAL
            $table->unsignedInteger('score_global');
            $table->json('scores_categoria');
            $table->string('nivel_riesgo_persona', 16); // BAJO | MEDIO | ALTO
            $table->string('recomendacion', 48); // PROCEDER_SIN_OBSERVACIONES | PROCEDER_CON_OBSERVACIONES | REVISION_MANUAL | NO_PROCEDER
            $table->boolean('edd_recomendada')->default(false);
            $table->text('edd_causa')->nullable();
            $table->json('factores_riesgo_json');
            $table->json('hallazgos_json');
            $table->json('fuentes_pendientes_json');
            $table->timestamp('conservar_hasta');
            $table->string('finalidad', 32)->default('alta_cliente'); // alta_cliente | seleccion_personal | otro
            $table->string('folio_vigente', 64)->nullable();
            $table->unsignedInteger('version_vigente')->default(1);
            $table->timestamp('proxima_reevaluacion')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'subject_id']);
        });

        // 4. Suscripciones de Monitoreo / Reconsulta Periódica (M8)
        Schema::create('pld_suscripciones', function (Blueprint $table) {
            $table->uuid('suscripcion_id')->primary();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->string('finalidad', 32)->default('alta_cliente');
            $table->string('consentimiento_ref')->nullable();
            $table->boolean('consentimiento_vigente')->default(true);
            $table->unsignedInteger('intervalo_meses')->default(6);
            $table->string('estado', 16)->default('ACTIVA'); // ACTIVA | PAUSADA | BAJA
            $table->string('motivo_baja')->nullable();
            $table->timestamp('proxima_reevaluacion')->nullable();
            $table->timestamp('ultima_reevaluacion_identidad')->nullable();
            $table->json('fallos_consecutivos_por_fuente')->nullable();
            $table->timestamps();

            $table->index(['estado', 'proxima_reevaluacion']);
        });

        // 5. Versiones Inmutables del Expediente con Hash Encadenado (M8)
        Schema::create('pld_expediente_versiones', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 64)->unique(); // {subject_id}-v{n}
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->uuid('expediente_pld_id')->nullable()->index();
            $table->unsignedInteger('version');
            $table->string('tipo_ejecucion', 40); // INICIAL | REEVALUACION_PERIODICA | REEVALUACION_ANUAL_IDENTIDAD | REEVALUACION_POR_EVENTO | BAJO_DEMANDA | CORRECCION
            $table->string('folio_corregido', 64)->nullable();
            $table->timestamp('fecha_generacion');
            $table->string('hash_version', 64);
            $table->string('hash_previo', 64)->nullable();
            $table->longText('payload_canonico'); // JSON canónico JCS (RFC 8785)
            $table->longText('expediente_formateado_texto')->nullable();
            $table->json('cambios_resumen')->nullable();
            $table->unsignedInteger('schema_version')->default(1);
            $table->timestamps();

            $table->unique(['subject_id', 'version']);
        });

        // 6. Resultados de Fuentes por Versión/Folio (M8)
        Schema::create('pld_fuente_resultado', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 64)->index();
            $table->string('fuente', 64);
            $table->string('categoria', 32);
            $table->string('criticidad', 16)->nullable();
            $table->unsignedInteger('status_http')->default(200);
            $table->json('resultado_crudo')->nullable();
            $table->timestamp('fecha_consulta');
            $table->timestamp('ultima_consulta_exitosa')->nullable();
            $table->string('estado', 24)->default('CONSULTADA'); // CONSULTADA | NO_RECONSULTADA
            $table->timestamps();
        });

        // 7. Eventos y Cola de Webhooks (M8, M9)
        Schema::create('pld_eventos', function (Blueprint $table) {
            $table->string('evento_id', 64)->primary();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->uuid('suscripcion_id')->nullable()->index();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->string('tipo', 40); // NIVEL_CAMBIO | HALLAZGO_CRITICO | RECOMENDACION_CAMBIO | REEVALUACION_VENCIDA | FUENTE_CAIDA_PERSISTENTE | REEVALUACION_SIN_CAMBIOS
            $table->string('folio_origen', 64);
            $table->json('payload_webhook');
            $table->timestamp('fecha');
            $table->timestamp('entregado_en')->nullable();
            $table->unsignedInteger('intentos')->default(0);
            $table->text('ultimo_error')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'fecha']);
        });

        // 8. Endpoints de Webhooks por Tenant (M9)
        Schema::create('pld_webhook_endpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('url');
            $table->text('secreto'); // Cifrado en reposo
            $table->json('eventos_suscritos');
            $table->boolean('incluir_sin_cambios')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pld_webhook_endpoints');
        Schema::dropIfExists('pld_eventos');
        Schema::dropIfExists('pld_fuente_resultado');
        Schema::dropIfExists('pld_expediente_versiones');
        Schema::dropIfExists('pld_suscripciones');
        Schema::dropIfExists('expedientes_pld_sujetos');
        Schema::dropIfExists('pld_scoring_policies');
        Schema::dropIfExists('pld_fuente_catalogo');

        Schema::table('subjects', function (Blueprint $table) {
            $cols = [
                'nacionalidad',
                'entidad_nacimiento',
                'actividad_economica',
                'fuentes_ingreso',
                'perfil_transaccional',
                'origen_destino_recursos',
                'declaracion_personal',
                'estructura_accionaria',
                'jurisdiccion_riesgo',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('subjects', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'api_key')) {
                $table->dropColumn('api_key');
            }
        });
    }
};
