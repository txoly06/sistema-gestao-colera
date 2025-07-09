<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Encaminhamento;
use App\Models\Paciente;
use App\Models\Triagem;
use App\Models\UnidadeSaude;
use App\Models\PontoCuidado;
use App\Models\Veiculo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Encaminhamentos",
 *     description="API para gerenciamento de encaminhamentos de pacientes"
 * )
 */
class EncaminhamentoController extends ApiController
{
    /**
     * Lista encaminhamentos com filtros e paginação
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Get(
     *     path="/encaminhamentos",
     *     summary="Lista encaminhamentos com filtros e paginação",
     *     description="Retorna uma lista de encaminhamentos com possibilidade de filtro por status, prioridade, etc.",
     *     operationId="listarEncaminhamentos",
     *     tags={"Encaminhamentos"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filtro por status do encaminhamento",
     *         required=false,
     *         @OA\Schema(type="string", enum={"pendente", "aprovado", "em_transporte", "concluido", "cancelado"})
     *     ),
     *     @OA\Parameter(
     *         name="prioridade",
     *         in="query",
     *         description="Filtro por nível de prioridade",
     *         required=false,
     *         @OA\Schema(type="string", enum={"baixa", "media", "alta", "emergencia"})
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Itens por página na paginação",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de encaminhamentos recuperada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Encaminhamentos obtidos com sucesso"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Encaminhamento::with(['paciente', 'unidadeOrigem', 'unidadeDestino', 'veiculo', 'responsavel'])
                    ->orderBy('created_at', 'desc');
                    
        // Array para rastrear filtros aplicados        
        $filtrosAplicados = [];
        
        // Contagem total antes de aplicar filtros
        $totalSemFiltros = Encaminhamento::count();
        
        // Filtrar por status
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
            $filtrosAplicados['status'] = $request->status;
        }
        
        // Filtrar por prioridade
        if ($request->has('prioridade') && $request->prioridade !== '') {
            $query->where('prioridade', $request->prioridade);
            $filtrosAplicados['prioridade'] = $request->prioridade;
        }
        
        // Filtrar por unidade de origem
        if ($request->has('unidade_origem_id') && $request->unidade_origem_id !== '') {
            $query->where('unidade_origem_id', $request->unidade_origem_id);
            $filtrosAplicados['unidade_origem_id'] = $request->unidade_origem_id;
        }
        
        // Filtrar por unidade de destino
        if ($request->has('unidade_destino_id') && $request->unidade_destino_id !== '') {
            $query->where('unidade_destino_id', $request->unidade_destino_id);
            $filtrosAplicados['unidade_destino_id'] = $request->unidade_destino_id;
        }
        
        // Filtrar por veículo
        if ($request->has('veiculo_id') && $request->veiculo_id !== '') {
            $query->where('veiculo_id', $request->veiculo_id);
            $filtrosAplicados['veiculo_id'] = $request->veiculo_id;
        }
        
        // Verificação de contagem antes da paginação para debugging
        $totalComFiltros = $query->count();
        
        $perPage = $request->per_page ?? 15;
        $encaminhamentos = $query->paginate($perPage);
        
        // Adicionar meta-informações na resposta
        $metaInfo = [
            'filtros_aplicados' => $filtrosAplicados,
            'total_registros_sem_filtro' => $totalSemFiltros,
            'total_com_filtros' => $totalComFiltros
        ];
        
        // Anexar metainfo à resposta
        $response = $encaminhamentos->toArray();
        $response['meta_info'] = $metaInfo;
        
        return $this->successResponse($response, 'Encaminhamentos obtidos com sucesso' . 
            (empty($filtrosAplicados) ? '' : ' - Filtros aplicados: ' . count($filtrosAplicados)));
    }

    /**
     * Obter encaminhamento por ID
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Get(
     *     path="/encaminhamentos/{id}",
     *     summary="Recupera os detalhes de um encaminhamento específico",
     *     description="Retorna dados detalhados de um encaminhamento pelo seu ID",
     *     operationId="obterEncaminhamento",
     *     tags={"Encaminhamentos"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do encaminhamento",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Encaminhamento recuperado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Encaminhamento obtido com sucesso"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Encaminhamento não encontrado"
     *     )
     * )
     */
    public function show($id)
    {
        $encaminhamento = Encaminhamento::with([
                'paciente', 'triagem', 'unidadeOrigem', 'unidadeDestino', 
                'pontoCuidadoOrigem', 'pontoCuidadoDestino', 'veiculo', 'responsavel'
            ])->find($id);
        
        if (!$encaminhamento) {
            return $this->errorResponse('Encaminhamento não encontrado', 404);
        }
        
        return $this->successResponse($encaminhamento, 'Encaminhamento obtido com sucesso');
    }

