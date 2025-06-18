<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Paciente;
use App\Models\Sintoma;
use App\Models\Triagem;
use App\Services\TriagemService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * @OA\Tag(
 *     name="Triagens",
 *     description="API para gerenciamento de triagens de pacientes"
 * )
 */
class TriagemController extends ApiController
{
    /**
     * Serviço de triagem
     */
    protected $triagemService;
    
    /**
     * Constructor
     */
    public function __construct(TriagemService $triagemService)
    {
        $this->triagemService = $triagemService;
    }
    
    /**
     * Lista triagens com filtros e paginação
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Get(
     *     path="/triagens",
     *     summary="Lista triagens com filtros e paginação",
     *     description="Retorna uma lista de triagens com possibilidade de filtro por status, nível de urgência, etc.",
     *     operationId="listarTriagens",
     *     tags={"Triagens"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filtro por status da triagem",
     *         required=false,
     *         @OA\Schema(type="string", enum={"pendente", "em_andamento", "concluida", "encaminhada"})
     *     ),
     *     @OA\Parameter(
     *         name="nivel_urgencia",
     *         in="query",
     *         description="Filtro por nível de urgência",
     *         required=false,
     *         @OA\Schema(type="string", enum={"baixo", "medio", "alto", "critico"})
     *     ),
     *     @OA\Parameter(
     *         name="unidade_saude_id",
     *         in="query",
     *         description="Filtro por ID da unidade de saúde",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="ponto_cuidado_id",
     *         in="query",
     *         description="Filtro por ID do ponto de cuidado",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="min_probabilidade_colera",
     *         in="query",
     *         description="Filtro por probabilidade mínima de cólera",
     *         required=false,
     *         @OA\Schema(type="number", format="float")
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
     *         description="Lista de triagens recuperada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Triagens obtidas com sucesso"),
     *             @OA\Property(property="data", type="object", 
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="data", type="array", @OA\Items(type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="paciente_id", type="integer"),
     *                     @OA\Property(property="status", type="string"),
     *                     @OA\Property(property="nivel_urgencia", type="string"),
     *                     @OA\Property(property="probabilidade_colera", type="number")
     *                 )),
     *                 @OA\Property(property="total", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Não autorizado"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Triagem::with(['paciente', 'unidadeSaude', 'pontoCuidado'])
                    ->orderBy('created_at', 'desc');
                    
        // Filtrar por status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Filtrar por nível de urgência 
        if ($request->has('nivel_urgencia')) {
            $query->where('nivel_urgencia', $request->nivel_urgencia);
        }
        
        // Filtrar por unidade de saúde
        if ($request->has('unidade_saude_id')) {
            $query->where('unidade_saude_id', $request->unidade_saude_id);
        }
        
        // Filtrar por ponto de cuidado
        if ($request->has('ponto_cuidado_id')) {
            $query->where('ponto_cuidado_id', $request->ponto_cuidado_id);
        }
        
        // Filtrar por probabilidade mínima de cólera
        if ($request->has('min_probabilidade_colera')) {
            $query->where('probabilidade_colera', '>=', $request->min_probabilidade_colera);
        }
        
        $triagens = $query->paginate($request->per_page ?? 15);
        
        return $this->successResponse($triagens, 'Triagens obtidas com sucesso');
    }

    /**
     * Obter triagem por ID
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Get(
     *     path="/triagens/{id}",
     *     summary="Recupera os detalhes de uma triagem específica",
     *     description="Retorna dados detalhados de uma triagem pelo seu ID, incluindo sintomas e relacionamentos",
     *     operationId="obterTriagem",
     *     tags={"Triagens"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID da triagem",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Triagem recuperada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Triagem obtida com sucesso"),
     *             @OA\Property(property="data", type="object", 
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="paciente_id", type="integer"),
     *                 @OA\Property(property="paciente", type="object"),
     *                 @OA\Property(property="status", type="string"),
     *                 @OA\Property(property="sintomas", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="nivel_urgencia", type="string"),
     *                 @OA\Property(property="probabilidade_colera", type="number"),
     *                 @OA\Property(property="recomendacoes", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Triagem não encontrada"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Não autorizado"
     *     )
     * )
     */
    public function show($id)
    {
        $triagem = Triagem::with(['paciente', 'unidadeSaude', 'pontoCuidado', 'responsavel'])
                        ->find($id);
        
        if (!$triagem) {
            return $this->errorResponse('Triagem não encontrada', 404);
        }
        
        return $this->successResponse($triagem, 'Triagem obtida com sucesso');
    }

