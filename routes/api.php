<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Aqui é onde você pode registrar as rotas da API para o seu aplicativo.
| Estas rotas são carregadas pelo RouteServiceProvider e todas serão
| atribuídas ao grupo de middleware "api". Faça algo incrível!
|
*/

// Versão 1 da API
Route::prefix('v1')->name('api.v1.')->group(function () {
    
    // Rotas públicas
    Route::post('/login', [\App\Http\Controllers\Api\V1\Auth\AuthController::class, 'login'])->name('login');
    
    // Rotas protegidas
    Route::middleware('auth:sanctum')->group(function () {
        // Autenticação
        Route::post('/logout', [\App\Http\Controllers\Api\V1\Auth\AuthController::class, 'logout'])->name('logout');
        Route::get('/user', [\App\Http\Controllers\Api\V1\Auth\AuthController::class, 'user'])->name('user');
        
        // Gabinetes Provinciais
        Route::apiResource('gabinetes', \App\Http\Controllers\Api\V1\GabineteProvincialController::class)
            ->middleware('permission:gabinetes.listar|gabinetes.visualizar|gabinetes.criar|gabinetes.editar|gabinetes.eliminar');
        
        // Unidades de Saúde
        Route::apiResource('unidades', \App\Http\Controllers\Api\V1\UnidadeSaudeController::class)
            ->middleware('permission:ver unidades-saude|criar unidades-saude|editar unidades-saude|eliminar unidades-saude');
        
        // Pacientes
        Route::apiResource('pacientes', \App\Http\Controllers\Api\V1\PacienteController::class);
        Route::get('pacientes/{paciente}/qrcode', [\App\Http\Controllers\Api\V1\PacienteController::class, 'generateQrCode'])
            ->name('pacientes.qrcode');
            
        // Pontos de Cuidados de Emergência
        Route::apiResource('pontos-cuidado', \App\Http\Controllers\Api\V1\PontoCuidadoController::class)
            ->middleware('permission:ver pontos-cuidado|criar pontos-cuidado|editar pontos-cuidado|eliminar pontos-cuidado');
        Route::put('pontos-cuidado/{id}/prontidao', [\App\Http\Controllers\Api\V1\PontoCuidadoController::class, 'updateProntidao'])
            ->name('pontos-cuidado.prontidao')
            ->middleware('permission:atualizar-prontidao pontos-cuidado');
        Route::put('pontos-cuidado/{id}/capacidade', [\App\Http\Controllers\Api\V1\PontoCuidadoController::class, 'updateCapacidade'])
            ->name('pontos-cuidado.capacidade')
            ->middleware('permission:atualizar-capacidade pontos-cuidado');
        
        // Fichas Clínicas
        Route::apiResource('fichas', \App\Http\Controllers\Api\V1\FichaClinicaController::class)
            ->middleware('permission:fichas.listar|fichas.visualizar|fichas.criar|fichas.editar|fichas.eliminar');
        
        // Casos de Cólera
        Route::apiResource('casos', \App\Http\Controllers\Api\V1\CasoColerController::class)
            ->middleware('permission:casos.listar|casos.visualizar|casos.criar|casos.editar|casos.eliminar');
        Route::get('casos/estatisticas', [\App\Http\Controllers\Api\V1\CasoColerController::class, 'estatisticas'])
            ->middleware('permission:casos.relatorios')
            ->name('casos.estatisticas');
        
        // Veículos
        Route::apiResource('veiculos', \App\Http\Controllers\Api\V1\VeiculoController::class)
            ->middleware('permission:ver veiculos|criar veiculos|editar veiculos|eliminar veiculos');
        Route::put('veiculos/{id}/status', [\App\Http\Controllers\Api\V1\VeiculoController::class, 'updateStatus'])
            ->name('veiculos.status')
            ->middleware('permission:editar veiculos');
        Route::put('veiculos/{id}/localizacao', [\App\Http\Controllers\Api\V1\VeiculoController::class, 'updateLocalizacao'])
            ->name('veiculos.localizacao')
            ->middleware('permission:editar veiculos');
        Route::put('veiculos/{id}/combustivel', [\App\Http\Controllers\Api\V1\VeiculoController::class, 'updateCombustivel'])
            ->name('veiculos.combustivel')
            ->middleware('permission:editar veiculos');
        Route::get('veiculos-disponiveis', [\App\Http\Controllers\Api\V1\VeiculoController::class, 'disponiveis'])
            ->name('veiculos.disponiveis')
            ->middleware('permission:ver veiculos');
        Route::get('veiculos-por-tipo/{tipo}', [\App\Http\Controllers\Api\V1\VeiculoController::class, 'porTipo'])
            ->name('veiculos.tipo')
            ->middleware('permission:ver veiculos');
        
        // Encaminhamentos
        Route::apiResource('encaminhamentos', \App\Http\Controllers\Api\V1\EncaminhamentoController::class)
            ->middleware('permission:encaminhamentos.listar|encaminhamentos.visualizar|encaminhamentos.criar|encaminhamentos.editar|encaminhamentos.eliminar');
    });
});
