<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\UnidadeSaude;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Controller()
 * @OA\Tag(name="Unidades de Saúde", description="Operações relacionadas às unidades de saúde")
 */
class UnidadeSaudeController extends ApiController
{
    /**
     * Display a listing of the resource.
     * 
     * @OA\Get(
     *     path="/unidades",
     *     summary="Listar todas as unidades de saúde",
     *     description="Retorna uma lista de todas as unidades de saúde cadastradas",
     *     operationId="listarUnidadesSaude",
     *     tags={"Unidades de Saúde"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista recuperada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Lista de unidades de saúde recuperada com sucesso"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="gabinete_provincial_id", type="integer", example=2),
     *                 @OA\Property(property="nome", type="string", example="Hospital Provincial de Luanda"),
     *                 @OA\Property(property="diretor_medico", type="string", example="Dra. Ana Silva"),
     *                 @OA\Property(property="tipo", type="string", example="Hospital_Geral"),
     *                 @OA\Property(property="endereco", type="string", example="Av. Principal, 123"),
     *                 @OA\Property(property="telefone", type="string", example="+244 923456789"),
     *                 @OA\Property(property="email", type="string", example="hospital@saude.gov.ao"),
     *                 @OA\Property(property="latitude", type="number", format="float", example=-8.839),
     *                 @OA\Property(property="longitude", type="number", format="float", example=13.289),
     *                 @OA\Property(property="capacidade", type="integer", example=200),
     *                 @OA\Property(property="tem_isolamento", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro interno do servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erro ao recuperar unidades de saúde: [mensagem de erro]")
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        try {
            $unidades = UnidadeSaude::all();
            
            return $this->successResponse($unidades, 'Lista de unidades de saúde recuperada com sucesso');
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao recuperar unidades de saúde: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * 
     * @OA\Post(
     *     path="/unidades",
     *     summary="Criar nova unidade de saúde",
     *     description="Cria um novo registro de unidade de saúde no sistema",
     *     operationId="criarUnidadeSaude",
     *     tags={"Unidades de Saúde"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"gabinete_provincial_id", "nome", "tipo", "endereco"},
     *             @OA\Property(property="gabinete_provincial_id", type="integer", example=1),
     *             @OA\Property(property="nome", type="string", example="Centro de Saúde Municipal"),
     *             @OA\Property(property="diretor_medico", type="string", example="Dr. Carlos Santos"),
     *             @OA\Property(property="tipo", type="string", example="Centro_Saude", enum={"Hospital_Geral", "Centro_Saude", "Posto_Medico", "Clinica", "Outro"}),
     *             @OA\Property(property="endereco", type="string", example="Rua Principal, 45"),
     *             @OA\Property(property="telefone", type="string", example="+244 923456780"),
     *             @OA\Property(property="email", type="string", example="centro@saude.gov.ao"),
     *             @OA\Property(property="latitude", type="number", format="float", example=-8.8123),
     *             @OA\Property(property="longitude", type="number", format="float", example=13.2345),
     *             @OA\Property(property="capacidade", type="integer", example=50),
     *             @OA\Property(property="tem_isolamento", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Unidade de saúde criada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Unidade de saúde criada com sucesso."),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/UnidadeSaude")
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
     *         response=422,
     *         description="Erro de validação",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erro de validação"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro interno do servidor"
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        // Verificar permissão
        if (!auth()->user()->can('criar unidades-saude')) {
            return $this->errorResponse('Não autorizado', 403);
        }

        // Validar dados
        $validator = Validator::make($request->all(), [
            'gabinete_provincial_id' => 'required|exists:gabinetes_provinciais,id',
            'nome' => 'required|string|max:255',
            'diretor_medico' => 'nullable|string|max:255',
            'tipo' => 'required|in:Hospital_Geral,Centro_Saude,Posto_Medico,Clinica,Outro',
            'endereco' => 'required|string|max:255',
            'telefone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'capacidade' => 'nullable|integer',
            'tem_isolamento' => 'nullable|boolean',
            'capacidade_isolamento' => 'nullable|integer',
            'casos_ativos' => 'nullable|integer',
            'leitos_ocupados' => 'nullable|integer',
            'status' => 'required|in:Ativo,Inativo,Em_Manutencao,Sobrelotado',
            'nivel_alerta' => 'nullable|in:Baixo,Medio,Alto,Critico',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação',
                'error' => $validator->errors()
            ], 422);
        }

        // Salvar e retornar dados
        try {
            DB::beginTransaction();
            
            $unidadeSaude = UnidadeSaude::create($request->all());
            
            DB::commit();
            
            return $this->successResponse($unidadeSaude, 'Unidade de saúde criada com sucesso', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Erro ao criar unidade de saúde: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     * 
     * @OA\Get(
     *     path="/unidades/{id}",
     *     summary="Obter detalhes de unidade de saúde",
     *     description="Retorna os dados de uma unidade de saúde específica pelo ID",
     *     operationId="obterUnidadeSaude",
     *     tags={"Unidades de Saúde"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID da unidade de saúde",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Unidade de saúde encontrada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Unidade de saúde encontrada com sucesso."),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/UnidadeSaude")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unidade de saúde não encontrada",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unidade de saúde não encontrada")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro interno do servidor"
     *     )
     * )
     */
    public function show(string $id): JsonResponse
    {
        try {
            $unidadeSaude = UnidadeSaude::findOrFail($id);

            return $this->successResponse($unidadeSaude, 'Unidade de saúde recuperada com sucesso');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unidade de saúde não encontrada',
                'error' => 'Recurso não encontrado'
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     * 
     * @OA\Put(
     *     path="/unidades/{id}",
     *     summary="Atualizar unidade de saúde",
     *     description="Atualiza os dados de uma unidade de saúde existente",
     *     operationId="atualizarUnidadeSaude",
     *     tags={"Unidades de Saúde"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID da unidade de saúde",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="gabinete_provincial_id", type="integer", example=1),
     *             @OA\Property(property="nome", type="string", example="Centro de Saúde Municipal - Atualizado"),
     *             @OA\Property(property="diretor_medico", type="string", example="Dra. Teresa Gomes"),
     *             @OA\Property(property="tipo", type="string", example="Centro_Saude"),
     *             @OA\Property(property="endereco", type="string", example="Av. Nova, 78"),
     *             @OA\Property(property="telefone", type="string", example="+244 923456790"),
     *             @OA\Property(property="email", type="string", example="centro.novo@saude.gov.ao"),
     *             @OA\Property(property="capacidade", type="integer", example=75),
     *             @OA\Property(property="tem_isolamento", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Unidade de saúde atualizada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Unidade de saúde atualizada com sucesso."),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/UnidadeSaude")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Não autorizado"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unidade de saúde não encontrada"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro interno do servidor"
     *     )
     * )
     */
    public function update(Request $request, string $id): JsonResponse
    {
        // Verificar permissão
        if (!auth()->user()->can('editar unidades-saude')) {
            return $this->errorResponse('Não autorizado', 403);
        }

        // Buscar unidade
        try {
            $unidadeSaude = UnidadeSaude::findOrFail($id);
        } catch (\Exception $e) {
            return $this->errorResponse('Unidade de saúde não encontrada', 404);
        }

        // Validar dados
        $validator = Validator::make($request->all(), [
            'gabinete_provincial_id' => 'exists:gabinetes_provinciais,id',
            'nome' => 'string|max:255',
            'diretor_medico' => 'nullable|string|max:255',
            'tipo' => 'in:Hospital_Geral,Centro_Saude,Posto_Medico,Clinica,Outro',
            'endereco' => 'string|max:255',
            'telefone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'capacidade' => 'nullable|integer',
            'tem_isolamento' => 'nullable|boolean',
            'capacidade_isolamento' => 'nullable|integer',
            'casos_ativos' => 'nullable|integer',
            'leitos_ocupados' => 'nullable|integer',
            'status' => 'in:Ativo,Inativo,Em_Manutencao,Sobrelotado',
            'nivel_alerta' => 'nullable|in:Baixo,Medio,Alto,Critico',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação',
                'error' => $validator->errors()
            ], 422);
        }

        // Atualizar e retornar dados
        try {
            DB::beginTransaction();
            
            $unidadeSaude->update($request->all());
            
            DB::commit();
            
            // Buscar o objeto atualizado para garantir que todos os dados estejam presentes
            $unidadeSaude = UnidadeSaude::findOrFail($id);
            
            return $this->successResponse($unidadeSaude, 'Unidade de saúde atualizada com sucesso');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Erro ao atualizar unidade de saúde: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * 
     * @OA\Delete(
     *     path="/unidades/{id}",
     *     summary="Eliminar unidade de saúde",
     *     description="Remove uma unidade de saúde do sistema",
     *     operationId="eliminarUnidadeSaude",
     *     tags={"Unidades de Saúde"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID da unidade de saúde",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Unidade de saúde eliminada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Unidade de saúde eliminada com sucesso."),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Não autorizado"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Unidade de saúde não encontrada"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro interno do servidor"
     *     )
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        // Verificar permissão
        if (!auth()->user()->can('eliminar unidades-saude')) {
            return $this->errorResponse('Não autorizado', 403);
        }

        // Buscar e eliminar unidade
        try {
            $unidadeSaude = UnidadeSaude::findOrFail($id);
            
            DB::beginTransaction();
            
            $unidadeSaude->delete();
            
            DB::commit();
            
            return $this->successResponse(null, 'Unidade de saúde eliminada com sucesso');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Erro ao eliminar unidade de saúde: ' . $e->getMessage(), 500);
        }
    }
}
