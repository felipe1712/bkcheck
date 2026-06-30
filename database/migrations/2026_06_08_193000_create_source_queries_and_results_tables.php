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
        Schema::create('source_queries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('subject_id');
            $table->string('source_type'); // 'rfc', 'csd', 'siger', 'sat_listas', 'marcas'
            $table->string('status')->default('pending'); // 'pending', 'processing', 'completed', 'failed'
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
        });

        Schema::create('source_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('source_query_id');
            $table->json('raw_payload');
            $table->json('processed_data')->nullable();
            $table->timestamps();

            $table->foreign('source_query_id')->references('id')->on('source_queries')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('source_results');
        Schema::dropIfExists('source_queries');
    }
};
