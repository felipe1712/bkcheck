<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->text('enrollment_terms')->nullable()->after('activo');
            $table->timestamp('enrollment_terms_updated_at')->nullable()->after('enrollment_terms');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['enrollment_terms', 'enrollment_terms_updated_at']);
        });
    }
};
