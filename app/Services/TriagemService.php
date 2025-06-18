<?php

namespace App\Services;

use App\Models\Sintoma;
use App\Models\Triagem;
use App\Models\Paciente;

/**
 * Serviço para gerenciar operações relacionadas à triagem de pacientes
 */
class TriagemService
{
    /**
     * Fatores de risco por categoria de sintoma (pesos)
     */
    protected $fatoresRisco = [
        'gastrointestinal' => [
            'peso_base' => 30,
            'sintomas_especificos' => ['Diarreia aquosa', 'Vômitos intensos', 'Diarreia com muco'],
            'multiplicador_especifico' => 1.5,
        ],
        'desidratação' => [
            'peso_base' => 25,
            'sintomas_especificos' => ['Sede extrema', 'Pele enrugada', 'Olhos encovados'],
            'multiplicador_especifico' => 1.2,
        ],
        'respiratório' => [
            'peso_base' => 10,
            'multiplicador_especifico' => 0.8,
        ],
        'neurológico' => [
            'peso_base' => 15,
            'multiplicador_especifico' => 0.9,
        ],
        'cardíaco' => [
            'peso_base' => 20,
            'multiplicador_especifico' => 1.0,
        ],
    ];
    
    /**
     * Limiares para recomendações baseadas na probabilidade de cólera
     */
    protected $limiares = [
        'critico' => 75,
        'alto' => 50,
        'medio' => 25,
        'baixo' => 0
    ];
    
    /**
     * Calcula a probabilidade de um paciente ter cólera com base nos sintomas e outros fatores
     *
     * @param array $sintomas Array de sintomas com IDs e intensidades
     * @param float $indiceDesidratacao Índice de desidratação (0-100%)
     * @param array $outrosFatores Outros fatores relevantes (opcionais)
     * @return float Probabilidade de cólera (0-100%)
     */
    public function calcularProbabilidadeColera(array $sintomas, float $indiceDesidratacao = 0, array $outrosFatores = []): float
    {
        $probabilidadeBase = 0;
        $totalPesos = 0;
        
        // Pré-carregar todos os sintomas relevantes para evitar múltiplas consultas ao banco
        $sintomasIds = array_column($sintomas, 'id');
        $sintomasDoBanco = Sintoma::whereIn('id', $sintomasIds)->get()->keyBy('id');
        
        // Calcular pontuação por sintoma
        foreach ($sintomas as $sintoma) {
            $id = $sintoma['id'];
            $intensidade = $sintoma['intensidade'] ?? 3; // Default 3 em escala de 1-5
            
            if (!isset($sintomasDoBanco[$id])) {
                continue; // Sintoma não encontrado no banco
            }
            
            $sintomaDoBanco = $sintomasDoBanco[$id];
            $categoria = $sintomaDoBanco->categoria;
            $gravidade = $sintomaDoBanco->gravidade;
            $especificoColera = $sintomaDoBanco->especifico_colera;
            
            // Determine o peso base da categoria
            $pesoBase = $this->fatoresRisco[$categoria]['peso_base'] ?? 10;
            
            // Calcular peso do sintoma baseado em gravidade e intensidade
            $pesoSintoma = $pesoBase * ($gravidade / 5) * ($intensidade / 5);
            
            // Aumentar peso se for sintoma específico de cólera
            if ($especificoColera) {
                $pesoSintoma *= $this->fatoresRisco[$categoria]['multiplicador_especifico'] ?? 1.2;
            }
            
            // Se o sintoma for um dos mais específicos da categoria, aumentar ainda mais
            $nomeSintoma = $sintomaDoBanco->nome;
            if (isset($this->fatoresRisco[$categoria]['sintomas_especificos']) && 
                in_array($nomeSintoma, $this->fatoresRisco[$categoria]['sintomas_especificos'])) {
                $pesoSintoma *= 1.3;
            }
            
            $probabilidadeBase += $pesoSintoma;
            $totalPesos += $pesoBase;
        }
        
        // Adicionar fator de desidratação (importante na cólera)
        if ($indiceDesidratacao > 0) {
            // Desidratação acima de 5% é sinal de alerta para cólera
            $fatorDesidratacao = $indiceDesidratacao < 5 ? $indiceDesidratacao * 2 : $indiceDesidratacao * 4;
            $probabilidadeBase += $fatorDesidratacao;
            $totalPesos += 20; // Peso base para desidratação
        }
        
        // Normalizar para escala 0-100
        $probabilidadeFinal = ($probabilidadeBase / ($totalPesos > 0 ? $totalPesos : 1)) * 100;
        
        // Limitar entre 0 e 100
        return max(0, min(100, $probabilidadeFinal));
    }
    
