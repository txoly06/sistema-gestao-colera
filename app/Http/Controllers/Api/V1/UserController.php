<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

/**
 * @OA\Tag(
 *     name="Usuários",
 *     description="API para gerenciamento de usuários e permissões"
 * )
 */
class UserController extends ApiController
{
    /**
     * Lista todos os usuários com filtros e paginação
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Get(
     *     path="/usuarios",
     *     summary="Lista usuários com filtros e paginação",
     *     description="Retorna uma lista de usuários com possibilidade de filtros por status, categoria, etc.",
     *     operationId="listarUsuarios",
     *     tags={"Usuários"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filtro por status (ativo, bloqueado, inativo)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"ativo", "bloqueado", "inativo"})
     *     ),
     *     @OA\Parameter(
     *         name="categoria",
     *         in="query",
     *         description="Filtro por categoria de usuário",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Termo de busca para nome ou email",
     *         required=false,
     *         @OA\Schema(type="string")
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
     *         description="Lista de usuários recuperada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Usuários obtidos com sucesso"),
     *             @OA\Property(property="data", type="object")
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
        if (!auth()->user()->can('usuarios.listar')) {
            return $this->errorResponse('Não autorizado. Apenas administradores podem listar usuários.', 403);
        }
        
        $query = User::with('roles')->orderBy('name');
        
        // Aplicar filtros
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('categoria')) {
            $query->where('categoria', $request->categoria);
        }
        
        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }
        
        // Filtrar por papel/função
        if ($request->has('role')) {
            $query->whereHas('roles', function($q) use ($request) {
                $q->where('name', $request->role);
            });
        }
        
        $usuarios = $query->paginate($request->per_page ?? 15);
        
        return $this->successResponse($usuarios, 'Usuários obtidos com sucesso');
    }

    /**
     * Mostra detalhes de um usuário específico
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Get(
     *     path="/usuarios/{id}",
     *     summary="Recupera os detalhes de um usuário específico",
     *     description="Retorna detalhes completos de um usuário, incluindo suas funções e permissões",
     *     operationId="visualizarUsuario",
     *     tags={"Usuários"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do usuário",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Usuário recuperado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Usuário obtido com sucesso"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Usuário não encontrado"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Não autorizado"
     *     )
     * )
     */
    public function show($id)
    {
        if (!auth()->user()->can('usuarios.visualizar')) {
            return $this->errorResponse('Não autorizado. Apenas administradores podem visualizar detalhes de usuários.', 403);
        }
        
        $usuario = User::with(['roles', 'permissions', 'unidadeSaude'])->find($id);
        
        if (!$usuario) {
            return $this->errorResponse('Usuário não encontrado', 404);
        }
        
        // Preparar dados para retorno, incluindo permissões diretas e por papel
        $data = $usuario->toArray();
        $data['all_permissions'] = $usuario->getAllPermissions()->pluck('name');
        
        return $this->successResponse($data, 'Usuário obtido com sucesso');
    }
    
