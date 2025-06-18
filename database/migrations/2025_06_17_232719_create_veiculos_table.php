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
        Schema::create('veiculos', function (Blueprint $table) {
            $table->id();
            $table->string('placa', 10)->unique();
            $table->string('modelo', 100);
            $table->integer('ano');
            $table->enum('tipo', ['ambulancia', 'transporte', 'apoio']);
            $table->enum('status', ['disponivel', 'em_transito', 'em_manutencao', 'indisponivel']);
            $table->string('descricao', 255)->nullable();
            $table->integer('capacidade_pacientes');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamp('ultima_atualizacao_localizacao')->nullable();
            $table->json('equipamentos')->nullable(); // Lista de equipamentos disponíveis no veículo
            $table->json('equipe_medica')->nullable(); // Detalhes da equipe médica a bordo
            $table->boolean('tem_gps')->default(false);
            $table->integer('nivel_combustivel')->nullable(); // Percentual 0-100
            $table->foreignId('ponto_cuidado_id')->nullable()->constrained('ponto_cuidados')->onDelete('set null');
            $table->foreignId('unidade_saude_id')->nullable()->constrained('unidades_saude')->onDelete('set null');
            $table->string('responsavel', 100)->nullable();
            $table->string('contato_responsavel', 20)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('veiculos');
    }
};
