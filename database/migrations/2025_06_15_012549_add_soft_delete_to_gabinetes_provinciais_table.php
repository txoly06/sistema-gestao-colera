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
        Schema::table('gabinetes_provinciais', function (Blueprint $table) {
            // Adicionar o campo para soft delete
            $table->softDeletes();
            
            // Adicionar campos para diretor e status ativo
            $table->string('diretor')->nullable()->after('email');
            $table->boolean('ativo')->default(true)->after('longitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gabinetes_provinciais', function (Blueprint $table) {
            // Remover o campo de soft delete
            $table->dropSoftDeletes();
            
            // Remover os outros campos adicionados
            $table->dropColumn(['diretor', 'ativo']);
        });
    }
};
