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
    
    // Rotas públicas - limite de 5 tentativas por minuto para evitar ataques de força bruta
    Route::middleware(['throttle:5,1'])->group(function () {
        Route::post('/login', [\App\Http\Controllers\Api\V1\Auth\AuthController::class, 'login'])->name('login');
    });
    
    // Rotas protegidas - limite padrão de 60 requisições por minuto para usuários autenticados
    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
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
        Route::get('pacientes/{paciente}/triagens', [\App\Http\Controllers\Api\V1\TriagemController::class, 'triagensPorPaciente'])
            ->name('pacientes.triagens')
            ->middleware('permission:ver triagens');
            
        // Pontos de Cuidados de Emergência - endpoint crítico com limite mais restritivo
        Route::middleware(['throttle:30,1'])->group(function () {
            Route::apiResource('pontos-cuidado', \App\Http\Controllers\Api\V1\PontoCuidadoController::class)
                ->middleware('permission:ver pontos-cuidado|criar pontos-cuidado|editar pontos-cuidado|eliminar pontos-cuidado');
        });
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
        
        // Veículos - endpoint crítico com limite mais restritivo
        Route::middleware(['throttle:30,1'])->group(function () {
            Route::apiResource('veiculos', \App\Http\Controllers\Api\V1\VeiculoController::class)
                ->middleware('permission:ver veiculos|criar veiculos|editar veiculos|eliminar veiculos');
        });
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
        
        // Sintomas e Triagens - endpoint crítico com limite mais restritivo
        Route::middleware(['throttle:30,1'])->group(function () {
            Route::apiResource('triagens', \App\Http\Controllers\Api\V1\TriagemController::class)
                ->middleware('permission:ver triagens|criar triagens|editar triagens|eliminar triagens');
        });
        Route::put('triagens/{id}/status', [\App\Http\Controllers\Api\V1\TriagemController::class, 'atualizarStatus'])
            ->name('triagens.status')
            ->middleware('permission:editar triagens');
        Route::post('triagens/{id}/encaminhar', [\App\Http\Controllers\Api\V1\TriagemController::class, 'encaminhar'])
            ->name('triagens.encaminhar')
            ->middleware('permission:editar triagens');
        Route::get('sintomas', [\App\Http\Controllers\Api\V1\TriagemController::class, 'sintomas'])
            ->name('triagens.sintomas');
            
        // Mapas e Geolocalização - serviço externo, maior proteção de limite
        Route::prefix('mapa')->name('mapa.')->middleware(['throttle:20,1'])->group(function () {
            Route::get('pontos', [\App\Http\Controllers\Api\V1\MapaController::class, 'todosOsPontos'])
                ->name('pontos')
                ->middleware('permission:ver pontos-cuidado|ver veiculos');
                
            Route::get('pontos-cuidado-proximos', [\App\Http\Controllers\Api\V1\MapaController::class, 'pontosCuidadoProximos'])
                ->name('pontos-proximos')
                ->middleware('permission:ver pontos-cuidado');
                
            Route::get('veiculos-proximos', [\App\Http\Controllers\Api\V1\MapaController::class, 'veiculosProximos'])
                ->name('veiculos-proximos')
                ->middleware('permission:ver veiculos');
                
            Route::post('calcular-rota', [\App\Http\Controllers\Api\V1\MapaController::class, 'calcularRota'])
                ->name('rota');
                
            Route::post('geocodificar', [\App\Http\Controllers\Api\V1\MapaController::class, 'geocodificarEndereco'])
                ->name('geocodificar');
                
            Route::get('heat-map-casos', [\App\Http\Controllers\Api\V1\MapaController::class, 'heatMapCasos'])
                ->name('heat-map')
                ->middleware('permission:ver triagens');
        });
        
        // Relatórios e Dashboards - operações pesadas, limite mais restritivo
        Route::prefix('relatorios')->name('relatorios.')->middleware(['permission:ver relatorios', 'throttle:15,1'])->group(function () {
            Route::get('estatisticas-gerais', [\App\Http\Controllers\Api\V1\RelatorioController::class, 'estatisticasGerais'])
                ->name('estatisticas');
                
            Route::get('casos-por-provincia', [\App\Http\Controllers\Api\V1\RelatorioController::class, 'casosPorProvincia'])
                ->name('casos-provincia');
                
            Route::get('evolucao-temporal', [\App\Http\Controllers\Api\V1\RelatorioController::class, 'evolucaoTemporal'])
                ->name('evolucao');
                
            Route::get('distribuicao-urgencia', [\App\Http\Controllers\Api\V1\RelatorioController::class, 'distribuicaoUrgencia'])
                ->name('urgencia');
                
            Route::get('ocupacao-pontos-cuidado', [\App\Http\Controllers\Api\V1\RelatorioController::class, 'ocupacaoPontosCuidado'])
                ->name('ocupacao');
                
            Route::get('dados-demograficos', [\App\Http\Controllers\Api\V1\RelatorioController::class, 'dadosDemograficos'])
                ->name('demograficos');
        });
            
        Route::get('triagens-criticas', [\App\Http\Controllers\Api\V1\TriagemController::class, 'triagensCriticas'])
            ->name('triagens.criticas')
            ->middleware('permission:ver triagens');
            
        // Auditoria - apenas para administradores
        Route::prefix('auditoria')->name('auditoria.')->middleware(['permission:ver auditoria', 'throttle:10,1'])->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\AuditoriaController::class, 'index'])
                ->name('index');
                
            Route::get('resumo', [\App\Http\Controllers\Api\V1\AuditoriaController::class, 'resumo'])
                ->name('resumo');
                
            Route::get('{id}', [\App\Http\Controllers\Api\V1\AuditoriaController::class, 'show'])
                ->name('show');
                
            Route::get('usuario/{usuarioId}', [\App\Http\Controllers\Api\V1\AuditoriaController::class, 'porUsuario'])
                ->name('por-usuario');
        });
    });
});
