<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\PontoCuidado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PontoCuidadoController extends ApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        // Verificar permissão
        if (!auth()->user()->can('ver pontos-cuidado')) {
            return $this->errorResponse('Não autorizado', 403);
        }
        
        try {
            $pontosCuidado = PontoCuidado::all();

            return $this->successResponse($pontosCuidado, 'Lista de pontos de cuidado recuperada com sucesso');
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao recuperar pontos de cuidado: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        // Verificar permissão
        if (!auth()->user()->can('criar pontos-cuidado')) {
            return $this->errorResponse('Não autorizado', 403);
        }

        // Validar dados
        $validator = Validator::make($request->all(), [
            'nome' => 'required|string|max:255',
            'descricao' => 'required|string',
            'endereco' => 'required|string|max:255',
            'telefone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'responsavel' => 'required|string|max:255',
            'capacidade_maxima' => 'required|integer|min:1',
            'capacidade_atual' => 'nullable|integer|min:0',
            'provincia' => 'required|string|max:100',
            'municipio' => 'required|string|max:100',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'tem_ambulancia' => 'nullable|boolean',
            'ambulancias_disponiveis' => 'nullable|integer|min:0',
            'nivel_prontidao' => 'nullable|string|in:Normal,Alerta,Emergência',
            'status' => 'nullable|string|in:Ativo,Inativo,Manutenção',
            'unidade_saude_id' => 'nullable|exists:unidades_saude,id'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 422);
        }

        try {
            $pontoCuidado = PontoCuidado::create($request->all());

            return $this->successResponse($pontoCuidado, 'Ponto de cuidado criado com sucesso', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao criar ponto de cuidado: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        // Verificar permissão
        if (!auth()->user()->can('ver pontos-cuidado')) {
            return $this->errorResponse('Não autorizado', 403);
        }

        try {
            $pontoCuidado = PontoCuidado::findOrFail($id);

            return $this->successResponse($pontoCuidado, 'Ponto de cuidado recuperado com sucesso');
        } catch (\Exception $e) {
            return $this->errorResponse('Ponto de cuidado não encontrado', 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        // Verificar permissão
        if (!auth()->user()->can('editar pontos-cuidado')) {
            return $this->errorResponse('Não autorizado', 403);
        }

        // Validar dados
        $validator = Validator::make($request->all(), [
            'nome' => 'sometimes|required|string|max:255',
            'descricao' => 'sometimes|required|string',
            'endereco' => 'sometimes|required|string|max:255',
            'telefone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'responsavel' => 'sometimes|required|string|max:255',
            'capacidade_maxima' => 'sometimes|required|integer|min:1',
            'capacidade_atual' => 'nullable|integer|min:0',
            'provincia' => 'sometimes|required|string|max:100',
            'municipio' => 'sometimes|required|string|max:100',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'tem_ambulancia' => 'nullable|boolean',
            'ambulancias_disponiveis' => 'nullable|integer|min:0',
            'nivel_prontidao' => 'nullable|string|in:Normal,Alerta,Emergência',
            'status' => 'nullable|string|in:Ativo,Inativo,Manutenção',
            'unidade_saude_id' => 'nullable|exists:unidades_saude,id'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 422);
        }

        try {
            $pontoCuidado = PontoCuidado::findOrFail($id);
            $pontoCuidado->update($request->all());

            return $this->successResponse($pontoCuidado, 'Ponto de cuidado atualizado com sucesso');
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao atualizar ponto de cuidado: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        // Verificar permissão
        if (!auth()->user()->can('eliminar pontos-cuidado')) {
            return $this->errorResponse('Não autorizado', 403);
        }

        try {
            $pontoCuidado = PontoCuidado::findOrFail($id);
            $pontoCuidado->delete();

            return $this->successResponse(['id' => $id], 'Ponto de cuidado eliminado com sucesso');
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao eliminar ponto de cuidado: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Atualiza o nível de prontidão do ponto de cuidado.
     */
    public function updateProntidao(Request $request, int $id): JsonResponse
    {
        // Verificar permissão
        if (!auth()->user()->can('editar pontos-cuidado')) {
            return $this->errorResponse('Não autorizado', 403);
        }

        // Validar dados
        $validator = Validator::make($request->all(), [
            'nivel_prontidao' => 'required|string|in:Normal,Alerta,Emergência',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 422);
        }

        try {
            $pontoCuidado = PontoCuidado::findOrFail($id);
            $pontoCuidado->nivel_prontidao = $request->nivel_prontidao;
            $pontoCuidado->save();

            return $this->successResponse($pontoCuidado, 'Nível de prontidão atualizado com sucesso');
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao atualizar nível de prontidão: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Atualiza a capacidade atual do ponto de cuidado.
     */
    public function updateCapacidade(Request $request, int $id): JsonResponse
    {
        // Verificar permissão
        if (!auth()->user()->can('editar pontos-cuidado')) {
            return $this->errorResponse('Não autorizado', 403);
        }

        // Validar dados
        $validator = Validator::make($request->all(), [
            'capacidade_atual' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 422);
        }

        try {
            $pontoCuidado = PontoCuidado::findOrFail($id);
            
            // Verificar se a capacidade atual excede a máxima
            if ($request->capacidade_atual > $pontoCuidado->capacidade_maxima) {
                return $this->errorResponse('Capacidade atual não pode exceder a capacidade máxima', 422);
            }
            
            $pontoCuidado->capacidade_atual = $request->capacidade_atual;
            $pontoCuidado->save();

            return $this->successResponse($pontoCuidado, 'Capacidade atual atualizada com sucesso');
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao atualizar capacidade: ' . $e->getMessage(), 500);
        }
    }
}
