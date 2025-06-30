<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\ApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

/**
 * @OA\Tag(
 *     name="Papéis e Permissões",
 *     description="API para gerenciamento de papéis e permissões do sistema"
 * )
 */
class RoleController extends ApiController
{
    /**
     * Listar todos os papéis (roles)
     *
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Get(
     *     path="/papeis",
     *     summary="Listar todos os papéis",
     *     description="Retorna uma lista de todos os papéis disponíveis no sistema",
     *     operationId="listarPapeis",
     *     tags={"Papéis e Permissões"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de papéis",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Papéis obtidos com sucesso"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Não autorizado"
     *     )
     * )
     */
    public function index()
    {
        if (!auth()->user()->can('papeis.gerenciar')) {
            return $this->errorResponse('Não autorizado. Apenas administradores podem gerenciar papéis.', 403);
        }
        
        $roles = Role::with('permissions')->get();
        
        return $this->successResponse($roles, 'Papéis obtidos com sucesso');
    }
    
    /**
     * Obter detalhes de um papel específico
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Get(
     *     path="/papeis/{id}",
     *     summary="Obter detalhes de um papel",
     *     description="Retorna os detalhes de um papel específico, incluindo suas permissões",
     *     operationId="detalhesPapel",
     *     tags={"Papéis e Permissões"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do papel",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalhes do papel",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Papel obtido com sucesso"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Papel não encontrado"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Não autorizado"
     *     )
     * )
     */
    public function show($id)
    {
        if (!auth()->user()->can('papeis.gerenciar')) {
            return $this->errorResponse('Não autorizado. Apenas administradores podem gerenciar papéis.', 403);
        }
        
        $role = Role::with('permissions')->find($id);
        
        if (!$role) {
            return $this->errorResponse('Papel não encontrado', 404);
        }
        
        return $this->successResponse($role, 'Papel obtido com sucesso');
    }
    
    /**
     * Criar um novo papel
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Post(
     *     path="/papeis",
     *     summary="Criar um novo papel",
     *     description="Cria um novo papel com as permissões especificadas",
     *     operationId="criarPapel",
     *     tags={"Papéis e Permissões"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Analista"),
     *             @OA\Property(property="permissions", type="array", @OA\Items(type="string", example="ver pacientes"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Papel criado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Papel criado com sucesso"),
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
     *     )
     * )
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('papeis.gerenciar')) {
            return $this->errorResponse('Não autorizado. Apenas administradores podem gerenciar papéis.', 403);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);
        
        if ($validator->fails()) {
            return $this->errorResponse('Erro de validação', 422, $validator->errors());
        }
        
        $role = Role::create(['name' => $request->name, 'guard_name' => 'web']);
        
        if ($request->has('permissions') && is_array($request->permissions)) {
            $permissions = Permission::whereIn('name', $request->permissions)->get();
            $role->syncPermissions($permissions);
        }
        
        $role->load('permissions');
        
        return $this->successResponse($role, 'Papel criado com sucesso', 201);
    }
    
    /**
     * Atualizar um papel existente
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Put(
     *     path="/papeis/{id}",
     *     summary="Atualizar um papel existente",
     *     description="Atualiza os detalhes e permissões de um papel existente",
     *     operationId="atualizarPapel",
     *     tags={"Papéis e Permissões"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do papel",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Analista Sênior"),
     *             @OA\Property(property="permissions", type="array", @OA\Items(type="string", example="ver pacientes"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Papel atualizado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Papel atualizado com sucesso"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Papel não encontrado"
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
        if (!auth()->user()->can('papeis.gerenciar')) {
            return $this->errorResponse('Não autorizado. Apenas administradores podem gerenciar papéis.', 403);
        }
        
        $role = Role::find($id);
        
        if (!$role) {
            return $this->errorResponse('Papel não encontrado', 404);
        }
        
        // Não permitir atualização de papéis especiais do sistema
        if (in_array($role->name, ['Administrador', 'Super_Admin'])) {
            return $this->errorResponse('Não é possível modificar papéis reservados do sistema', 403);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|unique:roles,name,'.$role->id,
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);
        
        if ($validator->fails()) {
            return $this->errorResponse('Erro de validação', 422, $validator->errors());
        }
        
        if ($request->filled('name')) {
            $role->name = $request->name;
            $role->save();
        }
        
        if ($request->has('permissions')) {
            $permissions = Permission::whereIn('name', $request->permissions)->get();
            $role->syncPermissions($permissions);
        }
        
        $role->load('permissions');
        
        return $this->successResponse($role, 'Papel atualizado com sucesso');
    }
    
    /**
     * Excluir um papel
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Delete(
     *     path="/papeis/{id}",
     *     summary="Excluir um papel",
     *     description="Exclui um papel do sistema",
     *     operationId="excluirPapel",
     *     tags={"Papéis e Permissões"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do papel",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Papel excluído com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Papel excluído com sucesso")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Papel não encontrado"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Não autorizado"
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflito - Papel em uso"
     *     )
     * )
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('papeis.gerenciar')) {
            return $this->errorResponse('Não autorizado. Apenas administradores podem gerenciar papéis.', 403);
        }
        
        $role = Role::with('users')->find($id);
        
        if (!$role) {
            return $this->errorResponse('Papel não encontrado', 404);
        }
        
        // Não permitir exclusão de papéis especiais do sistema
        if (in_array($role->name, ['Administrador', 'Gestor', 'Profissional_Saude', 'Tecnico', 'Condutor', 'Paciente'])) {
            return $this->errorResponse('Não é possível excluir papéis reservados do sistema', 403);
        }
        
        // Verificar se há usuários usando este papel
        if ($role->users->count() > 0) {
            return $this->errorResponse('Este papel não pode ser excluído porque está associado a ' . $role->users->count() . ' usuário(s)', 409);
        }
        
        $role->delete();
        
        // Registrar atividade de auditoria
        activity('papel')
            ->performedOn($role)
            ->causedBy(auth()->user())
            ->withProperties([
                'tipo_acao' => 'exclusao'
            ])
            ->log('Papel excluído');
        
        return $this->successResponse(null, 'Papel excluído com sucesso');
    }
    
    /**
     * Listar todas as permissões disponíveis
     *
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Get(
     *     path="/permissoes",
     *     summary="Listar todas as permissões",
     *     description="Retorna uma lista de todas as permissões disponíveis no sistema",
     *     operationId="listarPermissoes",
     *     tags={"Papéis e Permissões"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de permissões",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Permissões obtidas com sucesso"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Não autorizado"
     *     )
     * )
     */
    public function listarPermissoes()
    {
        if (!auth()->user()->can('papeis.gerenciar')) {
            return $this->errorResponse('Não autorizado. Apenas administradores podem gerenciar papéis e permissões.', 403);
        }
        
        $permissions = Permission::all();
        
        return $this->successResponse($permissions, 'Permissões obtidas com sucesso');
    }
}
