<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->string('enrollment_token')->unique()->nullable()->after('ine_back_path');
            $table->timestamp('enrollment_expires_at')->nullable()->after('enrollment_token');
            $table->timestamp('enrollment_completed_at')->nullable()->after('enrollment_expires_at');
            $table->string('enrollment_ip', 45)->nullable()->after('enrollment_completed_at');
            $table->timestamp('enrollment_tc_accepted_at')->nullable()->after('enrollment_ip');
            $table->string('selfie_path')->nullable()->after('enrollment_tc_accepted_at');
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn([
                'enrollment_token',
                'enrollment_expires_at',
                'enrollment_completed_at',
                'enrollment_ip',
                'enrollment_tc_accepted_at',
                'selfie_path',
            ]);
        });
    }
};