    /**
     * Criar novo encaminhamento
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Post(
     *     path="/encaminhamentos",
     *     summary="Cria um novo encaminhamento",
     *     description="Registra um novo encaminhamento de paciente",
     *     operationId="criarEncaminhamento",
     *     tags={"Encaminhamentos"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"paciente_id", "motivo"},
     *             @OA\Property(property="paciente_id", type="integer", example=1),
     *             @OA\Property(property="triagem_id", type="integer", example=5),
     *             @OA\Property(property="unidade_origem_id", type="integer", example=2),
     *             @OA\Property(property="unidade_destino_id", type="integer", example=3),
     *             @OA\Property(property="ponto_cuidado_origem_id", type="integer", example=null),
     *             @OA\Property(property="ponto_cuidado_destino_id", type="integer", example=4),
     *             @OA\Property(property="responsavel_id", type="integer", example=1),
     *             @OA\Property(property="prioridade", type="string", example="alta"),
     *             @OA\Property(property="motivo", type="string", example="Necessidade de atendimento especializado"),
     *             @OA\Property(property="observacoes", type="string", example="Paciente com desidratação severa"),
     *             @OA\Property(property="recursos_necessarios", type="object", example={"oxigenio": true, "equipamento_especial": "ventilador"}),
     *             @OA\Property(property="previsao_partida", type="string", format="date-time", example="2025-06-28T13:30:00")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Encaminhamento criado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Encaminhamento criado com sucesso"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'paciente_id' => 'required|exists:pacientes,id',
            'triagem_id' => 'nullable|exists:triagens,id',
            'unidade_origem_id' => 'nullable|exists:unidades_saude,id',
            'unidade_destino_id' => 'nullable|exists:unidades_saude,id',
            'ponto_cuidado_origem_id' => 'nullable|exists:ponto_cuidados,id',
            'ponto_cuidado_destino_id' => 'nullable|exists:ponto_cuidados,id',
            'responsavel_id' => 'nullable|exists:users,id',
            'prioridade' => ['nullable', Rule::in(['baixa', 'media', 'alta', 'emergencia'])],
            'motivo' => 'required|string|max:1000',
            'observacoes' => 'nullable|string|max:1000',
            'recursos_necessarios' => 'nullable|array',
            'previsao_partida' => 'nullable|date',
            'previsao_chegada' => 'nullable|date'
        ]);
        
        if ($validator->fails()) {
            return $this->errorResponse('Erro de validação', 422, $validator->errors());
        }

        // Determinar o tipo de encaminhamento
        $tipoEncaminhamento = $this->determinarTipoEncaminhamento(
            $request->unidade_origem_id, 
            $request->unidade_destino_id, 
            $request->ponto_cuidado_origem_id, 
            $request->ponto_cuidado_destino_id
        );
        
        // Criar encaminhamento
        $encaminhamento = new Encaminhamento($request->all());
        $encaminhamento->status = 'pendente';
        $encaminhamento->prioridade = $request->prioridade ?? 'media';
        $encaminhamento->tipo_encaminhamento = $tipoEncaminhamento;
        $encaminhamento->data_solicitacao = now();
        $encaminhamento->save();
        
        // Se há uma triagem associada, atualizá-la para o status 'encaminhada'
        if ($request->triagem_id) {
            $triagem = Triagem::find($request->triagem_id);
            if ($triagem) {
                $triagem->status = 'encaminhada';
                $triagem->save();
            }
        }
        
        return $this->successResponse(
            $encaminhamento->load(['paciente', 'unidadeOrigem', 'unidadeDestino', 'pontoCuidadoOrigem', 'pontoCuidadoDestino']), 
            'Encaminhamento criado com sucesso', 
            201
        );
    }

    /**
     * Determinar o tipo de encaminhamento com base nas origens e destinos
     */
    private function determinarTipoEncaminhamento($unidadeOrigemId, $unidadeDestinoId, $pontoCuidadoOrigemId, $pontoCuidadoDestinoId)
    {
        if ($unidadeOrigemId && $unidadeDestinoId) {
            return 'unidade_para_unidade';
        } elseif ($unidadeOrigemId && $pontoCuidadoDestinoId) {
            return 'unidade_para_ponto';
        } elseif ($pontoCuidadoOrigemId && $unidadeDestinoId) {
            return 'ponto_para_unidade';
        } elseif ($pontoCuidadoOrigemId && $pontoCuidadoDestinoId) {
            return 'ponto_para_ponto';
        }
        
        return null;
    }
    
