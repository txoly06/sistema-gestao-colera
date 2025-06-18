<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Triagem>
 */
class TriagemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Status possíveis para a triagem
        $statusOpcoes = ['pendente', 'em_andamento', 'concluida', 'encaminhada'];
        $niveisUrgencia = ['baixo', 'medio', 'alto', 'critico'];
        
        // Sintomas aleatórios (simulação de IDs e intensidades)
        $sintomasFake = [
            ['id' => 1, 'intensidade' => $this->faker->numberBetween(1, 5), 'descricao' => 'Diarreia aquosa'],
            ['id' => 2, 'intensidade' => $this->faker->numberBetween(1, 5), 'descricao' => 'Vômitos']
        ];
        
        // Probabilidade de cólera - geramos um número aleatório de 0 a 100
        $probabilidadeColera = $this->faker->randomFloat(2, 0, 100);
        
        // Ajustar nível de urgência conforme a probabilidade
        $nivelUrgencia = 'medio';
        if ($probabilidadeColera > 80) {
            $nivelUrgencia = 'critico';
        } elseif ($probabilidadeColera > 60) {
            $nivelUrgencia = 'alto';
        } elseif ($probabilidadeColera < 20) {
            $nivelUrgencia = 'baixo';
        }
        
        // Recomendações baseadas na probabilidade
        $recomendacoes = [];
        if ($probabilidadeColera > 70) {
            $recomendacoes[] = 'Isolamento imediato';
            $recomendacoes[] = 'Hidratação intravenosa';
            $recomendacoes[] = 'Antibioticoterapia';
        } elseif ($probabilidadeColera > 40) {
            $recomendacoes[] = 'Observação constante';
            $recomendacoes[] = 'Hidratação oral';
            $recomendacoes[] = 'Coleta de amostras para confirmação';
        } else {
            $recomendacoes[] = 'Hidratação oral';
            $recomendacoes[] = 'Observação doméstica';
            $recomendacoes[] = 'Retorno se sintomas persistirem';
        }
        
        return [
            'paciente_id' => \App\Models\Paciente::factory(),
            'unidade_saude_id' => \App\Models\UnidadeSaude::factory(),
            'ponto_cuidado_id' => $this->faker->boolean(50) ? \App\Models\PontoCuidado::factory() : null,
            'responsavel_id' => \App\Models\User::factory(),
            'nivel_urgencia' => $nivelUrgencia,
            'status' => $this->faker->randomElement($statusOpcoes),
            'sintomas' => $sintomasFake,
            'indice_desidratacao' => $this->faker->randomFloat(2, 0, 15),
            'temperatura' => $this->faker->randomFloat(1, 36.0, 40.0),
            'frequencia_cardiaca' => $this->faker->numberBetween(60, 160),
            'frequencia_respiratoria' => $this->faker->numberBetween(10, 40),
            'probabilidade_colera' => $probabilidadeColera,
            'recomendacoes' => $recomendacoes,
            'observacoes' => $this->faker->paragraph(),
            'encaminhamentos' => [],
            'data_inicio_sintomas' => $this->faker->dateTimeBetween('-7 days', 'now'),
            'data_conclusao' => $this->faker->boolean(30) ? $this->faker->dateTimeBetween('-2 days', 'now') : null,
        ];
    }
    
    /**
     * Estado para triagens críticas (alta probabilidade de cólera)
     */
    public function critica()
    {
        return $this->state(function (array $attributes) {
            return [
                'nivel_urgencia' => 'critico',
                'probabilidade_colera' => $this->faker->randomFloat(2, 75, 100),
                'indice_desidratacao' => $this->faker->randomFloat(2, 8, 15),
                'recomendacoes' => [
                    'Isolamento imediato',
                    'Hidratação intravenosa',
                    'Antibioticoterapia',
                    'Monitoramento contínuo de sinais vitais'
                ],
            ];
        });
    }
    
    /**
     * Estado para triagens concluídas
     */
    public function concluida()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'concluida',
                'data_conclusao' => $this->faker->dateTimeBetween('-2 days', 'now'),
            ];
        });
    }
    
    /**
     * Estado para triagens encaminhadas
     */
    public function encaminhada()
    {
        return $this->state(function (array $attributes) {
            $unidadeDestino = \App\Models\UnidadeSaude::factory()->create();
            
            return [
                'status' => 'encaminhada',
                'encaminhamentos' => [
                    [
                        'data' => $this->faker->dateTimeBetween('-1 day', 'now')->format('Y-m-d H:i:s'),
                        'unidade_destino_id' => $unidadeDestino->id,
                        'unidade_destino_nome' => $unidadeDestino->nome,
                        'motivo' => 'Necessidade de cuidados especializados',
                        'responsavel_id' => \App\Models\User::factory()->create()->id
                    ]
                ],
            ];
        });
    }
}