    /**
     * Criar nova triagem
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Post(
     *     path="/triagens",
     *     summary="Cria uma nova triagem",
     *     description="Registra uma nova triagem com sintomas e avalia risco automático",
     *     operationId="criarTriagem",
     *     tags={"Triagens"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"paciente_id", "sintomas"},
     *             @OA\Property(property="paciente_id", type="integer", example=1),
     *             @OA\Property(property="unidade_saude_id", type="integer", example=2),
     *             @OA\Property(property="ponto_cuidado_id", type="integer", example=3),
     *             @OA\Property(property="responsavel_id", type="integer", example=1),
     *             @OA\Property(property="sintomas", type="array", @OA\Items(type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="intensidade", type="integer", example=3, minimum=1, maximum=5)
     *             )),
     *             @OA\Property(property="indice_desidratacao", type="number", format="float", example=5.5),
     *             @OA\Property(property="temperatura", type="number", format="float", example=38.5),
     *             @OA\Property(property="frequencia_cardiaca", type="integer", example=95),
     *             @OA\Property(property="frequencia_respiratoria", type="integer", example=18),
     *             @OA\Property(property="observacoes", type="string", example="Paciente apresenta sintomas há 2 dias"),
     *             @OA\Property(property="data_inicio_sintomas", type="string", format="date", example="2023-06-10")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Triagem criada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Triagem criada com sucesso"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro ao processar triagem"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'paciente_id' => 'required|exists:pacientes,id',
            'unidade_saude_id' => 'nullable|exists:unidades_saude,id',
            'ponto_cuidado_id' => 'nullable|exists:ponto_cuidados,id',
            'responsavel_id' => 'nullable|exists:users,id',
            'sintomas' => 'required|array',
            'sintomas.*.id' => 'required|exists:sintomas,id',
            'sintomas.*.intensidade' => 'required|integer|min:1|max:5',
            'indice_desidratacao' => 'nullable|numeric|min:0|max:30',
            'temperatura' => 'nullable|numeric|min:34|max:42',
            'frequencia_cardiaca' => 'nullable|integer|min:40|max:220',
            'frequencia_respiratoria' => 'nullable|integer|min:8|max:60',
            'observacoes' => 'nullable|string|max:1000',
            'data_inicio_sintomas' => 'nullable|date',
        ]);
        
        if ($validator->fails()) {
            // Usar o formato padrão do Laravel para validação
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors()
            ], 422);
        }
        
        try {
            $triagem = $this->triagemService->processarTriagem($request->all());
            return $this->successResponse($triagem, 'Triagem criada com sucesso', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao processar triagem', 500, ['message' => $e->getMessage()]);
        }
    }

    /**
     * Atualizar uma triagem existente
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Put(
     *     path="/triagens/{id}",
     *     summary="Atualiza uma triagem",
     *     description="Atualiza os dados de uma triagem existente",
     *     operationId="atualizarTriagem",
     *     tags={"Triagens"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID da triagem",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="unidade_saude_id", type="integer", example=2),
     *             @OA\Property(property="ponto_cuidado_id", type="integer", example=3),
     *             @OA\Property(property="responsavel_id", type="integer", example=1),
     *             @OA\Property(property="sintomas", type="array", @OA\Items(type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="intensidade", type="integer", example=3, minimum=1, maximum=5)
     *             )),
     *             @OA\Property(property="indice_desidratacao", type="number", format="float", example=5.5),
     *             @OA\Property(property="temperatura", type="number", format="float", example=38.5),
     *             @OA\Property(property="frequencia_cardiaca", type="integer", example=95),
     *             @OA\Property(property="frequencia_respiratoria", type="integer", example=18),
     *             @OA\Property(property="status", type="string", example="em_andamento", enum={"pendente", "em_andamento", "concluida", "encaminhada"}),
     *             @OA\Property(property="observacoes", type="string", example="Paciente apresenta melhora nos sintomas"),
     *             @OA\Property(property="data_inicio_sintomas", type="string", format="date", example="2023-06-10"),
     *             @OA\Property(property="data_conclusao", type="string", format="date", example="2023-06-15")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Triagem atualizada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Triagem atualizada com sucesso"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Triagem não encontrada"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $triagem = Triagem::find($id);
        
        if (!$triagem) {
            return $this->errorResponse('Triagem não encontrada', 404);
        }
        
        $validator = Validator::make($request->all(), [
            'unidade_saude_id' => 'nullable|exists:unidades_saude,id',
            'ponto_cuidado_id' => 'nullable|exists:ponto_cuidados,id',
            'responsavel_id' => 'nullable|exists:users,id',
            'sintomas' => 'nullable|array',
            'sintomas.*.id' => 'required|exists:sintomas,id',
            'sintomas.*.intensidade' => 'required|integer|min:1|max:5',
            'indice_desidratacao' => 'nullable|numeric|min:0|max:30',
            'temperatura' => 'nullable|numeric|min:34|max:42',
            'frequencia_cardiaca' => 'nullable|integer|min:40|max:220',
            'frequencia_respiratoria' => 'nullable|integer|min:8|max:60',
            'status' => ['nullable', Rule::in(['pendente', 'em_andamento', 'concluida', 'encaminhada'])],
            'observacoes' => 'nullable|string|max:1000',
            'data_inicio_sintomas' => 'nullable|date',
            'data_conclusao' => 'nullable|date',
        ]);
        
        if ($validator->fails()) {
            return $this->errorResponse('Erro de validação', 422, $validator->errors());
        }
        
        // Atualiza os campos da triagem
        $triagem->fill($request->only([
            'unidade_saude_id', 'ponto_cuidado_id', 'responsavel_id', 
            'sintomas', 'indice_desidratacao', 'temperatura',
            'frequencia_cardiaca', 'frequencia_respiratoria',
            'status', 'observacoes', 'data_inicio_sintomas', 'data_conclusao'
        ]));
        
        // Recalcular probabilidade e recomendações se os sintomas ou índice de desidratação mudaram
        if ($request->has('sintomas') || $request->has('indice_desidratacao')) {
            $triagem = $this->triagemService->atualizarTriagem($triagem);
        } else {
            $triagem->save();
        }
        
        return $this->successResponse($triagem, 'Triagem atualizada com sucesso');
    }

    /**
     * Remover uma triagem
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Delete(
     *     path="/triagens/{id}",
     *     summary="Remove uma triagem",
     *     description="Remove uma triagem do sistema (soft delete)",
     *     operationId="removerTriagem",
     *     tags={"Triagens"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID da triagem",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Triagem removida com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Triagem eliminada com sucesso")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Triagem não encontrada"
     *     )
     * )
     */
    public function destroy($id)
    {
        $triagem = Triagem::find($id);
        
        if (!$triagem) {
            return $this->errorResponse('Triagem não encontrada', 404);
        }
        
        $triagem->delete();
        
        return $this->successResponse(null, 'Triagem eliminada com sucesso');
    }
    