    /**
     * Atualiza um encaminhamento existente
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Put(
     *     path="/encaminhamentos/{id}",
     *     summary="Atualiza um encaminhamento existente",
     *     description="Atualiza dados de um encaminhamento pelo seu ID",
     *     operationId="atualizarEncaminhamento",
     *     tags={"Encaminhamentos"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do encaminhamento",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="unidade_destino_id", type="integer", example=3),
     *             @OA\Property(property="ponto_cuidado_destino_id", type="integer", example=null),
     *             @OA\Property(property="prioridade", type="string", example="alta"),
     *             @OA\Property(property="motivo", type="string", example="Necessidade de atendimento especializado atualizado"),
     *             @OA\Property(property="observacoes", type="string", example="Paciente com desidratação severa e febre alta"),
     *             @OA\Property(property="recursos_necessarios", type="object", example={"oxigenio": true}),
     *             @OA\Property(property="previsao_partida", type="string", format="date-time", example="2025-06-28T14:00:00"),
     *             @OA\Property(property="previsao_chegada", type="string", format="date-time", example="2025-06-28T15:30:00")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Encaminhamento atualizado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Encaminhamento atualizado com sucesso"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Encaminhamento não encontrado"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $encaminhamento = Encaminhamento::find($id);
        
        if (!$encaminhamento) {
            return $this->errorResponse('Encaminhamento não encontrado', 404);
        }
        
        // Verificar se o encaminhamento já foi concluído ou cancelado
        if (in_array($encaminhamento->status, ['concluido', 'cancelado'])) {
            return $this->errorResponse(
                'Não é possível atualizar um encaminhamento que já foi ' . 
                ($encaminhamento->status == 'concluido' ? 'concluído' : 'cancelado'), 
                422
            );
        }
        
        $validator = Validator::make($request->all(), [
            'unidade_destino_id' => 'nullable|exists:unidades_saude,id',
            'ponto_cuidado_destino_id' => 'nullable|exists:ponto_cuidados,id',
            'prioridade' => ['nullable', Rule::in(['baixa', 'media', 'alta', 'emergencia'])],
            'motivo' => 'nullable|string|max:1000',
            'observacoes' => 'nullable|string|max:1000',
            'recursos_necessarios' => 'nullable|array',
            'previsao_partida' => 'nullable|date',
            'previsao_chegada' => 'nullable|date'
        ]);
        
        if ($validator->fails()) {
            return $this->errorResponse('Erro de validação', 422, $validator->errors());
        }
        
        // Atualizar o tipo de encaminhamento se destinos forem alterados
        if ($request->has('unidade_destino_id') || $request->has('ponto_cuidado_destino_id')) {
            $tipoEncaminhamento = $this->determinarTipoEncaminhamento(
                $encaminhamento->unidade_origem_id,
                $request->unidade_destino_id ?? $encaminhamento->unidade_destino_id,
                $encaminhamento->ponto_cuidado_origem_id,
                $request->ponto_cuidado_destino_id ?? $encaminhamento->ponto_cuidado_destino_id
            );
            $encaminhamento->tipo_encaminhamento = $tipoEncaminhamento;
        }
        
        $encaminhamento->update($request->all());
        
        return $this->successResponse(
            $encaminhamento->load(['paciente', 'unidadeOrigem', 'unidadeDestino', 'pontoCuidadoOrigem', 'pontoCuidadoDestino']),
            'Encaminhamento atualizado com sucesso'
        );        
    }
    
    /**
     * Exclui um encaminhamento
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Delete(
     *     path="/encaminhamentos/{id}",
     *     summary="Remove um encaminhamento existente",
     *     description="Realiza a exclusão lógica (soft delete) de um encaminhamento",
     *     operationId="removerEncaminhamento",
     *     tags={"Encaminhamentos"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do encaminhamento",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Encaminhamento removido com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Encaminhamento removido com sucesso")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Encaminhamento não encontrado"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Falha ao remover encaminhamento"
     *     )
     * )
     */
    public function destroy($id)
    {
        $encaminhamento = Encaminhamento::find($id);
        
        if (!$encaminhamento) {
            return $this->errorResponse('Encaminhamento não encontrado', 404);
        }
        
        // Verificar se o encaminhamento está em andamento
        if ($encaminhamento->status == 'em_transporte') {
            return $this->errorResponse(
                'Não é possível remover um encaminhamento que já está em transporte',
                422
            );
        }
        
        // Se o encaminhamento está associado a uma triagem, atualizar o status da triagem
        if ($encaminhamento->triagem_id) {
            $triagem = Triagem::find($encaminhamento->triagem_id);
            if ($triagem && $triagem->status == 'encaminhada') {
                $triagem->status = 'concluida';
                $triagem->save();
            }
        }
        
        try {
            $encaminhamento->delete();
            return $this->successResponse(null, 'Encaminhamento removido com sucesso');
        } catch (\Exception $e) {
            return $this->errorResponse('Falha ao remover encaminhamento: ' . $e->getMessage(), 422);
        }
    }
    
