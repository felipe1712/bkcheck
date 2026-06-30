<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('project_id');
            $table->string('tipo'); // persona_fisica, persona_moral
            $table->string('name_or_company');
            $table->text('rfc'); // Encrypted
            $table->text('curp')->nullable(); // Encrypted, physical person only
            $table->text('address')->nullable(); // Encrypted
            $table->boolean('consent_granted')->default(false);
            $table->timestamp('consent_date')->nullable();
            $table->string('consent_legal_basis')->nullable();
            $table->string('consent_document_path')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subjects');
    }
};
