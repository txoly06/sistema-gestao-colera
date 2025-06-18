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
        Schema::create('triagens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained('pacientes')->onDelete('cascade');
            $table->foreignId('unidade_saude_id')->nullable()->constrained('unidades_saude')->onDelete('set null');
            $table->foreignId('ponto_cuidado_id')->nullable()->constrained('ponto_cuidados')->onDelete('set null');
            $table->foreignId('responsavel_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Detalhes da triagem
            $table->enum('nivel_urgencia', ['baixo', 'medio', 'alto', 'critico'])->default('medio');
            $table->enum('status', ['pendente', 'em_andamento', 'concluida', 'encaminhada'])->default('pendente');
            $table->json('sintomas')->comment('IDs e intensidade dos sintomas registrados');
            $table->decimal('indice_desidratacao', 5, 2)->nullable()->comment('Percentual estimado de desidratação');
            $table->decimal('temperatura', 4, 2)->nullable()->comment('Temperatura em graus Celsius');
            $table->integer('frequencia_cardiaca')->nullable();
            $table->integer('frequencia_respiratoria')->nullable();
            $table->decimal('probabilidade_colera', 5, 2)->default(0)->comment('Probabilidade calculada de ser cólera (0-100%)');
            
            // Recomendações
            $table->json('recomendacoes')->nullable()->comment('Recomendações do sistema baseadas nos sintomas');
            $table->text('observacoes')->nullable();
            $table->json('encaminhamentos')->nullable()->comment('Histórico de encaminhamentos');
            
            // Datas importantes
            $table->timestamp('data_inicio_sintomas')->nullable();
            $table->timestamp('data_conclusao')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices para melhorar performance de consultas
            $table->index('nivel_urgencia');
            $table->index('status');
            $table->index(['paciente_id', 'created_at']);
            $table->index('probabilidade_colera');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('triagens');
    }
};
