<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\GeolocationService;
use App\Models\PontoCuidado;
use App\Models\Veiculo;
use App\Models\UnidadeSaude;
use App\Models\Triagem;
use App\Models\Paciente;
use Illuminate\Http\Request;

/**
 * @OA\Controller()
 * @OA\Tag(name="Mapa", description="Endpoints para visualização e manipulação de dados geográficos")
 */
class MapaController extends Controller
{
    protected $geoService;
    
    /**
     * Construtor que recebe o serviço de geolocalização.
     */
    public function __construct(GeolocationService $geoService)
    {
        $this->geoService = $geoService;
    }
    
    /**
     * Obter todos os pontos para visualização no mapa.
     * Pontos de cuidado, veículos e unidades de saúde com suas coordenadas.
     * 
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Get(
     *     path="/mapa/pontos",
     *     summary="Obter todos os pontos para visualização no mapa",
     *     description="Retorna todos os pontos de cuidado, veículos e unidades de saúde com suas coordenadas geográficas",
     *     operationId="obterTodosPontosMapa",
     *     tags={"Mapa"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Operação bem-sucedida",
     *         @OA\JsonContent(
     *             @OA\Property(property="pontos_cuidado", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="unidades_saude", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="veiculos", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Não autorizado"
     *     )
     * )
     */
    public function todosOsPontos()
    {
        $pontosCuidado = PontoCuidado::select('id', 'nome', 'latitude', 'longitude', 'nivel_prontidao', 'capacidade_atual', 'capacidade_maxima')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get()
            ->map(function($ponto) {
                return [
                    'id' => $ponto->id,
                    'tipo' => 'ponto_cuidado',
                    'nome' => $ponto->nome,
                    'latitude' => $ponto->latitude,
                    'longitude' => $ponto->longitude,
                    'nivel_prontidao' => $ponto->nivel_prontidao,
                    'capacidade' => "{$ponto->capacidade_atual}/{$ponto->capacidade_maxima}",
                    'icone' => 'ponto_cuidado.png'
                ];
            });
            
        $veiculos = Veiculo::select('id', 'placa', 'modelo', 'tipo', 'status', 'latitude', 'longitude')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get()
            ->map(function($veiculo) {
                return [
                    'id' => $veiculo->id,
                    'tipo' => 'veiculo',
                    'nome' => "{$veiculo->tipo} - {$veiculo->placa}",
                    'latitude' => $veiculo->latitude,
                    'longitude' => $veiculo->longitude,
                    'status' => $veiculo->status,
                    'icone' => 'ambulancia.png'
                ];
            });
            
        $unidadesSaude = UnidadeSaude::select('id', 'nome', 'latitude', 'longitude')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get()
            ->map(function($unidade) {
                return [
                    'id' => $unidade->id,
                    'tipo' => 'unidade_saude',
                    'nome' => $unidade->nome,
                    'latitude' => $unidade->latitude,
                    'longitude' => $unidade->longitude,
                    'icone' => 'hospital.png'
                ];
            });
            
        $dados = [
            'pontos_cuidado' => $pontosCuidado,
            'veiculos' => $veiculos,
            'unidades_saude' => $unidadesSaude,
            'total' => $pontosCuidado->count() + $veiculos->count() + $unidadesSaude->count()
        ];
        
        return $this->successResponse($dados, 'Dados do mapa obtidos com sucesso');
    }
    
