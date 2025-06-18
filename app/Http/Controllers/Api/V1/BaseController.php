<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class BaseController extends Controller
{
    /**
     * Retorna uma resposta de sucesso.
     *
     * @param string $message
     * @param mixed $data
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    public function successResponse($message = 'Operação realizada com sucesso', $data = [], $code = Response::HTTP_OK)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    /**
     * Retorna uma resposta de erro.
     *
     * @param string $message
     * @param array $errorData
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    public function errorResponse($message = 'Ocorreu um erro na operação', $errorData = [], $code = Response::HTTP_BAD_REQUEST)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errorData
        ], $code);
    }
}
