<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Veiculo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VeiculoController extends ApiController
{
    /**
     * Display a listing of the resource.
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
     */
    public function show(int $id): JsonResponse
    {
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
