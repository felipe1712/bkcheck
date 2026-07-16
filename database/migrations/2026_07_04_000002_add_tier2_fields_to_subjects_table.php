<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            // Comprobante de domicilio (CFE, agua, etc.) — OCR NuFi
            $table->string('proof_of_address_path')->nullable()->after('username');

            // Número de Seguridad Social (IMSS) — encriptado, dato sensible
            $table->string('nss', 11)->nullable()->after('proof_of_address_path');

            // Consentimiento explícito adicional para consulta de score crediticio (Buró de Crédito)
            // La ley exige autorización expresa y específica para consultas al buró
            $table->boolean('credit_consent_granted')->default(false)->after('nss');
            $table->timestamp('credit_consent_at')->nullable()->after('credit_consent_granted');
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn([
                'proof_of_address_path',
                'nss',
                'credit_consent_granted',
                'credit_consent_at',
            ]);
        });
    }
};
