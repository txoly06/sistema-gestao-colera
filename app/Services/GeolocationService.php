<?php

namespace App\Services;

use GoogleMaps\GoogleMaps;

class GeolocationService
{
    protected $googleMaps;
    
    /**
     * Construtor que inicializa o serviço com a instância do GoogleMaps.
     */
    public function __construct(GoogleMaps $googleMaps)
    {
        $this->googleMaps = $googleMaps;
    }
    
    /**
     * Calcular distância entre dois pontos (em metros).
     *
     * @param float $lat1 Latitude do ponto 1
     * @param float $lng1 Longitude do ponto 1
     * @param float $lat2 Latitude do ponto 2
     * @param float $lng2 Longitude do ponto 2
     * @return float Distância em metros
     */
    public function calcularDistancia($lat1, $lng1, $lat2, $lng2)
    {
        $params = [
            'origins'        => $lat1 . ',' . $lng1,
            'destinations'   => $lat2 . ',' . $lng2,
            'mode'           => 'driving',
            'language'       => 'pt-BR'
        ];
        
        try {
            $response = $this->googleMaps->load('distancematrix')
                ->setParam($params)
                ->get();
                
            $response = json_decode($response);
            
            if ($response->status === 'OK' && isset($response->rows[0]->elements[0]->distance->value)) {
                return $response->rows[0]->elements[0]->distance->value; // Distância em metros
            }
            
            return $this->calcularDistanciaHaversine($lat1, $lng1, $lat2, $lng2);
        } catch (\Exception $e) {
            // Fallback para cálculo básico caso a API falhe
            return $this->calcularDistanciaHaversine($lat1, $lng1, $lat2, $lng2);
        }
    }
    