    /**
     * Listar encaminhamentos pendentes ou aprovados
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Get(
     *     path="/encaminhamentos/pendentes",
     *     summary="Listar encaminhamentos pendentes",
     *     description="Retorna lista de encaminhamentos com status pendente ou aprovado",
     *     operationId="encaminhamentosPendentes",
     *     tags={"Encaminhamentos"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="prioridade",
     *         in="query",
     *         description="Filtro por prioridade",
     *         required=false,
     *         @OA\Schema(type="string", enum={"baixa", "media", "alta", "emergencia"})
     *     ),
     *     @OA\Parameter(
     *         name="unidade_destino_id",
     *         in="query",
     *         description="Filtro por unidade destino",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Itens por página",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de encaminhamentos pendentes obtida com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Encaminhamentos pendentes obtidos com sucesso"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function pendentes(Request $request)
    {
        $query = Encaminhamento::with(['paciente', 'unidadeOrigem', 'unidadeDestino', 'veiculo'])
                    ->whereIn('status', ['pendente', 'aprovado'])
                    ->orderBy('prioridade', 'desc')
                    ->orderBy('data_solicitacao', 'asc');
                    
        // Array para rastrear filtros aplicados        
        $filtrosAplicados = [];
        
        // Filtrar por prioridade
        if ($request->has('prioridade') && $request->prioridade !== '') {
            $query->where('prioridade', $request->prioridade);
            $filtrosAplicados['prioridade'] = $request->prioridade;
        }
        
        // Filtrar por unidade de destino
        if ($request->has('unidade_destino_id') && $request->unidade_destino_id !== '') {
            $query->where('unidade_destino_id', $request->unidade_destino_id);
            $filtrosAplicados['unidade_destino_id'] = $request->unidade_destino_id;
        }
        
        // Contagem de pendentes antes da paginação
        $totalPendentes = $query->count();
        
        $perPage = $request->per_page ?? 15;
        $encaminhamentos = $query->paginate($perPage);
        
        // Adicionar meta-informações na resposta
        $metaInfo = [
            'filtros_aplicados' => $filtrosAplicados,
            'total_pendentes' => $totalPendentes,
            'total_em_emergencia' => Encaminhamento::whereIn('status', ['pendente', 'aprovado'])
                                        ->where('prioridade', 'emergencia')
                                        ->count()
        ];
        
        // Anexar metainfo à resposta
        $response = $encaminhamentos->toArray();
        $response['meta_info'] = $metaInfo;
        
        return $this->successResponse($response, 'Encaminhamentos pendentes obtidos com sucesso');
    }
    
    /**
     * Atualizar o status de um encaminhamento
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Put(
     *     path="/encaminhamentos/{id}/status",
     *     summary="Atualiza o status de um encaminhamento",
     *     description="Permite mudar o status do encaminhamento (pendente, aprovado, em_transporte, concluido, cancelado)",
     *     operationId="atualizarStatusEncaminhamento",
     *     tags={"Encaminhamentos"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do encaminhamento",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", example="aprovado", enum={"pendente", "aprovado", "em_transporte", "concluido", "cancelado"}),
     *             @OA\Property(property="observacao", type="string", example="Aprovado para transporte imediato"),
     *             @OA\Property(property="data_inicio_transporte", type="string", format="date-time"),
     *             @OA\Property(property="data_chegada", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Status do encaminhamento atualizado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Status do encaminhamento atualizado com sucesso"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Encaminhamento não encontrado"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação"
     *     )
     * )
     */
    public function atualizarStatus(Request $request, $id)
    {
        $encaminhamento = Encaminhamento::find($id);
        
        if (!$encaminhamento) {
            return $this->errorResponse('Encaminhamento não encontrado', 404);
        }
        
        $validator = Validator::make($request->all(), [
            'status' => ['required', Rule::in(['pendente', 'aprovado', 'em_transporte', 'concluido', 'cancelado'])],
            'observacao' => 'nullable|string|max:500',
            'data_inicio_transporte' => 'nullable|date',
            'data_chegada' => 'nullable|date'
        ]);
        
        if ($validator->fails()) {
            return $this->errorResponse('Erro de validação', 422, $validator->errors());
        }
        
        // Verificar transições válidas de status
        $statusAtual = $encaminhamento->status;
        $novoStatus = $request->status;
        
        $transicoesValidas = [
            'pendente' => ['aprovado', 'cancelado'],
            'aprovado' => ['em_transporte', 'cancelado'],
            'em_transporte' => ['concluido', 'cancelado'],
            'concluido' => [],
            'cancelado' => []
        ];
        
        if ($statusAtual === $novoStatus) {
            // Status não mudou, apenas atualizar outras informações
        } else if (!in_array($novoStatus, $transicoesValidas[$statusAtual])) {
            return $this->errorResponse(
                "Transição inválida de status: de '$statusAtual' para '$novoStatus'",
                422
            );
        }
        
        // Atualizar o status
        $encaminhamento->status = $novoStatus;
        
        // Atualizar campos relacionados ao status
        if ($request->has('observacao')) {
            $encaminhamento->observacoes = $request->observacao . "\n" . 
                "[" . now()->format('Y-m-d H:i') . "] Status alterado para: $novoStatus" . 
                ($encaminhamento->observacoes ? "\n" . $encaminhamento->observacoes : "");
        }
        
        if ($novoStatus == 'em_transporte' && $request->has('data_inicio_transporte')) {
            $encaminhamento->data_inicio_transporte = $request->data_inicio_transporte ?? now();
        }
        
        if ($novoStatus == 'concluido' && $request->has('data_chegada')) {
            $encaminhamento->data_chegada = $request->data_chegada ?? now();
        }
        
        $encaminhamento->save();
        
        // Se o status for concluido ou cancelado e houver uma triagem associada, atualizar o status da triagem
        if (in_array($novoStatus, ['concluido', 'cancelado']) && $encaminhamento->triagem_id) {
            $triagem = Triagem::find($encaminhamento->triagem_id);
            if ($triagem) {
                $triagem->status = ($novoStatus == 'concluido') ? 'concluida' : 'em_atendimento';
                $triagem->save();
            }
        }
        
        return $this->successResponse(
            $encaminhamento->load(['paciente', 'unidadeOrigem', 'unidadeDestino', 'veiculo']),
            'Status do encaminhamento atualizado com sucesso'
        );
    }
    
