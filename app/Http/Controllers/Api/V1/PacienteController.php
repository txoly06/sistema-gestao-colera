<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\ApiController;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Models\Paciente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Controller()
 */
class PacienteController extends ApiController
{
    /**
     * Display a listing of the resource.
     * 
     * @OA\Get(
     *     path="/pacientes",
     *     summary="Lista todos os pacientes",
     *     description="Retorna uma lista de todos os pacientes cadastrados no sistema",
     *     operationId="listaPacientes",
     *     tags={"Pacientes"},
     *     security={"bearerAuth": {}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista recuperada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Lista de pacientes recuperada com sucesso"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="nome", type="string", example="João Silva"),
     *                 @OA\Property(property="bi", type="string", example="123456789LA042"),
     *                 @OA\Property(property="data_nascimento", type="string", format="date", example="1990-01-01"),
     *                 @OA\Property(property="sexo", type="string", example="Masculino"),
     *                 @OA\Property(property="estado", type="string", example="Ativo"),
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
     * 
     * @OA\Post(
     *     path="/pacientes",
     *     summary="Cadastra um novo paciente",
     *     description="Cria um novo registro de paciente no sistema",
     *     operationId="criarPaciente",
     *     tags={"Pacientes"},
     *     security={"bearerAuth": {}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nome", "bi", "data_nascimento", "sexo", "endereco", "provincia"},
     *             @OA\Property(property="nome", type="string", example="João Silva"),
     *             @OA\Property(property="bi", type="string", example="123456789LA042"),
     *             @OA\Property(property="data_nascimento", type="string", format="date", example="1990-01-01"),
     *             @OA\Property(property="sexo", type="string", example="Masculino", enum={"Masculino", "Feminino"}),
     *             @OA\Property(property="telefone", type="string", example="+244 923456789"),
     *             @OA\Property(property="endereco", type="string", example="Rua Principal, 123"),
     *             @OA\Property(property="provincia", type="string", example="Luanda"),
     *             @OA\Property(property="email", type="string", format="email", example="joao.silva@exemplo.com"),
     *             @OA\Property(property="latitude", type="number", format="float", example=-8.838333),
     *             @OA\Property(property="longitude", type="number", format="float", example=13.234444),
     *             @OA\Property(property="historico_saude", type="string", example="Hipertensão controlada"),
     *             @OA\Property(property="grupo_sanguineo", type="string", example="O+", enum={"A+", "A-", "B+", "B-", "AB+", "AB-", "O+", "O-"}),
     *             @OA\Property(property="tem_alergias", type="boolean", example=false),
     *             @OA\Property(property="alergias", type="string", example="Penicilina"),
     *             @OA\Property(property="estado", type="string", example="Ativo", enum={"Ativo", "Em_Tratamento", "Recuperado", "Óbito"}),
     *             @OA\Property(property="unidade_saude_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Paciente criado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Paciente criado com sucesso"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="nome", type="string"),
     *                 @OA\Property(property="bi", type="string")
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
     *         description="Erro do servidor"
     *     )
     * )
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
     *
     * @OA\Get(
     *     path="/pacientes/{id}",
     *     summary="Exibe detalhes de um paciente específico",
     *     description="Retorna os dados completos de um paciente cadastrado",
     *     operationId="mostrarPaciente",
     *     tags={"Pacientes"},
     *     security={"bearerAuth": {}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do paciente",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Dados recuperados com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Paciente encontrado com sucesso"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="nome", type="string"),
     *                 @OA\Property(property="bi", type="string"),
     *                 @OA\Property(property="data_nascimento", type="string", format="date"),
     *                 @OA\Property(property="sexo", type="string"),
     *                 @OA\Property(property="telefone", type="string"),
     *                 @OA\Property(property="endereco", type="string"),
     *                 @OA\Property(property="provincia", type="string"),
     *                 @OA\Property(property="email", type="string"),
     *                 @OA\Property(property="latitude", type="number", format="float"),
     *                 @OA\Property(property="longitude", type="number", format="float"),
     *                 @OA\Property(property="historico_saude", type="string"),
     *                 @OA\Property(property="grupo_sanguineo", type="string"),
     *                 @OA\Property(property="tem_alergias", type="boolean"),
     *                 @OA\Property(property="alergias", type="string"),
     *                 @OA\Property(property="estado", type="string"),
     *                 @OA\Property(property="unidade_saude_id", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Paciente não encontrado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Paciente não encontrado")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Não autorizado"
     *     )
     * )
     */
    public function show($id): JsonResponse
    {
        // Verificar permissão
        if (!auth()->user()->can('ver pacientes')) {
            return $this->errorResponse('Não autorizado', 403);
        }

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
     *
     * @OA\Put(
     *     path="/pacientes/{id}",
     *     summary="Atualiza dados de um paciente",
     *     description="Atualiza as informações de um paciente cadastrado",
     *     operationId="atualizarPaciente",
     *     tags={"Pacientes"},
     *     security={"bearerAuth": {}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do paciente",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="nome", type="string", example="João Silva"),
     *             @OA\Property(property="bi", type="string", example="123456789LA042"),
     *             @OA\Property(property="data_nascimento", type="string", format="date", example="1990-01-01"),
     *             @OA\Property(property="sexo", type="string", example="Masculino", enum={"Masculino", "Feminino"}),
     *             @OA\Property(property="telefone", type="string", example="+244 923456789"),
     *             @OA\Property(property="endereco", type="string", example="Rua Principal, 123"),
     *             @OA\Property(property="provincia", type="string", example="Luanda"),
     *             @OA\Property(property="email", type="string", format="email", example="joao.silva@exemplo.com"),
     *             @OA\Property(property="latitude", type="number", format="float", example=-8.838333),
     *             @OA\Property(property="longitude", type="number", format="float", example=13.234444),
     *             @OA\Property(property="historico_saude", type="string", example="Hipertensão controlada"),
     *             @OA\Property(property="grupo_sanguineo", type="string", example="O+", enum={"A+", "A-", "B+", "B-", "AB+", "AB-", "O+", "O-"}),
     *             @OA\Property(property="tem_alergias", type="boolean", example=false),
     *             @OA\Property(property="alergias", type="string", example="Penicilina"),
     *             @OA\Property(property="estado", type="string", example="Ativo", enum={"Ativo", "Em_Tratamento", "Recuperado", "Óbito"}),
     *             @OA\Property(property="unidade_saude_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Paciente atualizado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Paciente atualizado com sucesso"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Paciente não encontrado"
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
     * 
     * @OA\Delete(
     *     path="/pacientes/{id}",
     *     summary="Remove um paciente",
     *     description="Exclui um paciente do sistema (soft delete)",
     *     operationId="excluirPaciente",
     *     tags={"Pacientes"},
     *     security={"bearerAuth": {}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do paciente",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Paciente excluído com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Paciente excluído com sucesso")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Paciente não encontrado"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Não autorizado"
     *     )
     * )
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
    
    /**
     * Generate QR Code for a patient
     *
     * @param int $id ID do paciente
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     *
     * @OA\Get(
     *     path="/pacientes/{id}/qrcode",
     *     summary="Gerar QR Code para identificação de paciente",
     *     description="Gera um código QR com dados do paciente para identificação rápida",
     *     operationId="gerarQrCodePaciente",
     *     tags={"Pacientes"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do paciente",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="download",
     *         in="query",
     *         description="Se definido como true, retorna a imagem para download",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="QR Code gerado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="QR Code gerado com sucesso"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="paciente_id", type="integer", example=1),
     *                 @OA\Property(property="paciente_nome", type="string", example="João Silva"),
     *                 @OA\Property(property="qrcode", type="string", example="data:image/png;base64,iVBORw0KGgoAA...")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Paciente não encontrado"
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
    public function generateQrCode($id)
    {
        // Verificar permissão
        if (!auth()->user()->can('ver pacientes')) {
            return $this->errorResponse('Não autorizado', 403);
        }
        
        try {
            $paciente = Paciente::findOrFail($id);
            
            // Preparar dados para o QR Code
            $data = [
                'id' => $paciente->id,
                'nome' => $paciente->nome,
                'bi' => $paciente->bi,
                'data_nascimento' => $paciente->data_nascimento,
                'links' => [
                    'perfil' => route('api.v1.pacientes.show', $paciente->id),
                    'triagens' => route('api.v1.pacientes.triagens', $paciente->id)
                ],
                'acesso_em' => now()->toIso8601String(),
                'verificação' => md5($paciente->id . $paciente->created_at->toIso8601String())
            ];
            
            // Gerar QR Code como imagem
            $qrcode = QrCode::format('png')
                            ->size(300)
                            ->errorCorrection('H')
                            ->margin(1)
                            ->generate(json_encode($data));
            
            // Opção 1: Retornar QR code como imagem
            if (request()->query('download', false)) {
                $headers = [
                    'Content-Type' => 'image/png',
                    'Content-Disposition' => 'attachment; filename="paciente-' . $paciente->id . '-qrcode.png"'
                ];
                
                return response($qrcode, 200, $headers);
            }
            
            // Opção 2: Retornar QR code como dados base64 (padrão)
            return $this->successResponse([
                'paciente_id' => $paciente->id,
                'paciente_nome' => $paciente->nome,
                'qrcode' => 'data:image/png;base64,' . base64_encode($qrcode)
            ], 'QR Code gerado com sucesso');
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Paciente não encontrado', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao gerar QR Code: ' . $e->getMessage(), 500);
        }
    }
}
