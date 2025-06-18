<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\UnidadeSaude;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UnidadeSaudeController extends ApiController
{
    /**
     * Display a listing of the resource.
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
     */
    public function show($id): JsonResponse
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
     */
    public function update(Request $request, $id): JsonResponse
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
     */
    public function destroy($id): JsonResponse
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
