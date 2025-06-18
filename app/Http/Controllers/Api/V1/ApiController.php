<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ApiController extends Controller
{
    /**
     * Código de status de sucesso
     */
    protected const HTTP_OK = 200;
    protected const HTTP_CREATED = 201;
    protected const HTTP_ACCEPTED = 202;
    protected const HTTP_NO_CONTENT = 204;
    
    /**
     * Código de status de erro
     */
    protected const HTTP_BAD_REQUEST = 400;
    protected const HTTP_UNAUTHORIZED = 401;
    protected const HTTP_FORBIDDEN = 403;
    protected const HTTP_NOT_FOUND = 404;
    protected const HTTP_METHOD_NOT_ALLOWED = 405;
    protected const HTTP_UNPROCESSABLE_ENTITY = 422;
    protected const HTTP_INTERNAL_SERVER_ERROR = 500;
    
    /**
     * Resposta de sucesso
     *
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    protected function successResponse($data = null, string $message = '', int $statusCode = self::HTTP_OK): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }
    
    /**
     * Resposta de erro
     *
     * @param string $message
     * @param int $statusCode
     * @param mixed $errors
     * @return \Illuminate\Http\JsonResponse
     */
    protected function errorResponse(string $message, int $statusCode, $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];
        
        if ($errors !== null) {
            $response['error'] = $errors; // Mudado de 'errors' para 'error' para combinar com os testes
        }
        
        return response()->json($response, $statusCode);
    }
    
    /**
     * Resposta para criação de recurso
     *
     * @param mixed $data
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function createdResponse($data = null, string $message = 'Recurso criado com sucesso'): JsonResponse
    {
        return $this->successResponse($data, $message, self::HTTP_CREATED);
    }
    
    /**
     * Resposta para atualização de recurso
     *
     * @param mixed $data
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function updatedResponse($data = null, string $message = 'Recurso atualizado com sucesso'): JsonResponse
    {
        return $this->successResponse($data, $message, self::HTTP_OK);
    }
    
    /**
     * Resposta para exclusão de recurso
     *
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function deletedResponse(string $message = 'Recurso eliminado com sucesso'): JsonResponse
    {
        return $this->successResponse(null, $message, self::HTTP_OK);
    }
    
    /**
     * Resposta para recurso não encontrado
     *
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function notFoundResponse(string $message = 'Recurso não encontrado'): JsonResponse
    {
        return $this->errorResponse($message, self::HTTP_NOT_FOUND);
    }
    
    /**
     * Resposta para validação de erros
     *
     * @param mixed $errors
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function validationErrorResponse($errors, string $message = 'Erro de validação'): JsonResponse
    {
        return $this->errorResponse($message, self::HTTP_UNPROCESSABLE_ENTITY, $errors);
    }
}
