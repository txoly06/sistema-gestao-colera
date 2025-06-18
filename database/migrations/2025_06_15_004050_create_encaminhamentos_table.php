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
        Schema::create('encaminhamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caso_colera_id')->constrained('casos_colera');
            $table->foreignId('unidade_origem_id')->constrained('unidades_saude');
            $table->foreignId('unidade_destino_id')->constrained('unidades_saude');
            $table->foreignId('veiculo_id')->nullable()->constrained('veiculos');
            $table->dateTime('data_encaminhamento');
            $table->enum('estado', ['Pendente', 'Em_Transito', 'Concluido', 'Cancelado']);
            $table->text('motivo');
            $table->text('observacoes')->nullable();
            $table->timestamp('data_chegada')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('encaminhamentos');
    }
};
