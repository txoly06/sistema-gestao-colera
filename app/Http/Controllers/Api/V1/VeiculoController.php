<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Veiculo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Veículos",
 *     description="API para gerenciamento de veículos do sistema"
 * )
 */
class VeiculoController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/veiculos",
     *     summary="Lista todos os veículos",
     *     description="Retorna uma lista de todos os veículos cadastrados no sistema",
     *     operationId="listaVeiculos",
     *     tags={"Veículos"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista recuperada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Lista de veículos recuperada com sucesso"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="placa", type="string", example="LAD-1234"),
     *                 @OA\Property(property="modelo", type="string", example="Toyota Hilux"),
     *                 @OA\Property(property="status", type="string", example="disponivel"),
     *                 @OA\Property(property="tipo", type="string", example="ambulancia")
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Não autorizado"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro do servidor"
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        // Verificar permissão
        if (!auth()->user()->can('ver veiculos')) {
            return $this->errorResponse('Não autorizado', 403);
        }
        
        try {
            $veiculos = Veiculo::all();

            return $this->successResponse($veiculos, 'Lista de veículos recuperada com sucesso');
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao recuperar veículos: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @OA\Post(
     *     path="/veiculos",
     *     summary="Cadastra um novo veículo",
     *     description="Cria um novo registro de veículo no sistema",
     *     operationId="criarVeiculo",
     *     tags={"Veículos"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"placa", "modelo", "ano", "tipo", "status", "capacidade_pacientes"},
     *             @OA\Property(property="placa", type="string", example="LAD-1234"),
     *             @OA\Property(property="modelo", type="string", example="Toyota Hilux"),
     *             @OA\Property(property="ano", type="integer", example=2023),
     *             @OA\Property(property="tipo", type="string", example="ambulancia", enum={"ambulancia", "transporte", "apoio"}),
     *             @OA\Property(property="status", type="string", example="disponivel", enum={"disponivel", "em_transito", "em_manutencao", "indisponivel"}),
     *             @OA\Property(property="descricao", type="string", example="Ambulância UTI móvel com equipamentos completos"),
     *             @OA\Property(property="capacidade_pacientes", type="integer", example=2),
     *             @OA\Property(property="latitude", type="number", format="float", example=-8.838333),
     *             @OA\Property(property="longitude", type="number", format="float", example=13.234444),
     *             @OA\Property(property="equipamentos", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="equipe_medica", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="tem_gps", type="boolean", example=true),
     *             @OA\Property(property="nivel_combustivel", type="integer", example=80),
     *             @OA\Property(property="ponto_cuidado_id", type="integer", example=1),
     *             @OA\Property(property="unidade_saude_id", type="integer", example=2),
     *             @OA\Property(property="responsavel", type="string", example="João Silva"),
     *             @OA\Property(property="contato_responsavel", type="string", example="+244 923456789")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Veículo criado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Veículo criado com sucesso"),
     *             @OA\Property(property="data", type="object")
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
     *         description="Erro do servidor"
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        // Verificar permissão
        if (!auth()->user()->can('criar veiculos')) {
            return $this->errorResponse('Não autorizado', 403);
        }

        // Validar dados
        $validator = Validator::make($request->all(), [
            'placa' => 'required|string|max:10|unique:veiculos,placa',
            'modelo' => 'required|string|max:100',
            'ano' => 'required|integer|min:2000',
            'tipo' => 'required|string|in:ambulancia,transporte,apoio',
            'status' => 'required|string|in:disponivel,em_transito,em_manutencao,indisponivel',
            'descricao' => 'nullable|string|max:255',
            'capacidade_pacientes' => 'required|integer|min:1',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'equipamentos' => 'nullable|array',
            'equipe_medica' => 'nullable|array',
            'tem_gps' => 'nullable|boolean',
            'nivel_combustivel' => 'nullable|integer|min:0|max:100',
            'ponto_cuidado_id' => 'nullable|exists:ponto_cuidados,id',
            'unidade_saude_id' => 'nullable|exists:unidades_saude,id',
            'responsavel' => 'nullable|string|max:100',
            'contato_responsavel' => 'nullable|string|max:20'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 422);
        }

        try {
            $veiculo = Veiculo::create($request->all());

            return $this->successResponse($veiculo, 'Veículo criado com sucesso', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao criar veículo: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @OA\Get(
     *     path="/veiculos/{id}",
     *     summary="Exibe detalhes de um veículo específico",
     *     description="Retorna os dados completos de um veículo cadastrado",
     *     operationId="mostrarVeiculo",
     *     tags={"Veículos"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do veículo",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Dados recuperados com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Veículo recuperado com sucesso"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="placa", type="string"),
     *                 @OA\Property(property="modelo", type="string"),
     *                 @OA\Property(property="ano", type="integer"),
     *                 @OA\Property(property="tipo", type="string"),
     *                 @OA\Property(property="status", type="string"),
     *                 @OA\Property(property="descricao", type="string"),
     *                 @OA\Property(property="capacidade_pacientes", type="integer"),
     *                 @OA\Property(property="latitude", type="number", format="float"),
     *                 @OA\Property(property="longitude", type="number", format="float"),
     *                 @OA\Property(property="equipamentos", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="equipe_medica", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="tem_gps", type="boolean"),
     *                 @OA\Property(property="nivel_combustivel", type="integer"),
     *                 @OA\Property(property="ponto_cuidado_id", type="integer"),
     *                 @OA\Property(property="unidade_saude_id", type="integer"),
     *                 @OA\Property(property="responsavel", type="string"),
     *                 @OA\Property(property="contato_responsavel", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Veículo não encontrado"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Não autorizado"
     *     )
     * )
     */
    public function show($id): JsonResponse
    {
        // Convertendo ID para inteiro
        $id = (int) $id;
        // Verificar permissão
        if (!auth()->user()->can('ver veiculos')) {
            return $this->errorResponse('Não autorizado', 403);
        }

        try {
            $veiculo = Veiculo::findOrFail($id);

            return $this->successResponse($veiculo, 'Veículo recuperado com sucesso');
        } catch (\Exception $e) {
            return $this->errorResponse('Veículo não encontrado', 404);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @OA\Put(
     *     path="/veiculos/{id}",
     *     summary="Atualiza dados de um veículo",
     *     description="Atualiza as informações de um veículo cadastrado",
     *     operationId="atualizarVeiculo",
     *     tags={"Veículos"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do veículo",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="placa", type="string", example="LAD-1234"),
     *             @OA\Property(property="modelo", type="string", example="Toyota Hilux"),
     *             @OA\Property(property="ano", type="integer", example=2023),
     *             @OA\Property(property="tipo", type="string", example="ambulancia", enum={"ambulancia", "transporte", "apoio"}),
     *             @OA\Property(property="status", type="string", example="disponivel", enum={"disponivel", "em_transito", "em_manutencao", "indisponivel"}),
     *             @OA\Property(property="descricao", type="string", example="Ambulância UTI móvel com equipamentos completos"),
     *             @OA\Property(property="capacidade_pacientes", type="integer", example=2),
     *             @OA\Property(property="latitude", type="number", format="float", example=-8.838333),
     *             @OA\Property(property="longitude", type="number", format="float", example=13.234444),
     *             @OA\Property(property="equipamentos", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="equipe_medica", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="tem_gps", type="boolean", example=true),
     *             @OA\Property(property="nivel_combustivel", type="integer", example=80),
     *             @OA\Property(property="ponto_cuidado_id", type="integer", example=1),
     *             @OA\Property(property="unidade_saude_id", type="integer", example=2),
     *             @OA\Property(property="responsavel", type="string", example="João Silva"),
     *             @OA\Property(property="contato_responsavel", type="string", example="+244 923456789")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Veículo atualizado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Veículo atualizado com sucesso"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Veículo não encontrado"
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
     *         description="Erro do servidor"
     *     )
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        // Verificar permissão
        if (!auth()->user()->can('editar veiculos')) {
            return $this->errorResponse('Não autorizado', 403);
        }

        // Validar dados
        $validator = Validator::make($request->all(), [
            'placa' => 'sometimes|required|string|max:10|unique:veiculos,placa,' . $id,
            'modelo' => 'sometimes|required|string|max:100',
            'ano' => 'sometimes|required|integer|min:2000',
            'tipo' => 'sometimes|required|string|in:ambulancia,transporte,apoio',
            'status' => 'sometimes|required|string|in:disponivel,em_transito,em_manutencao,indisponivel',
            'descricao' => 'nullable|string|max:255',
            'capacidade_pacientes' => 'sometimes|required|integer|min:1',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'equipamentos' => 'nullable|array',
            'equipe_medica' => 'nullable|array',
            'tem_gps' => 'nullable|boolean',
            'nivel_combustivel' => 'nullable|integer|min:0|max:100',
            'ponto_cuidado_id' => 'nullable|exists:ponto_cuidados,id',
            'unidade_saude_id' => 'nullable|exists:unidades_saude,id',
            'responsavel' => 'nullable|string|max:100',
            'contato_responsavel' => 'nullable|string|max:20'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 422);
        }

        try {
            $veiculo = Veiculo::findOrFail($id);
            $veiculo->update($request->all());

            return $this->successResponse($veiculo, 'Veículo atualizado com sucesso');
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao atualizar veículo: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @OA\Delete(
     *     path="/veiculos/{id}",
     *     summary="Remove um veículo",
     *     description="Exclui um veículo do sistema (soft delete)",
     *     operationId="excluirVeiculo",
     *     tags={"Veículos"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do veículo",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Veículo excluído com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Veículo eliminado com sucesso")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Veículo não encontrado"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Não autorizado"
     *     )
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        // Verificar permissão
        if (!auth()->user()->can('eliminar veiculos')) {
            return $this->errorResponse('Não autorizado', 403);
        }

        try {
            $veiculo = Veiculo::findOrFail($id);
            $veiculo->delete();

            return $this->successResponse(['id' => $id], 'Veículo eliminado com sucesso');
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao eliminar veículo: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Atualiza o status do veículo.
     *
     * @OA\Patch(
     *     path="/veiculos/{id}/status",
     *     summary="Atualiza o status de um veículo",
     *     description="Atualiza apenas o status de um veículo cadastrado",
     *     operationId="atualizarStatusVeiculo",
     *     tags={"Veículos"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do veículo",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", example="em_transito", enum={"disponivel", "em_transito", "em_manutencao", "indisponivel"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Status do veículo atualizado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Status do veículo atualizado com sucesso"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Veículo não encontrado"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Não autorizado"
     *     )
     * )
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        // Verificar permissão
        if (!auth()->user()->can('editar veiculos')) {
            return $this->errorResponse('Não autorizado', 403);
        }

        // Validar dados
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:disponivel,em_transito,em_manutencao,indisponivel',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 422);
        }

        try {
            $veiculo = Veiculo::findOrFail($id);
            $veiculo->atualizarStatus($request->status);

            return $this->successResponse($veiculo, 'Status do veículo atualizado com sucesso');
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse('Status inválido: ' . $e->getMessage(), 422);
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao atualizar status do veículo: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Atualiza a localização do veículo.
     *
     * @OA\Put(
     *     path="/veiculos/{id}/localizacao",
     *     summary="Atualiza a localização de um veículo",
     *     description="Atualiza apenas as coordenadas geográficas de um veículo cadastrado",
     *     operationId="atualizarLocalizacaoVeiculo",
     *     tags={"Veículos"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do veículo",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"latitude", "longitude"},
     *             @OA\Property(property="latitude", type="number", format="float", example=-8.838333),
     *             @OA\Property(property="longitude", type="number", format="float", example=13.234444)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Localização do veículo atualizada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Localização do veículo atualizada com sucesso"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Veículo não encontrado"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Não autorizado"
     *     )
     * )
     */
    public function updateLocalizacao(Request $request, int $id): JsonResponse
    {
        // Verificar permissão
        if (!auth()->user()->can('editar veiculos')) {
            return $this->errorResponse('Não autorizado', 403);
        }

        // Validar dados
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 422);
        }

        try {
            $veiculo = Veiculo::findOrFail($id);
            $veiculo->atualizarLocalizacao($request->latitude, $request->longitude);

            return $this->successResponse($veiculo, 'Localização do veículo atualizada com sucesso');
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao atualizar localização do veículo: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Atualiza o nível de combustível do veículo.
     * 
     * @OA\Patch(
     *     path="/veiculos/{id}/combustivel",
     *     summary="Atualiza o nível de combustível de um veículo",
     *     description="Atualiza apenas o nível de combustível de um veículo cadastrado",
     *     operationId="atualizarCombustivelVeiculo",
     *     tags={"Veículos"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do veículo",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nivel_combustivel"},
     *             @OA\Property(property="nivel_combustivel", type="integer", example=80, minimum=0, maximum=100)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Nível de combustível atualizado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Nível de combustível atualizado com sucesso"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Veículo não encontrado"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Não autorizado"
     *     )
     * )
     */
    public function updateCombustivel(Request $request, int $id): JsonResponse
    {
        // Verificar permissão
        if (!auth()->user()->can('editar veiculos')) {
            return $this->errorResponse('Não autorizado', 403);
        }

        // Validar dados
        $validator = Validator::make($request->all(), [
            'nivel_combustivel' => 'required|integer|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 422);
        }

        try {
            $veiculo = Veiculo::findOrFail($id);
            $veiculo->nivel_combustivel = $request->nivel_combustivel;
            $veiculo->save();

            return $this->successResponse($veiculo, 'Nível de combustível atualizado com sucesso');
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao atualizar nível de combustível: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Lista todos os veículos disponíveis.
     *
     * @OA\Get(
     *     path="/veiculos/disponiveis",
     *     summary="Lista veículos disponíveis",
     *     description="Retorna uma lista de veículos com status 'disponivel'",
     *     operationId="veiculosDisponiveis",
     *     tags={"Veículos"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista recuperada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Lista de veículos disponíveis recuperada com sucesso"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Não autorizado"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro do servidor"
     *     )
     * )
     */
    public function disponiveis(): JsonResponse
    {
        // Verificar permissão
        if (!auth()->user()->can('ver veiculos')) {
            return $this->errorResponse('Não autorizado', 403);
        }
        
        try {
            $veiculos = Veiculo::where('status', 'disponivel')->get();

            return $this->successResponse($veiculos, 'Lista de veículos disponíveis recuperada com sucesso');
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao recuperar veículos disponíveis: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Lista veículos por tipo (ambulancia, transporte, apoio).
     *
     * @OA\Get(
     *     path="/veiculos/tipo/{tipo}",
     *     summary="Lista veículos por tipo",
     *     description="Retorna uma lista de veículos filtrados pelo tipo especificado",
     *     operationId="veiculosPorTipo",
     *     tags={"Veículos"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="tipo",
     *         in="path",
     *         description="Tipo de veículo",
     *         required=true,
     *         @OA\Schema(type="string", enum={"ambulancia", "transporte", "apoio"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista recuperada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Lista de veículos do tipo ambulância recuperada com sucesso"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Tipo de veículo inválido"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Não autorizado"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro do servidor"
     *     )
     * )
     */
    public function porTipo(string $tipo): JsonResponse
    {
        // Verificar permissão
        if (!auth()->user()->can('ver veiculos')) {
            return $this->errorResponse('Não autorizado', 403);
        }
        
        if (!in_array($tipo, ['ambulancia', 'transporte', 'apoio'])) {
            return $this->errorResponse('Tipo de veículo inválido', 422);
        }
        
        try {
            $veiculos = Veiculo::where('tipo', $tipo)->get();

            return $this->successResponse($veiculos, "Lista de veículos do tipo {$tipo} recuperada com sucesso");
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao recuperar veículos: ' . $e->getMessage(), 500);
        }
    }
}
