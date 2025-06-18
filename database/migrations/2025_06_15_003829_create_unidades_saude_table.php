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
        Schema::create('unidades_saude', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gabinete_provincial_id')->constrained('gabinetes_provinciais');
            $table->string('nome');
            $table->enum('tipo', ['Hospital_Geral', 'Centro_Saude', 'Posto_Medico', 'Clinica', 'Outro']);
            $table->string('endereco');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->integer('capacidade')->nullable();
            $table->enum('status', ['Ativo', 'Inativo', 'Em_Manutencao', 'Sobrelotado']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unidades_saude');
    }
};
