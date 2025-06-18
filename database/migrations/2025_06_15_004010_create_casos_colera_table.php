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
        Schema::create('casos_colera', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ficha_clinica_id')->constrained('fichas_clinicas');
            $table->foreignId('unidade_saude_id')->constrained('unidades_saude');
            $table->dateTime('data_confirmacao');
            $table->enum('estado', ['Suspeito', 'Confirmado', 'Em_Tratamento', 'Recuperado', 'Obito']);
            $table->string('fonte_contaminacao')->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamp('data_alta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('casos_colera');
    }
};