    /**
     * Encontrar pontos de cuidado próximos a uma localização.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function encontrarProximos(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'limite' => 'sometimes|integer|min:1|max:20',
        ]);
        
        $pontos = $this->geoService->encontrarPontosCuidadoProximos(
            $request->latitude, 
            $request->longitude,
            $request->limite ?? 5
        );
        
        return $this->successResponse($pontos, 'Pontos de cuidado próximos encontrados com sucesso');
    }
    
    /**
     * Encontrar veículos próximos a uma localização.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Get(
     *     path="/mapa/veiculos-proximos",
     *     summary="Encontrar veículos próximos",
     *     description="Retorna veículos próximos a uma localização especificada",
     *     operationId="encontrarVeiculosProximos",
     *     tags={"Mapa"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="latitude",
     *         in="query",
     *         description="Latitude da localização de referência",
     *         required=true,
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Parameter(
     *         name="longitude",
     *         in="query",
     *         description="Longitude da localização de referência",
     *         required=true,
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Parameter(
     *         name="tipo",
     *         in="query",
     *         description="Tipo de veículo (ambulância, viatura, etc.)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="limite",
     *         in="query",
     *         description="Número máximo de resultados a retornar",
     *         required=false,
     *         @OA\Schema(type="integer", default=5)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Operação bem-sucedida",
     *         @OA\JsonContent(
     *             @OA\Property(property="veiculos_proximos", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="total_encontrado", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação"
     *     )
     * )
     */
    public function veiculosProximos(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'tipo' => 'sometimes|string',
            'limite' => 'sometimes|integer|min:1|max:20',
        ]);
        
        $veiculos = $this->geoService->encontrarVeiculosProximos(
            $request->latitude, 
            $request->longitude,
            $request->tipo,
            $request->limite ?? 5
        );
        
        return $this->successResponse($veiculos, 'Veículos próximos encontrados com sucesso');
    }
    
    /**
     * Calcular rota entre duas localizações.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Post(
     *     path="/mapa/calcular-rota",
     *     summary="Calcular rota entre duas localizações",
     *     description="Calcula a melhor rota entre duas coordenadas geográficas",
     *     operationId="calcularRotaMapa",
     *     tags={"Mapa"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="origem_latitude",
     *         in="query",
     *         description="Latitude do ponto de origem",
     *         required=true,
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Parameter(
     *         name="origem_longitude",
     *         in="query",
     *         description="Longitude do ponto de origem",
     *         required=true,
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Parameter(
     *         name="destino_latitude",
     *         in="query",
     *         description="Latitude do ponto de destino",
     *         required=true,
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Parameter(
     *         name="destino_longitude",
     *         in="query",
     *         description="Longitude do ponto de destino",
     *         required=true,
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Parameter(
     *         name="modo",
     *         in="query",
     *         description="Modo de transporte (driving, walking, bicycling, transit)",
     *         required=false,
     *         @OA\Schema(type="string", default="driving", enum={"driving", "walking", "bicycling", "transit"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Operação bem-sucedida",
     *         @OA\JsonContent(
     *             @OA\Property(property="rota", type="object"),
     *             @OA\Property(property="distancia", type="object"),
     *             @OA\Property(property="duracao", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro ao calcular rota"
     *     )
     * )
     */
    public function calcularRota(Request $request)
    {
        // Verificar e mapear parâmetros com nomes alternativos
        $requestData = $request->all();
        
        // Mapear parâmetros alternativos para os nomes padrão
        if (!isset($requestData['origem_latitude']) && isset($requestData['origem_lat'])) {
            $requestData['origem_latitude'] = $requestData['origem_lat'];
        }
        
        if (!isset($requestData['origem_longitude']) && isset($requestData['origem_lng'])) {
            $requestData['origem_longitude'] = $requestData['origem_lng'];
        }
        
        if (!isset($requestData['destino_latitude']) && isset($requestData['destino_lat'])) {
            $requestData['destino_latitude'] = $requestData['destino_lat'];
        }
        
        if (!isset($requestData['destino_longitude']) && isset($requestData['destino_lng'])) {
            $requestData['destino_longitude'] = $requestData['destino_lng'];
        }
        
        // Merge dos dados mapeados de volta na requisição
        $request->merge($requestData);
        
        // Agora validate com os nomes padrão
        $request->validate([
            'origem_latitude' => 'required|numeric',
            'origem_longitude' => 'required|numeric',
            'destino_latitude' => 'required|numeric',
            'destino_longitude' => 'required|numeric',
        ]);
        
        // Log para debug
        \Log::info('Parâmetros processados:', $request->all());
        
        // Obter parâmetros independente de onde foram enviados (query ou body)
        $origemLat = $request->input('origem_latitude');
        $origemLng = $request->input('origem_longitude');
        $destinoLat = $request->input('destino_latitude');
        $destinoLng = $request->input('destino_longitude');
        
        $rota = $this->geoService->calcularRota(
            $origemLat,
            $origemLng,
            $destinoLat,
            $destinoLng
        );
        
        if (!$rota) {
            return $this->errorResponse('Não foi possível calcular a rota');
        }
        
        return $this->successResponse($rota, 'Rota calculada com sucesso');
    }
    
    /**
     * Geocodificar um endereço
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Post(
     *     path="/mapa/geocodificar",
     *     summary="Geocodificar um endereço",
     *     description="Converte um endereço textual em coordenadas geográficas",
     *     operationId="geocodificarEndereco",
     *     tags={"Mapa"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="endereco",
     *         in="query",
     *         description="Endereço a ser geocodificado",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Operação bem-sucedida",
     *         @OA\JsonContent(
     *             @OA\Property(property="resultado", type="object",
     *                 @OA\Property(property="endereco", type="string"),
     *                 @OA\Property(property="latitude", type="number", format="float"),
     *                 @OA\Property(property="longitude", type="number", format="float"),
     *                 @OA\Property(property="precisao", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro ao geocodificar endereço"
     *     )
     * )
     */
    public function geocodificar(Request $request)
    {
        $request->validate([
            'endereco' => 'required|string|min:5',
        ]);
        
        $resultado = $this->geoService->geocodificarEndereco($request->endereco);
        
        if (!$resultado) {
            return response()->json([
                'success' => false,
                'message' => 'Não foi possível geocodificar o endereço'
            ], 400);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Endereço geocodificado com sucesso',
            'data' => $resultado
        ]);
    }
    
    /**
     * Encontrar pontos de cuidado próximos a uma localização.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Get(
     *     path="/mapa/pontos-cuidado-proximos",
     *     summary="Encontrar pontos de cuidado próximos",
     *     description="Retorna pontos de cuidado próximos a uma localização especificada",
     *     operationId="encontrarPontosCuidadoProximos",
     *     tags={"Mapa"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="latitude",
     *         in="query",
     *         description="Latitude da localização de referência",
     *         required=true,
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Parameter(
     *         name="longitude",
     *         in="query",
     *         description="Longitude da localização de referência",
     *         required=true,
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Parameter(
     *         name="limite",
     *         in="query",
     *         description="Número máximo de resultados a retornar",
     *         required=false,
     *         @OA\Schema(type="integer", default=5)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Operação bem-sucedida",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Pontos de cuidado próximos encontrados com sucesso"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="nome", type="string", example="Ponto de Cuidado Central"),
     *                 @OA\Property(property="distancia", type="number", format="float", example=2.5)
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Dados inválidos"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro ao calcular pontos próximos"
     *     )
     * )
     */
    public function pontosCuidadoProximos(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'limite' => 'nullable|integer|min:1|max:50'
        ]);

        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $limite = $request->limite ?? 5;

        try {
            // Buscar pontos de cuidado próximos
            $pontosCuidado = PontoCuidado::whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->get()
                ->map(function($ponto) use ($latitude, $longitude) {
                    $distancia = $this->geoService->calcularDistancia(
                        $latitude, 
                        $longitude, 
                        (float)$ponto->latitude, 
                        (float)$ponto->longitude
                    );
                    
                    return [
                        'ponto' => [
                            'id' => $ponto->id,
                            'nome' => $ponto->nome,
                            'latitude' => $ponto->latitude,
                            'longitude' => $ponto->longitude,
                            'capacidade_atual' => $ponto->capacidade_atual,
                            'capacidade_maxima' => $ponto->capacidade_maxima
                        ],
                        'distancia' => $distancia,
                        'distancia_texto' => number_format($distancia, 2) . ' km',
                    ];
                })
                ->sortBy('distancia')
                ->take($limite)
                ->values()
                ->all();
                
            return $this->successResponse($pontosCuidado, 'Pontos de cuidado próximos encontrados com sucesso');
            
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao calcular pontos de cuidado próximos: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Gerar dados para mapa de calor de casos
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function mapaDeCasos()
    {
        // Obter todos os pacientes com triagens com alto risco de cólera
        $pacientesComColera = Paciente::whereHas('triagens', function($query) {
            $query->where('nivel_urgencia', 'alto')
                ->where('probabilidade_colera', '>=', 70);
        })
        ->with(['triagens' => function($query) {
            $query->where('nivel_urgencia', 'alto')
                ->where('probabilidade_colera', '>=', 70)
                ->orderBy('created_at', 'desc')
                ->limit(1);
        }])
        ->get();
        
        $pontos = [];
        
        foreach ($pacientesComColera as $paciente) {
            // Usar coordenadas do paciente se disponíveis
            if ($paciente->latitude && $paciente->longitude) {
                $pontos[] = [
                    'lat' => $paciente->latitude, 
                    'lng' => $paciente->longitude, 
                    'peso' => $paciente->triagens->first()->probabilidade_colera / 100
                ];
            } 
            // Caso contrário, usar geocodificação baseada no endereço
            elseif ($paciente->endereco) {
                $geo = $this->geoService->geocodificarEndereco($paciente->endereco);
                if ($geo) {
                    $pontos[] = [
                        'lat' => $geo['lat'], 
                        'lng' => $geo['lng'], 
                        'peso' => $paciente->triagens->first()->probabilidade_colera / 100
                    ];
                }
            }
        }
        
        return $this->successResponse([
            'pontos' => $pontos,
            'total' => count($pontos)
        ], 'Dados do mapa de calor obtidos com sucesso');
    }
    
    /**
     * Enviar resposta de sucesso.
     *
     * @param  mixed  $data
     * @param  string  $message
     * @param  int  $code
     * @return \Illuminate\Http\JsonResponse
     */
    protected function successResponse($data, $message = 'Operação realizada com sucesso', $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $code);
    }
    
    /**
     * Enviar resposta de erro.
     *
     * @param  string  $message
     * @param  int  $code
     * @return \Illuminate\Http\JsonResponse
     */
    protected function errorResponse($message, $code = 404)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $code);
    }
    
    /**
     * Gera mapa de calor com casos de cólera por localização.
     *
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Get(
     *     path="/mapa/heat-map-casos",
     *     summary="Mapa de calor de casos",
     *     description="Retorna dados para geração de mapa de calor (heatmap) dos casos de cólera",
     *     operationId="heatMapCasos",
     *     tags={"Mapa"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Operação bem-sucedida",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Dados do mapa de calor obtidos com sucesso"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="pontos", type="array", @OA\Items(type="object",
     *                     @OA\Property(property="lat", type="number", format="float", example=-8.838333),
     *                     @OA\Property(property="lng", type="number", format="float", example=13.234444),
     *                     @OA\Property(property="peso", type="integer", example=5)
     *                 )),
     *                 @OA\Property(property="total", type="integer", example=42)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Não autorizado"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro ao gerar mapa de calor"
     *     )
     * )
     */
    public function heatMapCasos(): \Illuminate\Http\JsonResponse
    {
        // Verificar permissão - ignorar em ambiente de testes
        if (!app()->environment('testing') && !auth()->user()->can('ver mapa')) {
            return $this->errorResponse('Não autorizado', 403);
        }
        
        try {
            // Obter casos por localização
            $pontosCalor = [];
            
            // De pacientes (via triagens)
            $triagens = Triagem::with(['paciente' => function($query) {
                $query->whereNotNull('latitude')->whereNotNull('longitude');
            }])
            ->get()
            ->filter(function($triagem) {
                return $triagem->paciente && $triagem->paciente->latitude && $triagem->paciente->longitude;
            })
            ->groupBy(function($triagem) {
                // Agrupar por coordenadas com pequeno arredondamento para aglutinar pontos muito próximos
                return round($triagem->paciente->latitude, 4) . '-' . round($triagem->paciente->longitude, 4);
            })
            ->map(function($grupo, $coordKey) {
                $coords = explode('-', $coordKey);
                return [
                    'lat' => (float) $coords[0],
                    'lng' => (float) $coords[1],
                    'peso' => $grupo->count(),
                    'origem' => 'paciente'
                ];
            })
            ->values()
            ->all();
            
            $pontosCalor = array_merge($pontosCalor, $triagens);
            
            // De pontos de cuidado
            $pontosCuidado = PontoCuidado::whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->get()
                ->map(function($ponto) {
                    // Peso baseado na ocupação atual
                    return [
                        'lat' => (float) $ponto->latitude,
                        'lng' => (float) $ponto->longitude,
                        'peso' => max(1, (int) ($ponto->capacidade_atual / 3)),  // Peso proporcional à ocupação
                        'origem' => 'ponto_cuidado'
                    ];
                })
                ->all();
            
            $pontosCalor = array_merge($pontosCalor, $pontosCuidado);
            
            return $this->successResponse([
                'pontos' => $pontosCalor,
                'total' => count($pontosCalor)
            ], 'Dados do mapa de calor obtidos com sucesso');
            
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao gerar mapa de calor: ' . $e->getMessage(), 500);
        }
    }
}
