<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            if (!Schema::hasColumn('subjects', 'finalidad_clave')) {
                $table->string('finalidad_clave')->default('CONTRATACION_LABORAL')->after('consent_legal_basis');
            }
            if (!Schema::hasColumn('subjects', 'nivel_producto')) {
                $table->string('nivel_producto')->default('due_diligence')->after('finalidad_clave');
            }
        });

        Schema::table('source_queries', function (Blueprint $table) {
            if (!Schema::hasColumn('source_queries', 'estado_evaluado')) {
                $table->string('estado_evaluado')->default('CONFIRMADO_POSITIVO')->after('status');
            }
            if (!Schema::hasColumn('source_queries', 'retry_count')) {
                $table->integer('retry_count')->default(0)->after('estado_evaluado');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn(['finalidad_clave', 'nivel_producto']);
        });

        Schema::table('source_queries', function (Blueprint $table) {
            $table->dropColumn(['estado_evaluado', 'retry_count']);
        });
    }
};
