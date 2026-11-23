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
            // Permitir registro de persona física únicamente con CURP (salvaguarda Fase 1)
            $table->string('name_or_company')->nullable()->change();
            $table->text('rfc')->nullable()->change();

            if (!Schema::hasColumn('subjects', 'first_name')) {
                $table->string('first_name')->nullable()->after('name_or_company');
            }
            if (!Schema::hasColumn('subjects', 'father_surname')) {
                $table->string('father_surname')->nullable()->after('first_name');
            }
            if (!Schema::hasColumn('subjects', 'mother_surname')) {
                $table->string('mother_surname')->nullable()->after('father_surname');
            }
            if (!Schema::hasColumn('subjects', 'birth_date')) {
                $table->date('birth_date')->nullable()->after('mother_surname');
            }
            if (!Schema::hasColumn('subjects', 'phone')) {
                $table->text('phone')->nullable()->after('address'); // Encrypted
            }
            if (!Schema::hasColumn('subjects', 'email')) {
                $table->text('email')->nullable()->after('phone'); // Encrypted
            }
            if (!Schema::hasColumn('subjects', 'known_aliases')) {
                $table->json('known_aliases')->nullable()->after('username');
            }
            if (!Schema::hasColumn('subjects', 'rfc_source')) {
                $table->string('rfc_source', 30)->default('manual')->after('rfc');
            }
            if (!Schema::hasColumn('subjects', 'identity_confidence_score')) {
                $table->unsignedTinyInteger('identity_confidence_score')->nullable()->after('rfc_source');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $columns = [
                'first_name',
                'father_surname',
                'mother_surname',
                'birth_date',
                'phone',
                'email',
                'known_aliases',
                'rfc_source',
                'identity_confidence_score',
            ];
            foreach ($columns as $col) {
                if (Schema::hasColumn('subjects', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
