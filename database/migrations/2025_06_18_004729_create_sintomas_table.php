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
        Schema::create('sintomas', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100);
            $table->string('descricao', 255)->nullable();
            $table->integer('gravidade')->comment('Escala de 1-5, onde 5 é mais grave'); // Escala de gravidade
            $table->boolean('especifico_colera')->default(false)->comment('Indica se é um sintoma específico da cólera');
            $table->string('categoria', 50)->nullable()->comment('Categoria do sintoma: gastrointestinal, desidratação, etc.');
            $table->json('sintomas_relacionados')->nullable()->comment('IDs de sintomas frequentemente associados');
            $table->timestamps();
            
            // Garantir que não haja nomes duplicados
            $table->unique('nome');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sintomas');
    }
};