    /**
     * Atualizar o status de uma triagem
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Patch(
     *     path="/triagens/{id}/status",
     *     summary="Atualiza o status de uma triagem",
     *     description="Atualiza apenas o status de uma triagem existente",
     *     operationId="atualizarStatusTriagem",
     *     tags={"Triagens"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID da triagem",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", example="em_andamento", enum={"pendente", "em_andamento", "concluida", "encaminhada"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Status da triagem atualizado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Status da triagem atualizado com sucesso"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Triagem não encontrada"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação"
     *     )
     * )
     */
    public function atualizarStatus(Request $request, $id)
    {
        $triagem = Triagem::find($id);
        
        if (!$triagem) {
            return $this->errorResponse('Triagem não encontrada', 404);
        }
        
        $validator = Validator::make($request->all(), [
            'status' => ['required', Rule::in(['pendente', 'em_andamento', 'concluida', 'encaminhada'])],
        ]);
        
        if ($validator->fails()) {
            return $this->errorResponse('Erro de validação', 422, $validator->errors());
        }
        
        $triagem->status = $request->status;
        
        // Se o status mudou para concluída, adicionamos a data de conclusão
        if ($request->status === 'concluida' && !$triagem->data_conclusao) {
            $triagem->data_conclusao = now();
        }
        
        $triagem->save();
        
        return $this->successResponse($triagem, 'Status da triagem atualizado com sucesso');
    }
    
    /**
     * Registrar um encaminhamento para outra unidade
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Post(
     *     path="/triagens/{id}/encaminhar",
     *     summary="Encaminha uma triagem para outra unidade",
     *     description="Registra encaminhamento de um paciente para outra unidade de saúde",
     *     operationId="encaminharTriagem",
     *     tags={"Triagens"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID da triagem",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"unidade_destino_id", "motivo", "responsavel_id"},
     *             @OA\Property(property="unidade_destino_id", type="integer", example=3),
     *             @OA\Property(property="motivo", type="string", example="Necessidade de tratamento especializado"),
     *             @OA\Property(property="responsavel_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Paciente encaminhado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Paciente encaminhado com sucesso"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Triagem não encontrada"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação"
     *     )
     * )
     */
    public function encaminhar(Request $request, $id)
    {
        $triagem = Triagem::find($id);
        
        if (!$triagem) {
            return $this->errorResponse('Triagem não encontrada', 404);
        }
        
        $validator = Validator::make($request->all(), [
            'unidade_destino_id' => 'required|exists:unidades_saude,id',
            'motivo' => 'required|string|max:255',
            'responsavel_id' => 'required|exists:users,id',
        ]);
        
        if ($validator->fails()) {
            return $this->errorResponse('Erro de validação', 422, $validator->errors());
        }
        
        // Busca informações da unidade de destino
        $unidadeDestino = \App\Models\UnidadeSaude::find($request->unidade_destino_id);
        
        // Cria o registro de encaminhamento
        $encaminhamentos = $triagem->encaminhamentos ?? [];
        $encaminhamentos[] = [
            'data' => now()->format('Y-m-d H:i:s'),
            'unidade_destino_id' => $unidadeDestino->id,
            'unidade_destino_nome' => $unidadeDestino->nome,
            'motivo' => $request->motivo,
            'responsavel_id' => $request->responsavel_id
        ];
        
        // Atualiza a triagem
        $triagem->encaminhamentos = $encaminhamentos;
        $triagem->status = 'encaminhada';
        $triagem->save();
        
        return $this->successResponse($triagem, 'Paciente encaminhado com sucesso');
    }
    