    /**
     * Atribuir veículo a um encaminhamento
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Put(
     *     path="/encaminhamentos/{id}/atribuir-veiculo",
     *     summary="Atribui um veículo a um encaminhamento",
     *     description="Permite associar um veículo disponível ao transporte do paciente",
     *     operationId="atribuirVeiculoEncaminhamento",
     *     tags={"Encaminhamentos"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do encaminhamento",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"veiculo_id"},
     *             @OA\Property(property="veiculo_id", type="integer", example=5),
     *             @OA\Property(property="previsao_partida", type="string", format="date-time"),
     *             @OA\Property(property="previsao_chegada", type="string", format="date-time"),
     *             @OA\Property(property="observacao", type="string", example="Veículo designado com equipamento de emergência")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Veículo atribuído com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Veículo atribuído ao encaminhamento com sucesso"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Encaminhamento ou veículo não encontrado"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação ou veículo indisponível"
     *     )
     * )
     */
    public function atribuirVeiculo(Request $request, $id)
    {
        $encaminhamento = Encaminhamento::find($id);
        
        if (!$encaminhamento) {
            return $this->errorResponse('Encaminhamento não encontrado', 404);
        }
        
        // Verificar se o encaminhamento está em um estado válido para atribuição de veículo
        if (!in_array($encaminhamento->status, ['pendente', 'aprovado'])) {
            return $this->errorResponse(
                'Somente encaminhamentos pendentes ou aprovados podem receber atribuição de veículo',
                422
            );
        }
        
        $validator = Validator::make($request->all(), [
            'veiculo_id' => 'required|exists:veiculos,id',
            'previsao_partida' => 'nullable|date',
            'previsao_chegada' => 'nullable|date',
            'observacao' => 'nullable|string|max:500'
        ]);
        
        if ($validator->fails()) {
            return $this->errorResponse('Erro de validação', 422, $validator->errors());
        }
        
        // Verificar se o veículo existe e está disponível
        $veiculo = Veiculo::find($request->veiculo_id);
        
        if (!$veiculo) {
            return $this->errorResponse('Veículo não encontrado', 404);
        }
        
        if ($veiculo->status !== 'disponivel') {
            return $this->errorResponse(
                'O veículo selecionado não está disponível. Status atual: ' . $veiculo->status,
                422
            );
        }
        
        // Atribuir veículo
        $encaminhamento->veiculo_id = $veiculo->id;
        
        // Atualizar previsões
        if ($request->has('previsao_partida')) {
            $encaminhamento->previsao_partida = $request->previsao_partida;
        }
        
        if ($request->has('previsao_chegada')) {
            $encaminhamento->previsao_chegada = $request->previsao_chegada;
        }
        
        // Atualizar observações
        if ($request->has('observacao')) {
            $encaminhamento->observacoes = $request->observacao . "\n" . 
                "[" . now()->format('Y-m-d H:i') . "] Veículo #{$veiculo->id} ({$veiculo->placa}) atribuído" . 
                ($encaminhamento->observacoes ? "\n" . $encaminhamento->observacoes : "");
        }
        
        // Se o encaminhamento estava pendente, atualizá-lo para aprovado
        if ($encaminhamento->status === 'pendente') {
            $encaminhamento->status = 'aprovado';
        }
        
        $encaminhamento->save();
        
        // Atualizar status do veículo para 'em_transito'
        $veiculo->status = 'em_transito';
        $veiculo->save();
        
        return $this->successResponse(
            $encaminhamento->load(['paciente', 'unidadeOrigem', 'unidadeDestino', 'veiculo']),
            'Veículo atribuído ao encaminhamento com sucesso'
        );
    }
}
