<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends ApiController
{
    /**
     * Login do utilizador e emissão de token
     *
     * @param Request $request
     * @return JsonResponse
     * 
     * @OA\Post(
     *     path="/login",
     *     summary="Autenticar utilizador e obter token",
     *     description="Realiza login de um utilizador e retorna token Sanctum para autenticação",
     *     operationId="login",
     *     tags={"Autenticação"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Credenciais de login",
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="admin@sistema-colera.ao"),
     *             @OA\Property(property="password", type="string", format="password", example="password123"),
     *             @OA\Property(property="device_name", type="string", example="Chrome Browser")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login realizado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Login realizado com sucesso."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Admin Usuário"),
     *                     @OA\Property(property="email", type="string", example="admin@sistema-colera.ao"),
     *                     @OA\Property(property="roles", type="array", @OA\Items(type="string", example="admin")),
     *                     @OA\Property(property="permissions", type="array", @OA\Items(type="string", example="ver pacientes"))
     *                 ),
     *                 @OA\Property(property="token", type="string", example="1|laravel_sanctum_Xg5JedNwtazEXyYJ9RtzOFcsi4922XGvpDbR8vta")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The email field is required."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro interno do servidor"
     *     )
     * )
     */
    public function login(Request $request): JsonResponse
    {
        try {
            // Validar credenciais
            $request->validate([
                'email' => 'required|email|exists:users,email',
                'password' => 'required|string|min:8',
                'device_name' => 'nullable|string',
            ]);

            // Procurar o utilizador pelo e-mail
            $user = User::where('email', $request->email)->first();

            // Verificar se o usuário existe
            if (!$user) {
                throw ValidationException::withMessages([
                    'email' => ['As credenciais fornecidas estão incorretas.'],
                ]);
            }
            
            // Obter a senha em formato string para contornar problemas de tipagem
            $storedHashedPassword = (string) $user->getAuthPassword();
            $providedPassword = (string) $request->password;
            
            // Verificar se a senha está correta usando Hash facade com cast explícito
            if (!Hash::check($providedPassword, $storedHashedPassword)) {
                throw ValidationException::withMessages([
                    'email' => ['As credenciais fornecidas estão incorretas.'],
                ]);
            }
            
            // Nome do dispositivo para o token ou valor padrão
            $deviceName = $request->device_name ?? 'API Token';
            
            // Criar token de acesso
            $token = $user->createToken($deviceName)->plainTextToken;
            
            // Buscar as permissões e papéis do utilizador
            $permissions = $user->getAllPermissions()->pluck('name');
            $roles = $user->roles->pluck('name');

            return $this->successResponse([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $roles,
                    'permissions' => $permissions,
                ],
                'token' => $token,
            ], 'Login realizado com sucesso.', self::HTTP_OK);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Erro de validação das credenciais.');
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao processar o login: ' . $e->getMessage(), self::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Logout do utilizador e revogação do token atual
     *
     * @param Request $request
     * @return JsonResponse
     * 
     * @OA\Post(
     *     path="/logout",
     *     summary="Revogar token e encerrar sessão",
     *     description="Encerra a sessão do utilizador ao revogar o token de autenticação atual",
     *     operationId="logout",
     *     tags={"Autenticação"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logout realizado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Logout realizado com sucesso."),
     *             @OA\Property(property="data", type="null")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autenticado"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro interno do servidor"
     *     )
     * )
     * */
    public function logout(Request $request): JsonResponse
    {
        try {
            // Revogar o token atual que está sendo usado para a autenticação
            $request->user()->currentAccessToken()->delete();
            
            return $this->successResponse(null, 'Logout realizado com sucesso.', self::HTTP_OK);
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao processar o logout: ' . $e->getMessage(), self::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Obter os detalhes do utilizador autenticado
     *
     * @param Request $request
     * @return JsonResponse
     * 
     * @OA\Get(
     *     path="/user",
     *     summary="Obter dados do utilizador atual",
     *     description="Retorna os detalhes do utilizador autenticado, incluindo papéis e permissões",
     *     operationId="getUser",
     *     tags={"Autenticação"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dados obtidos com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Dados do utilizador obtidos com sucesso."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Admin Usuário"),
     *                 @OA\Property(property="email", type="string", example="admin@sistema-colera.ao"),
     *                 @OA\Property(property="roles", type="array", @OA\Items(type="string", example="admin")),
     *                 @OA\Property(property="permissions", type="array", @OA\Items(type="string", example="ver pacientes"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autenticado"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro interno do servidor"
     *     )
     * )
     **/
    public function user(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Buscar as permissões e papéis do utilizador
            $permissions = $user->getAllPermissions()->pluck('name');
            $roles = $user->roles->pluck('name');

            return $this->successResponse([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $roles,
                'permissions' => $permissions,
            ], 'Dados do utilizador obtidos com sucesso.', self::HTTP_OK);
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao obter dados do utilizador: ' . $e->getMessage(), self::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