    /**
     * Listar sintomas disponíveis para triagem
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Get(
     *     path="/triagens/sintomas",
     *     summary="Lista sintomas disponíveis para triagem",
     *     description="Retorna lista de sintomas que podem ser utilizados em triagens",
     *     operationId="listarSintomas",
     *     tags={"Triagens"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="especifico_colera",
     *         in="query",
     *         description="Filtrar por sintomas específicos de cólera",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="categoria",
     *         in="query",
     *         description="Filtrar por categoria de sintoma",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="gravidade_min",
     *         in="query",
     *         description="Filtrar por gravidade mínima",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=5)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Sintomas obtidos com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Sintomas obtidos com sucesso"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="nome", type="string"),
     *                 @OA\Property(property="categoria", type="string"),
     *                 @OA\Property(property="gravidade", type="integer"),
     *                 @OA\Property(property="especifico_colera", type="boolean")
     *             ))
     *         )
     *     )
     * )
     */
    public function sintomas(Request $request)
    {
        $query = Sintoma::query();
        
        // Filtros
        if ($request->has('especifico_colera')) {
            $query->where('especifico_colera', (bool)$request->especifico_colera);
        }
        
        if ($request->has('categoria')) {
            $query->where('categoria', $request->categoria);
        }
        
        if ($request->has('gravidade_min')) {
            $query->where('gravidade', '>=', $request->gravidade_min);
        }
        
        $query->orderBy('categoria')->orderBy('gravidade', 'desc');
        
        $sintomas = $query->get();
        
        return $this->successResponse($sintomas, 'Sintomas obtidos com sucesso');
    }
    
    /**
     * Listar triagens críticas (nível de urgência alto ou crítico)
     *
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Get(
     *     path="/triagens/criticas",
     *     summary="Lista triagens críticas",
     *     description="Retorna triagens com nível de urgência alto ou crítico pendentes ou em andamento",
     *     operationId="listarTriagensCriticas",
     *     tags={"Triagens"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Triagens críticas obtidas com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Triagens críticas obtidas com sucesso"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function triagensCriticas()
    {
        $triagens = Triagem::with(['paciente', 'unidadeSaude'])
                        ->whereIn('nivel_urgencia', ['alto', 'critico'])
                        ->whereIn('status', ['pendente', 'em_andamento'])
                        ->orderBy('probabilidade_colera', 'desc')
                        ->get();
        
        return $this->successResponse($triagens, 'Triagens críticas obtidas com sucesso');
    }
    
    /**
     * Listar triagens de um paciente específico
     * 
     * @param int $pacienteId ID do paciente
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Get(
     *     path="/pacientes/{paciente_id}/triagens",
     *     summary="Lista triagens de um paciente",
     *     description="Retorna histórico de triagens de um paciente específico",
     *     operationId="listarTriagensPorPaciente",
     *     tags={"Triagens", "Pacientes"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="paciente_id",
     *         in="path",
     *         description="ID do paciente",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Histórico de triagens obtido com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Histórico de triagens do paciente obtido com sucesso"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="total", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Paciente não encontrado"
     *     )
     * )
     */
    public function triagensPorPaciente($pacienteId)
    {
        // Verificar se o paciente existe
        $paciente = Paciente::find($pacienteId);
        
        if (!$paciente) {
            return $this->errorResponse('Paciente não encontrado', 404);
        }
        
        // Buscar triagens do paciente com paginação
        $triagens = Triagem::where('paciente_id', $pacienteId)
                        ->with(['pontoCuidado', 'unidadeSaude'])
                        ->orderBy('created_at', 'desc')
                        ->paginate(10);
        
        return $this->successResponse(
            $triagens,
            'Histórico de triagens do paciente obtido com sucesso'
        );
    }
}
