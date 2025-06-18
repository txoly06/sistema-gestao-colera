<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Paciente;
use App\Models\Triagem;
use App\Models\PontoCuidado;
use App\Models\Veiculo;
use App\Models\UnidadeSaude;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * @OA\Controller()
 * @OA\Tag(name="Relatórios", description="Endpoints para geração de relatórios e estatísticas do sistema")
 */
class RelatorioController extends ApiController
{
    /**
     * Retorna estatísticas gerais do sistema.
     *
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Get(
     *     path="/relatorios/estatisticas-gerais",
     *     summary="Estatísticas gerais do sistema",
     *     description="Retorna dados estatísticos gerais para dashboard e visão geral do sistema",
     *     operationId="estatisticasGerais",
     *     tags={"Relatórios"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Operação bem-sucedida",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Estatísticas gerais obtidas com sucesso"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="totais", type="object",
     *                     @OA\Property(property="pacientes", type="integer", example=1250),
     *                     @OA\Property(property="triagens", type="integer", example=800),
     *                     @OA\Property(property="pontos_cuidado", type="integer", example=15),
     *                     @OA\Property(property="veiculos", type="integer", example=40)
     *                 ),
     *                 @OA\Property(property="pacientes", type="object",
     *                     @OA\Property(property="ativos", type="integer", example=450),
     *                     @OA\Property(property="em_tratamento", type="integer", example=350),
     *                     @OA\Property(property="recuperados", type="integer", example=400),
     *                     @OA\Property(property="obitos", type="integer", example=50),
     *                     @OA\Property(property="porcentagem_recuperacao", type="number", format="float", example=32.0),
     *                     @OA\Property(property="porcentagem_obito", type="number", format="float", example=4.0)
     *                 ),
     *                 @OA\Property(property="operacao", type="object",
     *                     @OA\Property(property="triagens_criticas", type="integer", example=120),
     *                     @OA\Property(property="veiculos_disponiveis", type="integer", example=25),
     *                     @OA\Property(property="taxa_ocupacao", type="number", format="float", example=78.5)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Não autorizado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Não autorizado")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro ao gerar estatísticas",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erro ao gerar estatísticas: [detalhes do erro]")
     *         )
     *     )
     * )
     */
    public function estatisticasGerais(): JsonResponse
    {
        // Verificar permissão
        if (!auth()->user()->can('ver relatorios')) {
            return $this->errorResponse('Não autorizado', 403);
        }
        
        try {
            // Estatísticas para dashboard principal
            $totalPacientes = Paciente::count();
            $pacientesAtivos = Paciente::where('estado', 'Ativo')->count();
            $pacientesEmTratamento = Paciente::where('estado', 'Em_Tratamento')->count();
            $pacientesRecuperados = Paciente::where('estado', 'Recuperado')->count();
            $pacientesObito = Paciente::where('estado', 'Óbito')->count();
            
            $totalTriagens = Triagem::count();
            $triagensCriticas = Triagem::where('nivel_urgencia', 'alto')->orWhere('nivel_urgencia', 'critico')->count();
            $totalPontosCuidado = PontoCuidado::count();
            $totalVeiculos = Veiculo::count();
            $veiculosDisponiveis = Veiculo::where('status', 'disponivel')->count();
            
            $porcentagemRecuperacao = $totalPacientes > 0 ? 
                round(($pacientesRecuperados / $totalPacientes) * 100, 2) : 0;
            
            $porcentagemObito = $totalPacientes > 0 ? 
                round(($pacientesObito / $totalPacientes) * 100, 2) : 0;
                
            $taxaOcupacao = $totalPontosCuidado > 0 ?
                round((PontoCuidado::sum('capacidade_atual') / PontoCuidado::sum('capacidade_maxima')) * 100, 2) : 0;
            
            return $this->successResponse([
                'totais' => [
                    'pacientes' => $totalPacientes,
                    'triagens' => $totalTriagens,
                    'pontos_cuidado' => $totalPontosCuidado,
                    'veiculos' => $totalVeiculos,
                ],
                'pacientes' => [
                    'ativos' => $pacientesAtivos,
                    'em_tratamento' => $pacientesEmTratamento,
                    'recuperados' => $pacientesRecuperados,
                    'obitos' => $pacientesObito,
                    'porcentagem_recuperacao' => $porcentagemRecuperacao,
                    'porcentagem_obito' => $porcentagemObito
                ],
                'operacao' => [
                    'triagens_criticas' => $triagensCriticas,
                    'veiculos_disponiveis' => $veiculosDisponiveis,
                    'taxa_ocupacao' => $taxaOcupacao
                ]
            ], 'Estatísticas gerais obtidas com sucesso');
            
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao gerar estatísticas: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Retorna estatísticas de casos por província.
     *
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Get(
     *     path="/relatorios/casos-por-provincia",
     *     summary="Distribuição de casos por província",
     *     description="Retorna a contagem de casos agrupados por província",
     *     operationId="casosPorProvincia",
     *     tags={"Relatórios"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Operação bem-sucedida",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Distribuição de casos por província obtida com sucesso"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="dados", type="array", @OA\Items(type="object",
     *                     @OA\Property(property="provincia", type="string", example="Luanda"),
     *                     @OA\Property(property="total", type="integer", example=450)
     *                 )),
     *                 @OA\Property(property="total_provincias", type="integer", example=18)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Não autorizado"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro ao gerar estatísticas por província"
     *     )
     * )
     */
    public function casosPorProvincia(): JsonResponse
    {
        // Verificar permissão
        if (!auth()->user()->can('ver relatorios')) {
            return $this->errorResponse('Não autorizado', 403);
        }
        
        try {
            $dados = Paciente::select('provincia', DB::raw('count(*) as total'))
                            ->groupBy('provincia')
                            ->orderByDesc('total')
                            ->get();
                
            return $this->successResponse([
                'dados' => $dados,
                'total_provincias' => $dados->count()
            ], 'Distribuição de casos por província obtida com sucesso');
            
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao gerar estatísticas por província: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Retorna evolução temporal de casos.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Get(
     *     path="/relatorios/evolucao-temporal",
     *     summary="Evolução temporal de casos",
     *     description="Retorna dados para visualização da evolução temporal de casos",
     *     operationId="evolucaoTemporal",
     *     tags={"Relatórios"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="periodo",
     *         in="query",
     *         description="Período para análise (dia, semana, mes, ano)",
     *         required=false,
     *         @OA\Schema(type="string", default="semana", enum={"dia", "semana", "mes", "ano"})
     *     ),
     *     @OA\Parameter(
     *         name="data_inicio",
     *         in="query",
     *         description="Data inicial para o relatório (formato: Y-m-d)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="data_fim",
     *         in="query",
     *         description="Data final para o relatório (formato: Y-m-d)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Operação bem-sucedida",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Evolução temporal obtida com sucesso"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="periodo", type="string", example="semana"),
     *                 @OA\Property(property="dados", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="filtros_aplicados", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Não autorizado"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro ao gerar evolução temporal"
     *     )
     * )
     */
    public function evolucaoTemporal(Request $request): JsonResponse
    {
        // Verificar permissão
        if (!auth()->user()->can('ver relatorios')) {
            return $this->errorResponse('Não autorizado', 403);
        }
        
        try {
            // Parâmetros de filtro
            $periodo = $request->query('periodo', 'mensal'); // diario, semanal, mensal
            $dataInicio = $request->query('data_inicio');
            $dataFim = $request->query('data_fim');
            
            // Configurar datas padrão se não forem fornecidas
            if (!$dataInicio) {
                $dataInicio = Carbon::now()->subMonths(3)->startOfDay();
            } else {
                $dataInicio = Carbon::parse($dataInicio)->startOfDay();
            }
            
            if (!$dataFim) {
                $dataFim = Carbon::now()->endOfDay();
            } else {
                $dataFim = Carbon::parse($dataFim)->endOfDay();
            }
            
            // Agrupar dados pela data de criação
            $query = Paciente::whereBetween('created_at', [$dataInicio, $dataFim]);
            
            // Usar funções compatíveis com SQLite
            switch ($periodo) {
                case 'diario':
                    $dados = $query->select(
                        DB::raw("strftime('%Y-%m-%d', created_at) as data"),
                        DB::raw('count(*) as total')
                    )
                    ->groupBy('data')
                    ->orderBy('data')
                    ->get();
                    break;
                    
                case 'semanal':
                    // Simplificado para SQLite
                    $dados = $query->select(
                        DB::raw("strftime('%Y-%W', created_at) as semana"),
                        DB::raw("strftime('%Y-%m-%d', created_at, 'weekday 0', '-6 days') as inicio_semana"),
                        DB::raw('count(*) as total')
                    )
                    ->groupBy('semana')
                    ->orderBy('semana')
                    ->get();
                    break;
                    
                case 'mensal':
                default:
                    $dados = $query->select(
                        DB::raw("strftime('%Y', created_at) as ano"),
                        DB::raw("strftime('%m', created_at) as mes"),
                        DB::raw("strftime('%Y-%m', created_at) as ano_mes"),
                        DB::raw('count(*) as total')
                    )
                    ->groupBy('ano', 'mes')
                    ->orderBy('ano')
                    ->orderBy('mes')
                    ->get();
                    break;
            }
            
            return $this->successResponse([
                'periodo' => $periodo,
                'data_inicio' => $dataInicio->toDateString(),
                'data_fim' => $dataFim->toDateString(),
                'dados' => $dados,
                'total_registros' => $dados->sum('total')
            ], 'Evolução temporal de casos obtida com sucesso');
            
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao gerar evolução temporal: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Retorna distribuição de níveis de urgência nas triagens.
     *
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Get(
     *     path="/relatorios/distribuicao-urgencia",
     *     summary="Distribuição de níveis de urgência",
     *     description="Retorna estatísticas sobre a distribuição de níveis de urgência nas triagens",
     *     operationId="distribuicaoUrgencia",
     *     tags={"Relatórios"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Operação bem-sucedida",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Distribuição de níveis de urgência obtida com sucesso"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="distribuicao", type="array", @OA\Items(type="object",
     *                     @OA\Property(property="nivel_urgencia", type="string", example="alto"),
     *                     @OA\Property(property="quantidade", type="integer", example=87),
     *                     @OA\Property(property="percentual", type="number", format="float", example=28.5)
     *                 )),
     *                 @OA\Property(property="total_triagens", type="integer", example=305)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Não autorizado"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro ao gerar distribuição de urgência"
     *     )
     * )
     */
    public function distribuicaoUrgencia(): JsonResponse
    {
        // Verificar permissão
        if (!auth()->user()->can('ver relatorios')) {
            return $this->errorResponse('Não autorizado', 403);
        }
        
        try {
            $dados = Triagem::select('nivel_urgencia', DB::raw('count(*) as total'))
                          ->groupBy('nivel_urgencia')
                          ->get();
            
            // Calcular porcentagens
            $total = $dados->sum('total');
            $dadosComPorcentagem = $dados->map(function($item) use ($total) {
                $item->porcentagem = $total > 0 ? round(($item->total / $total) * 100, 2) : 0;
                return $item;
            });
            
            return $this->successResponse([
                'distribuicao' => $dadosComPorcentagem,
                'total_triagens' => $total
            ], 'Distribuição de níveis de urgência obtida com sucesso');
            
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao gerar distribuição de urgência: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Retorna ocupação dos pontos de cuidado.
     *
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Get(
     *     path="/relatorios/ocupacao-pontos-cuidado",
     *     summary="Ocupação dos pontos de cuidado",
     *     description="Retorna estatísticas sobre a ocupação atual de cada ponto de cuidado",
     *     operationId="ocupacaoPontosCuidado",
     *     tags={"Relatórios"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Operação bem-sucedida",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Ocupação dos pontos de cuidado obtida com sucesso"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="pontos", type="array", @OA\Items(type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="nome", type="string", example="Ponto de Cuidado Central"),
     *                     @OA\Property(property="capacidade_atual", type="integer", example=45),
     *                     @OA\Property(property="capacidade_maxima", type="integer", example=60),
     *                     @OA\Property(property="percentual_ocupacao", type="number", format="float", example=75.0),
     *                     @OA\Property(property="nivel_prontidao", type="string", example="normal")
     *                 )),
     *                 @OA\Property(property="media_ocupacao", type="number", format="float", example=68.5),
     *                 @OA\Property(property="pontos_em_alerta", type="integer", example=3),
     *                 @OA\Property(property="total_pontos", type="integer", example=12)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Não autorizado"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro ao gerar dados de ocupação"
     *     )
     * )
     */
    public function ocupacaoPontosCuidado(): JsonResponse
    {
        // Verificar permissão
        if (!auth()->user()->can('ver relatorios')) {
            return $this->errorResponse('Não autorizado', 403);
        }
        
        try {
            $dados = PontoCuidado::select(
                            'id', 
                            'nome', 
                            'capacidade_atual', 
                            'capacidade_maxima',
                            'nivel_prontidao',
                            DB::raw('(capacidade_atual / capacidade_maxima) * 100 as porcentagem_ocupacao')
                        )
                        ->orderByDesc('porcentagem_ocupacao')
                        ->get();
            
            $totalCapacidade = $dados->sum('capacidade_maxima');
            $totalOcupacao = $dados->sum('capacidade_atual');
            $taxaOcupacaoGeral = $totalCapacidade > 0 ? round(($totalOcupacao / $totalCapacidade) * 100, 2) : 0;
            
            // Classificação por nível de ocupação
            $classificacao = [
                'critico' => $dados->where('porcentagem_ocupacao', '>=', 90)->count(),
                'alto' => $dados->whereBetween('porcentagem_ocupacao', [75, 89.99])->count(),
                'moderado' => $dados->whereBetween('porcentagem_ocupacao', [50, 74.99])->count(),
                'normal' => $dados->where('porcentagem_ocupacao', '<', 50)->count()
            ];
            
            return $this->successResponse([
                'pontos_cuidado' => $dados,
                'resumo' => [
                    'total_capacidade' => $totalCapacidade,
                    'total_ocupacao' => $totalOcupacao,
                    'taxa_ocupacao_geral' => $taxaOcupacaoGeral,
                    'classificacao' => $classificacao
                ]
            ], 'Ocupação dos pontos de cuidado obtida com sucesso');
            
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao gerar relatório de ocupação: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Retorna dados demográficos dos pacientes.
     *
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Get(
     *     path="/relatorios/dados-demograficos",
     *     summary="Dados demográficos dos pacientes",
     *     description="Retorna estatísticas demográficas dos pacientes (idade, sexo, etc.)",
     *     operationId="dadosDemograficos",
     *     tags={"Relatórios"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Operação bem-sucedida",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Dados demográficos obtidos com sucesso"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="distribuicao_sexo", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="distribuicao_idade", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="media_idade", type="number", format="float", example=34.5),
     *                 @OA\Property(property="grupo_sangue_predominante", type="string", example="O+"),
     *                 @OA\Property(property="total_analisado", type="integer", example=1250)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Não autorizado"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro ao gerar dados demográficos"
     *     )
     * )
     */
    public function dadosDemograficos(): JsonResponse
    {
        // Verificar permissão
        if (!auth()->user()->can('ver relatorios')) {
            return $this->errorResponse('Não autorizado', 403);
        }
        
        try {
            // Distribuição por sexo
            $distribuicaoSexo = Paciente::select('sexo', DB::raw('count(*) as total'))
                                     ->groupBy('sexo')
                                     ->get();
            
            // Distribuição por faixa etária
            $faixasEtarias = [
                '0-5' => [0, 5],
                '6-14' => [6, 14],
                '15-24' => [15, 24],
                '25-44' => [25, 44],
                '45-64' => [45, 64],
                '65+' => [65, 200]
            ];
            
            $distribuicaoIdade = [];
            
            foreach ($faixasEtarias as $faixa => $intervalo) {
                $count = Paciente::whereRaw("(strftime('%Y', 'now') - strftime('%Y', data_nascimento)) - (strftime('%m-%d', 'now') < strftime('%m-%d', data_nascimento)) >= ?", [$intervalo[0]])
                               ->whereRaw("(strftime('%Y', 'now') - strftime('%Y', data_nascimento)) - (strftime('%m-%d', 'now') < strftime('%m-%d', data_nascimento)) <= ?", [$intervalo[1]])
                               ->count();
                               
                $distribuicaoIdade[] = [
                    'faixa_etaria' => $faixa,
                    'total' => $count
                ];
            }
            
            // Total para cálculo de porcentagens
            $total = Paciente::count();
            
            return $this->successResponse([
                'total_pacientes' => $total,
                'distribuicao_sexo' => $distribuicaoSexo,
                'distribuicao_idade' => $distribuicaoIdade
            ], 'Dados demográficos obtidos com sucesso');
            
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao gerar dados demográficos: ' . $e->getMessage(), 500);
        }
    }
}