    /**
     * Criar um novo usuário
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Post(
     *     path="/usuarios",
     *     summary="Cria um novo usuário",
     *     description="Cria um novo usuário com os dados fornecidos, incluindo funções e permissões",
     *     operationId="criarUsuario",
     *     tags={"Usuários"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "password_confirmation"},
     *             @OA\Property(property="name", type="string", example="João Silva"),
     *             @OA\Property(property="email", type="string", format="email", example="joao.silva@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="senha123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="senha123"),
     *             @OA\Property(property="status", type="string", example="ativo", enum={"ativo", "bloqueado", "inativo"}),
     *             @OA\Property(property="categoria", type="string", example="médico"),
     *             @OA\Property(property="cargo", type="string", example="Clínico Geral"),
     *             @OA\Property(property="telefone", type="string", example="+244 923456789"),
     *             @OA\Property(property="unidade_saude_id", type="integer", example=1),
     *             @OA\Property(property="observacoes", type="string", example="Observações sobre o usuário"),
     *             @OA\Property(property="roles", type="array", @OA\Items(type="string", example="Profissional_Saude")),
     *             @OA\Property(property="permissions", type="array", @OA\Items(type="string", example="ver pacientes"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Usuário criado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Usuário criado com sucesso"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Dados inválidos."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Não autorizado"
     *     )
     * )
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('usuarios.criar')) {
            return $this->errorResponse('Não autorizado. Apenas administradores podem criar usuários.', 403);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'status' => 'nullable|string|in:ativo,bloqueado,inativo',
            'categoria' => 'nullable|string|max:50',
            'cargo' => 'nullable|string|max:100',
            'telefone' => 'nullable|string|max:20',
            'unidade_saude_id' => 'nullable|exists:unidades_saude,id',
            'observacoes' => 'nullable|string|max:1000',
            'roles' => 'nullable|array',
            'roles.*' => 'string|exists:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Dados inválidos.',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Criar usuário
        $usuario = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'status' => $request->status ?? 'ativo',
            'categoria' => $request->categoria,
            'cargo' => $request->cargo,
            'telefone' => $request->telefone,
            'unidade_saude_id' => $request->unidade_saude_id,
            'observacoes' => $request->observacoes,
        ]);
        
        // Atribuir funções (roles)
        if ($request->has('roles') && is_array($request->roles)) {
            $usuario->syncRoles($request->roles);
        }
        
        // Atribuir permissões diretas
        if ($request->has('permissions') && is_array($request->permissions)) {
            $usuario->syncPermissions($request->permissions);
        }
        
        // Carregar relações para retorno
        $usuario->load(['roles', 'permissions', 'unidadeSaude']);
        
        return $this->successResponse($usuario, 'Usuário criado com sucesso', 201);
    }

    /**
     * Atualizar um usuário existente
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Put(
     *     path="/usuarios/{id}",
     *     summary="Atualiza um usuário existente",
     *     description="Atualiza os dados, funções e permissões de um usuário existente",
     *     operationId="atualizarUsuario",
     *     tags={"Usuários"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do usuário",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="João Silva Atualizado"),
     *             @OA\Property(property="email", type="string", format="email", example="joao.silva.novo@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="novaSenha123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="novaSenha123"),
     *             @OA\Property(property="status", type="string", example="ativo", enum={"ativo", "bloqueado", "inativo"}),
     *             @OA\Property(property="categoria", type="string", example="médico"),
     *             @OA\Property(property="cargo", type="string", example="Clínico Geral"),
     *             @OA\Property(property="telefone", type="string", example="+244 923456789"),
     *             @OA\Property(property="unidade_saude_id", type="integer", example=1),
     *             @OA\Property(property="observacoes", type="string", example="Observações atualizadas"),
     *             @OA\Property(property="roles", type="array", @OA\Items(type="string", example="Profissional_Saude")),
     *             @OA\Property(property="permissions", type="array", @OA\Items(type="string", example="ver pacientes"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Usuário atualizado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Usuário atualizado com sucesso"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Usuário não encontrado"
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
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('usuarios.editar')) {
            return $this->errorResponse('Não autorizado. Apenas administradores podem editar usuários.', 403);
        }
        
        $usuario = User::find($id);
        
        if (!$usuario) {
            return $this->errorResponse('Usuário não encontrado', 404);
        }
        
        // Regras de validação
        $rules = [
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:users,email,'.$id,
            'status' => 'nullable|string|in:ativo,bloqueado,inativo',
            'categoria' => 'nullable|string|max:50',
            'cargo' => 'nullable|string|max:100',
            'telefone' => 'nullable|string|max:20',
            'unidade_saude_id' => 'nullable|exists:unidades_saude,id',
            'observacoes' => 'nullable|string|max:1000',
            'roles' => 'nullable|array',
            'roles.*' => 'string|exists:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ];
        
        // Adicionar regras de senha apenas se estiver sendo atualizada
        if ($request->filled('password')) {
            $rules['password'] = 'required|string|min:8|confirmed';
        }
        
        $validator = Validator::make($request->all(), $rules);
        
        if ($validator->fails()) {
            return $this->errorResponse('Erro de validação', 422, $validator->errors());
        }
        
        // Atualizar dados do usuário
        if ($request->filled('name')) {
            $usuario->name = $request->name;
        }
        
        if ($request->filled('email')) {
            $usuario->email = $request->email;
        }
        
        if ($request->filled('password')) {
            $usuario->password = Hash::make($request->password);
        }
        
        if ($request->filled('status')) {
            $usuario->status = $request->status;
        }
        
        if ($request->filled('categoria')) {
            $usuario->categoria = $request->categoria;
        }
        
        if ($request->filled('cargo')) {
            $usuario->cargo = $request->cargo;
        }
        
        if ($request->filled('telefone')) {
            $usuario->telefone = $request->telefone;
        }
        
        if ($request->filled('unidade_saude_id')) {
            $usuario->unidade_saude_id = $request->unidade_saude_id;
        }
        
        if ($request->filled('observacoes')) {
            $usuario->observacoes = $request->observacoes;
        }
        
        $usuario->save();
        
        // Atualizar funções (roles) se fornecidas
        if ($request->has('roles')) {
            $usuario->syncRoles($request->roles);
        }
        
        // Atualizar permissões diretas se fornecidas
        if ($request->has('permissions')) {
            $usuario->syncPermissions($request->permissions);
        }
        
        // Carregar relações para retorno
        $usuario->load(['roles', 'permissions', 'unidadeSaude']);
        
        return $this->successResponse($usuario, 'Usuário atualizado com sucesso');
    }
    
    /**
     * Bloquear um usuário
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Post(
     *     path="/usuarios/{id}/bloquear",
     *     summary="Bloquear um usuário",
     *     description="Bloqueia o acesso de um usuário ao sistema",
     *     operationId="bloquearUsuario",
     *     tags={"Usuários"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do usuário",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="motivo", type="string", example="Violação das regras de uso do sistema")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Usuário bloqueado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Usuário bloqueado com sucesso")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Usuário não encontrado"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Não autorizado"
     *     )
     * )
     */
    public function bloquear(Request $request, $id)
    {
        if (!auth()->user()->can('usuarios.editar')) {
            return $this->errorResponse('Não autorizado. Apenas administradores podem bloquear usuários.', 403);
        }
        
        $usuario = User::find($id);
        
        if (!$usuario) {
            return $this->errorResponse('Usuário não encontrado', 404);
        }
        
        // Não permitir que um administrador seja bloqueado por outro administrador
        if ($usuario->hasRole('Administrador') && auth()->user()->id !== $usuario->id) {
            return $this->errorResponse('Não é possível bloquear outro administrador', 403);
        }
        
        // Não permitir que o próprio usuário se bloqueie
        if (auth()->user()->id === $usuario->id) {
            return $this->errorResponse('Não é possível bloquear a si mesmo', 403);
        }
        
        $motivo = $request->input('motivo', 'Bloqueio administrativo');
        
        $usuario->update([
            'status' => 'bloqueado',
            'observacoes' => ($usuario->observacoes ? $usuario->observacoes . "\n" : '') . 
                           "[" . now()->format('d/m/Y H:i:s') . "] Bloqueado: {$motivo}"
        ]);
        
        // Registrar atividade de auditoria
        activity('usuario')
            ->performedOn($usuario)
            ->causedBy(auth()->user())
            ->withProperties([
                'motivo' => $motivo,
                'tipo_acao' => 'bloqueio'
            ])
            ->log('Usuário bloqueado');
            
        return $this->successResponse(null, 'Usuário bloqueado com sucesso');
    }
    
