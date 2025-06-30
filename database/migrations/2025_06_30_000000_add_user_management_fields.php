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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('status', ['ativo', 'bloqueado', 'inativo'])->default('ativo')->after('password');
            $table->string('categoria')->nullable()->after('status');
            $table->string('cargo')->nullable()->after('categoria');
            $table->string('telefone')->nullable()->after('cargo');
            $table->unsignedBigInteger('unidade_saude_id')->nullable()->after('telefone');
            $table->timestamp('ultimo_login')->nullable()->after('remember_token');
            $table->text('observacoes')->nullable()->after('ultimo_login');
            
            // Foreign key para unidade de saúde
            $table->foreign('unidade_saude_id')->references('id')->on('unidades_saude')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['unidade_saude_id']);
            $table->dropColumn([
                'status',
                'categoria',
                'cargo',
                'telefone',
                'unidade_saude_id',
                'ultimo_login',
                'observacoes'
            ]);
        });
    }
};
