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

            // Verificar se a senha está correta
            if (!$user || !Hash::check($request->password, $user->password)) {
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
     */
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
     */
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
