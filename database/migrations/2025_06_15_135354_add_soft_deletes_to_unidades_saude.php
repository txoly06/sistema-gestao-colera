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
        Schema::table('unidades_saude', function (Blueprint $table) {
            // Adicionar soft delete
            $table->softDeletes();
            
            // Campos adicionais
            $table->string('diretor_medico')->nullable()->after('nome');
            $table->string('telefone', 20)->nullable()->after('endereco');
            $table->string('email')->nullable()->after('telefone');
            $table->boolean('tem_isolamento')->default(false)->after('capacidade');
            $table->integer('capacidade_isolamento')->nullable()->after('tem_isolamento');
            $table->integer('casos_ativos')->default(0)->after('capacidade_isolamento');
            $table->integer('leitos_ocupados')->default(0)->after('casos_ativos');
            $table->enum('nivel_alerta', ['Baixo', 'Medio', 'Alto', 'Critico'])->default('Baixo')->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('unidades_saude', function (Blueprint $table) {
            // Remover campos adicionais e soft delete
            $table->dropColumn([
                'deleted_at', 
                'diretor_medico', 
                'telefone', 
                'email', 
                'tem_isolamento', 
                'capacidade_isolamento', 
                'casos_ativos', 
                'leitos_ocupados', 
                'nivel_alerta'
            ]);
        });
    }
};
