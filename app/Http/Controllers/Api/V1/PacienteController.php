<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Paciente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PacienteController extends ApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        // Verificar permissão
        if (!auth()->user()->can('ver pacientes')) {
            return $this->errorResponse('Não autorizado', 403);
        }
        
        try {
            $pacientes = Paciente::all();

            return $this->successResponse($pacientes, 'Lista de pacientes recuperada com sucesso');
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao recuperar pacientes: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        // Verificar permissão
        if (!auth()->user()->can('criar pacientes')) {
            return $this->errorResponse('Não autorizado', 403);
        }

        // Validar dados
        $validator = Validator::make($request->all(), [
            'nome' => 'required|string|max:255',
            'bi' => 'required|string|max:20|unique:pacientes',
            'data_nascimento' => 'required|date',
            'sexo' => 'required|in:Masculino,Feminino',
            'telefone' => 'nullable|string|max:20',
            'endereco' => 'required|string|max:255',
            'provincia' => 'required|string|max:50',
            'email' => 'nullable|email|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'historico_saude' => 'nullable|string',
            'grupo_sanguineo' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'tem_alergias' => 'nullable|boolean',
            'alergias' => 'nullable|string',
            'estado' => 'nullable|in:Ativo,Em_Tratamento,Recuperado,Óbito',
            'unidade_saude_id' => 'nullable|exists:unidades_saude,id',
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

            $paciente = new Paciente();
            $paciente->nome = $request->nome;
            $paciente->bi = $request->bi;
            $paciente->data_nascimento = $request->data_nascimento;
            $paciente->sexo = $request->sexo;
            $paciente->telefone = $request->telefone; // Será encriptado automaticamente pelo accessor
            $paciente->endereco = $request->endereco;
            $paciente->provincia = $request->provincia;
            $paciente->email = $request->email; // Será encriptado automaticamente pelo accessor
            $paciente->latitude = $request->latitude;
            $paciente->longitude = $request->longitude;
            $paciente->historico_saude = $request->historico_saude; // Será encriptado automaticamente pelo accessor
            $paciente->grupo_sanguineo = $request->grupo_sanguineo;
            $paciente->tem_alergias = $request->tem_alergias ?? false;
            $paciente->alergias = $request->alergias; // Será encriptado automaticamente pelo accessor
            $paciente->estado = $request->estado ?? 'Ativo';
            $paciente->unidade_saude_id = $request->unidade_saude_id;
            $paciente->save();

            DB::commit();

            return $this->successResponse($paciente, 'Paciente criado com sucesso', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Erro ao criar paciente: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id): JsonResponse
    {
        try {
            $paciente = Paciente::findOrFail($id);

            return $this->successResponse($paciente, 'Paciente recuperado com sucesso');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Paciente não encontrado',
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
        if (!auth()->user()->can('editar pacientes')) {
            return $this->errorResponse('Não autorizado', 403);
        }

        // Buscar paciente
        try {
            $paciente = Paciente::findOrFail($id);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Paciente não encontrado',
                'error' => 'Recurso não encontrado'
            ], 404);
        }

        // Validar dados
        $validator = Validator::make($request->all(), [
            'nome' => 'string|max:255',
            'bi' => 'string|max:20|unique:pacientes,bi,' . $id,
            'data_nascimento' => 'date',
            'sexo' => 'in:Masculino,Feminino',
            'telefone' => 'nullable|string|max:20',
            'endereco' => 'string|max:255',
            'provincia' => 'string|max:50',
            'email' => 'nullable|email|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'historico_saude' => 'nullable|string',
            'grupo_sanguineo' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'tem_alergias' => 'boolean',
            'alergias' => 'nullable|string',
            'estado' => 'in:Ativo,Em_Tratamento,Recuperado,Óbito',
            'unidade_saude_id' => 'nullable|exists:unidades_saude,id',
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

            // Atualizar campos um por um para garantir que os accessors de encriptação sejam usados
            if ($request->has('nome')) $paciente->nome = $request->nome;
            if ($request->has('bi')) $paciente->bi = $request->bi;
            if ($request->has('data_nascimento')) $paciente->data_nascimento = $request->data_nascimento;
            if ($request->has('sexo')) $paciente->sexo = $request->sexo;
            if ($request->has('telefone')) $paciente->telefone = $request->telefone;
            if ($request->has('endereco')) $paciente->endereco = $request->endereco;
            if ($request->has('provincia')) $paciente->provincia = $request->provincia;
            if ($request->has('email')) $paciente->email = $request->email;
            if ($request->has('latitude')) $paciente->latitude = $request->latitude;
            if ($request->has('longitude')) $paciente->longitude = $request->longitude;
            if ($request->has('historico_saude')) $paciente->historico_saude = $request->historico_saude;
            if ($request->has('grupo_sanguineo')) $paciente->grupo_sanguineo = $request->grupo_sanguineo;
            if ($request->has('tem_alergias')) $paciente->tem_alergias = $request->tem_alergias;
            if ($request->has('alergias')) $paciente->alergias = $request->alergias;
            if ($request->has('estado')) $paciente->estado = $request->estado;
            if ($request->has('unidade_saude_id')) $paciente->unidade_saude_id = $request->unidade_saude_id;
            
            $paciente->save();

            DB::commit();

            // Buscar o objeto atualizado para garantir que todos os dados estejam presentes
            $paciente = Paciente::findOrFail($id);

            return $this->successResponse($paciente, 'Paciente atualizado com sucesso');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Erro ao atualizar paciente: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id): JsonResponse
    {
        // Verificar permissão
        if (!auth()->user()->can('eliminar pacientes')) {
            return $this->errorResponse('Não autorizado', 403);
        }

        // Buscar e eliminar paciente
        try {
            $paciente = Paciente::findOrFail($id);

            DB::beginTransaction();

            $paciente->delete();

            DB::commit();

            return $this->successResponse(null, 'Paciente eliminado com sucesso');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Erro ao eliminar paciente: ' . $e->getMessage(), 500);
        }
    }
}
