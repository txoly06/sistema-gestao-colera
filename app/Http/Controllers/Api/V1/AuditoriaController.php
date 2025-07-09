<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;
use Carbon\Carbon;
use App\Http\Controllers\Api\V1\BaseController;

/**
 * @OA\Controller()
 */
class AuditoriaController extends BaseController
{
    /**
     * Lista todas as atividades de auditoria com opções de filtragem.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Get(
     *     path="/auditoria",
     *     summary="Lista atividades de auditoria",
     *     description="Retorna uma lista paginada de atividades de auditoria com opções de filtragem",
     *     operationId="auditLogsList",
     *     tags={"Auditoria"},
     *     security={"bearerAuth": {}},
     *     @OA\Parameter(
     *         name="data_inicio",
     *         in="query",
     *         description="Data de início para filtrar (formato Y-m-d)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="data_fim",
     *         in="query",
     *         description="Data de fim para filtrar (formato Y-m-d)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="modelo",
     *         in="query",
     *         description="Nome do modelo para filtrar (ex: Paciente, Triagem)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="evento",
     *         in="query",
     *         description="Tipo de evento: created, updated ou deleted",
     *         required=false,
     *         @OA\Schema(type="string", enum={"created", "updated", "deleted"})
     *     ),
     *     @OA\Parameter(
     *         name="usuario_id",
     *         in="query",
     *         description="ID do usuário que realizou as ações",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Número de registros por página (10-100)",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=10, maximum=100, default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Operação bem-sucedida",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Registros de auditoria obtidos com sucesso"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="atividades", type="object",
     *                     @OA\Property(property="current_page", type="integer"),
     *                     @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *                     @OA\Property(property="total", type="integer"),
     *                     @OA\Property(property="per_page", type="integer")
     *                 ),
     *                 @OA\Property(property="filtros_aplicados", type="object")
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
     *         response=429,
     *         description="Limite de requisições excedido"
     *     )
     * )
     */
    public function index(Request $request)
    {
        // Validação dos parâmetros de filtro
        $request->validate([
            'data_inicio' => 'nullable|date_format:Y-m-d',
            'data_fim' => 'nullable|date_format:Y-m-d',
            'modelo' => 'nullable|string',
            'evento' => 'nullable|string|in:created,updated,deleted',
            'usuario_id' => 'nullable|integer|exists:users,id',
            'per_page' => 'nullable|integer|min:10|max:100',
        ]);
        
        // Consulta base
        $query = Activity::with(['causer'])->latest();
        
        // Aplica filtros
        if ($request->has('data_inicio')) {
            $query->whereDate('created_at', '>=', $request->data_inicio);
        }
        
        if ($request->has('data_fim')) {
            $query->whereDate('created_at', '<=', $request->data_fim);
        }
        
        if ($request->has('modelo')) {
            $modelo = $request->modelo;
            $query->where('subject_type', 'LIKE', "%{$modelo}%");
        }
        
        if ($request->has('evento')) {
            $query->where('event', $request->evento);
        }
        
        if ($request->has('usuario_id')) {
            $query->where('causer_id', $request->usuario_id);
        }
        
        // Paginação
        $perPage = $request->input('per_page', 15);
        $atividades = $query->paginate($perPage);
        
        return $this->successResponse(
            'Registros de auditoria obtidos com sucesso',
            [
                'atividades' => $atividades,
                'filtros_aplicados' => $request->only([
                    'data_inicio', 'data_fim', 'modelo', 'evento', 'usuario_id'
                ])
            ]
        );
    }

    /**
     * Exibe os detalhes de uma atividade específica.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Get(
     *     path="/auditoria/{id}",
     *     summary="Mostra detalhes de uma atividade",
     *     description="Retorna os detalhes de uma atividade de auditoria específica",
     *     operationId="auditLogsShow",
     *     tags={"Auditoria"},
     *     security={"bearerAuth": {}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID da atividade de auditoria",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Operação bem-sucedida",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Detalhes do registro de auditoria obtidos com sucesso"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="atividade", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Atividade não encontrada",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Registro de auditoria não encontrado")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Não autorizado"
     *     )
     * )
     */
    public function show($id)
    {
        $atividade = Activity::with(['causer'])->find($id);
        
        if (!$atividade) {
            return $this->errorResponse('Registro de auditoria não encontrado', [], 404);
        }
        
        return $this->successResponse(
            'Detalhes do registro de auditoria obtidos com sucesso',
            ['atividade' => $atividade]
        );
    }
    
    /**
     * Retorna um resumo das atividades recentes.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Get(
     *     path="/auditoria/resumo",
     *     summary="Resumo de atividades recentes",
     *     description="Retorna um resumo estatístico das atividades recentes",
     *     operationId="auditLogsSummary",
     *     tags={"Auditoria"},
     *     security={"bearerAuth": {}},
     *     @OA\Parameter(
     *         name="dias",
     *         in="query",
     *         description="Número de dias para analisar",
     *         required=false,
     *         @OA\Schema(type="integer", default=30)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Operação bem-sucedida",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Resumo de atividades obtido com sucesso"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="periodo", type="object",
     *                     @OA\Property(property="inicio", type="string", format="date"),
     *                     @OA\Property(property="fim", type="string", format="date")
     *                 ),
     *                 @OA\Property(property="total_atividades", type="integer"),
     *                 @OA\Property(property="por_modelo", type="object"),
     *                 @OA\Property(property="por_evento", type="object"),
     *                 @OA\Property(property="usuarios_mais_ativos", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Não autorizado"
     *     )
     * )
     */
    public function resumo(Request $request)
    {
        // Período (padrão: últimos 30 dias)
        $dias = $request->input('dias', 30);
        $dataInicio = Carbon::now()->subDays($dias);
        
        // Consultas para estatísticas
        $totalAtividades = Activity::where('created_at', '>=', $dataInicio)->count();
        
        $porModelo = Activity::where('created_at', '>=', $dataInicio)
            ->selectRaw('subject_type, count(*) as total')
            ->groupBy('subject_type')
            ->get();
        
        $porEvento = Activity::where('created_at', '>=', $dataInicio)
            ->selectRaw('event, count(*) as total')
            ->groupBy('event')
            ->get();
        
        $atividadesRecentes = Activity::with(['causer'])
            ->latest()
            ->take(10)
            ->get();
        
        return $this->successResponse(
            'Resumo das atividades de auditoria obtido com sucesso',
            [
                'periodo_dias' => $dias,
                'total_atividades' => $totalAtividades,
                'por_modelo' => $porModelo,
                'por_evento' => $porEvento,
                'atividades_recentes' => $atividadesRecentes
            ]
        );
    }
    
    /**
     * Retorna as atividades de um usuário específico.
     *
     * @param  int  $usuarioId
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function porUsuario($usuarioId, Request $request)
    {
        // Validação do usuário
        if (!\App\Models\User::find($usuarioId)) {
            return $this->errorResponse('Usuário não encontrado', [], 404);
        }
        
        // Consulta
        $query = Activity::with(['causer'])
            ->where('causer_id', $usuarioId)
            ->latest();
            
        // Paginação
        $perPage = $request->input('per_page', 15);
        $atividades = $query->paginate($perPage);
        
        return $this->successResponse(
            'Registros de auditoria do usuário obtidos com sucesso',
            ['atividades' => $atividades]
        );
    }
}