    /**
     * Gerar recomendações com base na probabilidade de cólera e nos sintomas
     *
     * @param float $probabilidadeColera Probabilidade de cólera (0-100%)
     * @param array $sintomasIds IDs dos sintomas do paciente
     * @return array Array com recomendações
     */
    public function gerarRecomendacoes(float $probabilidadeColera, array $sintomasIds = []): array
    {
        $recomendacoes = [];
        
        // Recomendações básicas para todos os casos
        $recomendacoes[] = 'Manter hidratação adequada';
        
        if ($probabilidadeColera >= $this->limiares['critico']) {
            // Caso crítico - Alta probabilidade de cólera
            $recomendacoes[] = 'Isolamento imediato do paciente';
            $recomendacoes[] = 'Hidratação intravenosa urgente';
            $recomendacoes[] = 'Antibioticoterapia conforme protocolo de cólera';
            $recomendacoes[] = 'Monitoramento contínuo de sinais vitais';
            $recomendacoes[] = 'Notificação ao sistema de vigilância epidemiológica';
            $recomendacoes[] = 'Coleta de amostras para confirmação laboratorial de cólera';
        } 
        elseif ($probabilidadeColera >= $this->limiares['alto']) {
            // Caso de alto risco
            $recomendacoes[] = 'Isolamento do paciente recomendado';
            $recomendacoes[] = 'Hidratação intravenosa';
            $recomendacoes[] = 'Monitoramento frequente de sinais vitais';
            $recomendacoes[] = 'Coleta de amostras para análise laboratorial';
            $recomendacoes[] = 'Considerar antibioticoterapia se sintomas persistirem';
            $recomendacoes[] = 'Notificação preventiva ao sistema de vigilância';
        }
        elseif ($probabilidadeColera >= $this->limiares['medio']) {
            // Caso de risco médio
            $recomendacoes[] = 'Reidratação oral intensificada';
            $recomendacoes[] = 'Monitoramento de sinais vitais a cada 4 horas';
            $recomendacoes[] = 'Observação por 24-48 horas';
            $recomendacoes[] = 'Coleta de amostras para análise se sintomas persistirem';
            $recomendacoes[] = 'Orientações para isolamento domiciliar';
        }
        else {
            // Caso de baixo risco
            $recomendacoes[] = 'Reidratação oral';
            $recomendacoes[] = 'Orientações para manejo domiciliar dos sintomas';
            $recomendacoes[] = 'Retornar se houver piora dos sintomas';
            $recomendacoes[] = 'Medidas gerais de higiene';
        }
        
        return $recomendacoes;
    }
    
    /**
     * Determina o nível de urgência baseado na probabilidade de cólera e outros fatores
     *
     * @param float $probabilidadeColera Probabilidade de cólera
     * @param array $outrosFatores Outros fatores (idade, comorbidades, etc.)
     * @return string Nível de urgência ('baixo', 'medio', 'alto', 'critico')
     */
    public function determinarNivelUrgencia(float $probabilidadeColera, array $outrosFatores = []): string
    {
        if ($probabilidadeColera >= $this->limiares['critico']) {
            return 'critico';
        } elseif ($probabilidadeColera >= $this->limiares['alto']) {
            return 'alto';
        } elseif ($probabilidadeColera >= $this->limiares['medio']) {
            return 'medio';
        } else {
            return 'baixo';
        }
    }
    