    /**
     * Cálculo de distância usando a fórmula de Haversine (fallback).
     * Estima a distância em linha reta entre dois pontos na superfície terrestre.
     */
    protected function calcularDistanciaHaversine($lat1, $lng1, $lat2, $lng2)
    {
        // Raio médio da Terra em metros
        $raioTerra = 6371000;
        
        // Converter coordenadas para radianos
        $lat1Rad = deg2rad($lat1);
        $lng1Rad = deg2rad($lng1);
        $lat2Rad = deg2rad($lat2);
        $lng2Rad = deg2rad($lng2);
        
        // Diferenças em radianos
        $latDelta = $lat2Rad - $lat1Rad;
        $lngDelta = $lng2Rad - $lng1Rad;
        
        // Fórmula de Haversine
        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos($lat1Rad) * cos($lat2Rad) * 
             sin($lngDelta / 2) * sin($lngDelta / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        // Distância em metros
        return $raioTerra * $c;
    }
    
    /**
     * Geocodificar um endereço para obter coordenadas.
     *
     * @param string $endereco Endereço a ser geocodificado
     * @return array|null Array com 'lat' e 'lng' ou null se falhar
     */
    public function geocodificarEndereco($endereco)
    {
        $params = [
            'address' => $endereco,
            'language' => 'pt-BR',
        ];
        
        try {
            $response = $this->googleMaps->load('geocoding')
                ->setParam($params)
                ->get();
                
            $response = json_decode($response);
            
            if ($response->status === 'OK' && isset($response->results[0]->geometry->location)) {
                return [
                    'lat' => $response->results[0]->geometry->location->lat,
                    'lng' => $response->results[0]->geometry->location->lng,
                ];
            }
            
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Calcular rota entre dois pontos.
     *
     * @param float $lat1 Latitude da origem
     * @param float $lng1 Longitude da origem
     * @param float $lat2 Latitude do destino
     * @param float $lng2 Longitude do destino
     * @return array|null Informações da rota ou null se falhar
     */
    public function calcularRota($lat1, $lng1, $lat2, $lng2)
    {
        $params = [
            'origin'        => $lat1 . ',' . $lng1,
            'destination'   => $lat2 . ',' . $lng2,
            'mode'          => 'driving',
            'language'      => 'pt-BR'
        ];
        
        try {
            $response = $this->googleMaps->load('directions')
                ->setParam($params)
                ->get();
                
            $response = json_decode($response);
            
            if ($response->status === 'OK' && isset($response->routes[0])) {
                return [
                    'distancia' => $response->routes[0]->legs[0]->distance->value, // metros
                    'duracao' => $response->routes[0]->legs[0]->duration->value, // segundos
                    'duracao_texto' => $response->routes[0]->legs[0]->duration->text,
                    'instrucoes' => $this->extrairInstrucoes($response->routes[0]->legs[0]->steps),
                    'rota_polyline' => $response->routes[0]->overview_polyline->points
                ];
            }
            
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Extrair instruções de navegação das etapas da rota.
     */
    protected function extrairInstrucoes($steps)
    {
        $instrucoes = [];
        
        foreach ($steps as $step) {
            $instrucoes[] = [
                'texto' => strip_tags($step->html_instructions),
                'distancia' => $step->distance->value,
                'duracao' => $step->duration->value
            ];
        }
        
        return $instrucoes;
    }
    
    /**
     * Encontrar pontos de cuidado mais próximos de uma localização.
     *
     * @param float $lat Latitude atual
     * @param float $lng Longitude atual
     * @param int $limite Número máximo de resultados
     * @return array Pontos de cuidado ordenados por proximidade
     */
    public function encontrarPontosCuidadoProximos($lat, $lng, $limite = 5)
    {
        $pontosCuidado = \App\Models\PontoCuidado::all();
        $pontosCuidadoDistancias = [];
        
        foreach ($pontosCuidado as $ponto) {
            if ($ponto->latitude && $ponto->longitude) {
                $distancia = $this->calcularDistanciaHaversine($lat, $lng, $ponto->latitude, $ponto->longitude);
                
                $pontosCuidadoDistancias[] = [
                    'ponto' => $ponto,
                    'distancia' => $distancia,
                    'distancia_texto' => $this->formatarDistancia($distancia)
                ];
            }
        }
        
        // Ordenar por proximidade
        usort($pontosCuidadoDistancias, function ($a, $b) {
            return $a['distancia'] <=> $b['distancia'];
        });
        
        // Limitar resultados
        return array_slice($pontosCuidadoDistancias, 0, $limite);
    }
    
    /**
     * Encontrar veículos mais próximos de uma localização.
     *
     * @param float $lat Latitude atual
     * @param float $lng Longitude atual
     * @param string|null $tipo Filtrar por tipo de veículo (opcional)
     * @param int $limite Número máximo de resultados
     * @return array Veículos ordenados por proximidade
     */
    public function encontrarVeiculosProximos($lat, $lng, $tipo = null, $limite = 5)
    {
        $query = \App\Models\Veiculo::where('status', 'disponivel');
        
        if ($tipo) {
            $query->where('tipo', $tipo);
        }
        
        $veiculos = $query->get();
        $veiculosDistancias = [];
        
        foreach ($veiculos as $veiculo) {
            if ($veiculo->latitude && $veiculo->longitude) {
                $distancia = $this->calcularDistanciaHaversine($lat, $lng, $veiculo->latitude, $veiculo->longitude);
                
                $veiculosDistancias[] = [
                    'veiculo' => $veiculo,
                    'distancia' => $distancia,
                    'distancia_texto' => $this->formatarDistancia($distancia),
                    'tempo_estimado' => $this->estimarTempoChegada($distancia)
                ];
            }
        }
        
        // Ordenar por proximidade
        usort($veiculosDistancias, function ($a, $b) {
            return $a['distancia'] <=> $b['distancia'];
        });
        
        // Limitar resultados
        return array_slice($veiculosDistancias, 0, $limite);
    }
    
    /**
     * Formatar distância para exibição amigável.
     */
    protected function formatarDistancia($metros)
    {
        if ($metros < 1000) {
            return round($metros) . ' m';
        } else {
            return round($metros / 1000, 1) . ' km';
        }
    }
    
    /**
     * Estimar tempo de chegada baseado na distância.
     * Considera velocidade média de 40 km/h em áreas urbanas.
     */
    protected function estimarTempoChegada($metros)
    {
        // Velocidade média: 40 km/h = 11.11 m/s
        $velocidadeMedia = 11.11;
        $segundos = $metros / $velocidadeMedia;
        
        // Converter para minutos
        $minutos = ceil($segundos / 60);
        
        if ($minutos < 60) {
            return $minutos . ' minutos';
        } else {
            $horas = floor($minutos / 60);
            $minutosRestantes = $minutos % 60;
            
            if ($minutosRestantes > 0) {
                return $horas . ' h ' . $minutosRestantes . ' min';
            } else {
                return $horas . ' hora(s)';
            }
        }
    }
}
