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
        // Verifica se a tabela já existe e faz drop para recriá-la com a estrutura nova
        if (Schema::hasTable('encaminhamentos')) {
            Schema::dropIfExists('encaminhamentos');
        }

        Schema::create('encaminhamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('triagem_id')->nullable()->constrained('triagens')->onDelete('cascade');
            $table->foreignId('paciente_id')->constrained('pacientes')->onDelete('cascade');
            $table->foreignId('unidade_origem_id')->nullable()->constrained('unidades_saude')->onDelete('restrict');
            $table->foreignId('unidade_destino_id')->nullable()->constrained('unidades_saude')->onDelete('restrict');
            $table->foreignId('ponto_cuidado_origem_id')->nullable()->constrained('ponto_cuidados')->onDelete('restrict');
            $table->foreignId('ponto_cuidado_destino_id')->nullable()->constrained('ponto_cuidados')->onDelete('restrict');
            $table->foreignId('veiculo_id')->nullable()->constrained('veiculos')->onDelete('set null');
            $table->foreignId('responsavel_id')->nullable()->constrained('users')->onDelete('set null');
            
            $table->enum('status', ['pendente', 'aprovado', 'em_transporte', 'concluido', 'cancelado'])->default('pendente');
            $table->enum('prioridade', ['baixa', 'media', 'alta', 'emergencia'])->default('media');
            $table->enum('tipo_encaminhamento', ['unidade_para_unidade', 'unidade_para_ponto', 'ponto_para_unidade', 'ponto_para_ponto'])->nullable();
            
            $table->text('motivo');
            $table->text('observacoes')->nullable();
            $table->json('recursos_necessarios')->nullable();
            
            $table->dateTime('data_solicitacao');
            $table->dateTime('previsao_partida')->nullable();
            $table->dateTime('previsao_chegada')->nullable();
            $table->dateTime('data_inicio_transporte')->nullable();
            $table->dateTime('data_chegada')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
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
