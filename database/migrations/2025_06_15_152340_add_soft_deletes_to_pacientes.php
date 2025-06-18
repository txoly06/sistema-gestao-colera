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
        Schema::table('pacientes', function (Blueprint $table) {
            // Adicionar SoftDeletes
            $table->softDeletes();
            
            // Adicionar campos adicionais relevantes
            $table->text('email_encrypted')->nullable()->comment('Email criptografado com AES-256');
            $table->text('historico_saude_encrypted')->nullable()->comment('Histórico médico criptografado');
            $table->enum('grupo_sanguineo', ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])->nullable();
            $table->boolean('tem_alergias')->default(false);
            $table->text('alergias_encrypted')->nullable()->comment('Alergias criptografadas');
            $table->enum('estado', ['Ativo', 'Em_Tratamento', 'Recuperado', 'Óbito'])->default('Ativo');
            $table->foreignId('unidade_saude_id')->nullable()->constrained('unidades_saude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pacientes', function (Blueprint $table) {
            //
        });
    }
};