    /**
     * Calcula o risco combinando probabilidade, nível de urgência e recomendações
     *
     * @param array $sintomas Array de sintomas com detalhes
     * @param float $indiceDesidratacao Índice de desidratação (0-10)
     * @param float $temperatura Temperatura corporal em Celsius
     * @param array $outrosFatores Outros fatores relevantes (opcional)
     * @return array Resultado com probabilidade, nível de urgência e recomendações
     */
    public function calcularRisco(array $sintomas, float $indiceDesidratacao = 0, float $temperatura = 37.0, array $outrosFatores = []): array
    {
        // Extrair apenas os IDs dos sintomas para uso posterior
        $sintomasIds = array_column($sintomas, 'id');
        
        // Calcular probabilidade de cólera
        $probabilidade = $this->calcularProbabilidadeColera($sintomas, $indiceDesidratacao, $outrosFatores);
        
        // Determinar nível de urgência
        $nivelUrgencia = $this->determinarNivelUrgencia($probabilidade, [
            'temperatura' => $temperatura,
            'indice_desidratacao' => $indiceDesidratacao
        ]);
        
        // Gerar recomendações baseadas na probabilidade e sintomas
        $recomendacoes = $this->gerarRecomendacoes($probabilidade, $sintomasIds);
        
        // Retornar resultado combinado
        return [
            'probabilidade' => $probabilidade,
            'nivel_urgencia' => $nivelUrgencia,
            'recomendacoes' => implode(\PHP_EOL, $recomendacoes)
        ];
    }
    
    /**
     * Processa uma nova triagem com base nos dados fornecidos
     * 
     * @param array $dados Dados da triagem
     * @return Triagem
     */
    public function processarTriagem(array $dados): Triagem
    {
        // Extrair dados essenciais
        $pacienteId = $dados['paciente_id'];
        $sintomasArray = $dados['sintomas'] ?? [];
        $indiceDesidratacao = $dados['indice_desidratacao'] ?? 0;
        
        // Calcular probabilidade de cólera
        $probabilidadeColera = $this->calcularProbabilidadeColera(
            $sintomasArray, 
            $indiceDesidratacao
        );
        
        // Determinar nível de urgência
        $nivelUrgencia = $this->determinarNivelUrgencia($probabilidadeColera);
        
        // Gerar recomendações
        $sintomasIds = array_column($sintomasArray, 'id');
        $recomendacoes = $this->gerarRecomendacoes($probabilidadeColera, $sintomasIds);
        
        // Atualizar dados com os cálculos
        $dados['probabilidade_colera'] = $probabilidadeColera;
        $dados['nivel_urgencia'] = $nivelUrgencia;
        $dados['recomendacoes'] = $recomendacoes;
        $dados['status'] = $dados['status'] ?? 'pendente';
        
        // Criar nova triagem
        return Triagem::create($dados);
    }
    
    /**
     * Atualiza probabilidade e recomendações de uma triagem existente
     * 
     * @param Triagem $triagem
     * @return Triagem
     */
    public function atualizarTriagem(Triagem $triagem): Triagem
    {
        $probabilidadeColera = $this->calcularProbabilidadeColera(
            $triagem->sintomas, 
            $triagem->indice_desidratacao
        );
        
        $nivelUrgencia = $this->determinarNivelUrgencia($probabilidadeColera);
        $sintomasIds = array_column($triagem->sintomas, 'id');
        $recomendacoes = $this->gerarRecomendacoes($probabilidadeColera, $sintomasIds);
        
        $triagem->update([
            'probabilidade_colera' => $probabilidadeColera,
            'nivel_urgencia' => $nivelUrgencia,
            'recomendacoes' => $recomendacoes
        ]);
        
        return $triagem;
    }
}