    /**
     * Desbloquear um usuário
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Post(
     *     path="/usuarios/{id}/desbloquear",
     *     summary="Desbloquear um usuário",
     *     description="Desbloqueia o acesso de um usuário ao sistema",
     *     operationId="desbloquearUsuario",
     *     tags={"Usuários"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do usuário",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Usuário desbloqueado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Usuário desbloqueado com sucesso")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Usuário não encontrado"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Não autorizado"
     *     )
     * )
     */
    public function desbloquear($id)
    {
        if (!auth()->user()->can('usuarios.editar')) {
            return $this->errorResponse('Não autorizado. Apenas administradores podem desbloquear usuários.', 403);
        }
        
        $usuario = User::find($id);
        
        if (!$usuario) {
            return $this->errorResponse('Usuário não encontrado', 404);
        }
        
        if ($usuario->status !== 'bloqueado') {
            return $this->errorResponse('Este usuário não está bloqueado', 422);
        }
        
        $usuario->update([
            'status' => 'ativo',
            'observacoes' => ($usuario->observacoes ? $usuario->observacoes . "\n" : '') . 
                           "[" . now()->format('d/m/Y H:i:s') . "] Desbloqueado por " . auth()->user()->name
        ]);
        
        // Registrar atividade de auditoria
        activity('usuario')
            ->performedOn($usuario)
            ->causedBy(auth()->user())
            ->withProperties([
                'tipo_acao' => 'desbloqueio'
            ])
            ->log('Usuário desbloqueado');
            
        return $this->successResponse(null, 'Usuário desbloqueado com sucesso');
    }
    
    /**
     * Excluir um usuário
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Delete(
     *     path="/usuarios/{id}",
     *     summary="Excluir um usuário",
     *     description="Exclui um usuário do sistema (exclusão lógica)",
     *     operationId="excluirUsuario",
     *     tags={"Usuários"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do usuário",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Usuário excluído com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Usuário excluído com sucesso")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Usuário não encontrado"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Não autorizado"
     *     )
     * )
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('usuarios.eliminar')) {
            return $this->errorResponse('Não autorizado. Apenas administradores podem excluir usuários.', 403);
        }
        
        $usuario = User::find($id);
        
        if (!$usuario) {
            return $this->errorResponse('Usuário não encontrado', 404);
        }
        
        // Não permitir que um administrador seja excluído por outro administrador
        if ($usuario->hasRole('Administrador') && auth()->user()->id !== $usuario->id) {
            return $this->errorResponse('Não é possível excluir outro administrador', 403);
        }
        
        // Não permitir que o próprio usuário se exclua
        if (auth()->user()->id === $usuario->id) {
            return $this->errorResponse('Não é possível excluir a si mesmo', 403);
        }
        
        // Realizar exclusão lógica (soft delete)
        $usuario->delete();
        
        // Registrar atividade de auditoria
        activity('usuario')
            ->performedOn($usuario)
            ->causedBy(auth()->user())
            ->withProperties([
                'tipo_acao' => 'exclusao'
            ])
            ->log('Usuário excluído');
            
        return $this->successResponse(null, 'Usuário excluído com sucesso');
    }
}
