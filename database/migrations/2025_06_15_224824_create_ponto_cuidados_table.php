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
        Schema::create('ponto_cuidados', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->text('descricao');
            $table->string('endereco');
            $table->string('telefone')->nullable();
            $table->string('email')->nullable();
            $table->string('responsavel');
            $table->integer('capacidade_maxima');
            $table->integer('capacidade_atual')->default(0);
            $table->string('provincia');
            $table->string('municipio');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('tem_ambulancia')->default(false);
            $table->integer('ambulancias_disponiveis')->default(0);
            $table->string('nivel_prontidao')->default('Normal'); // Normal, Alerta, Emergência
            $table->string('status')->default('Ativo'); // Ativo, Inativo, Manutenção
            $table->foreignId('unidade_saude_id')->nullable()->constrained('unidades_saude')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ponto_cuidados');
    }
};
