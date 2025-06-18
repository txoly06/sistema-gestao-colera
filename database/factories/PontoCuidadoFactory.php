<?php

namespace Database\Factories;

use App\Models\UnidadeSaude;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PontoCuidado>
 */
class PontoCuidadoFactory extends Factory
{
    /**
     * Define o estado padrão do modelo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => fake()->company() . ' - Ponto de Cuidado',
            'descricao' => fake()->paragraph(),
            'endereco' => fake()->address(),
            'telefone' => fake()->phoneNumber(),
            'email' => fake()->safeEmail(),
            'responsavel' => fake()->name(),
            'capacidade_maxima' => fake()->numberBetween(30, 150),
            'capacidade_atual' => fake()->numberBetween(0, 30),
            'provincia' => fake()->randomElement(['Luanda', 'Benguela', 'Huambo', 'Huíla', 'Uíge']),
            'municipio' => fake()->city(),
            'latitude' => fake()->latitude(-8, -18),
            'longitude' => fake()->longitude(11, 24),
            'tem_ambulancia' => fake()->boolean(),
            'ambulancias_disponiveis' => fake()->numberBetween(0, 5),
            'nivel_prontidao' => fake()->randomElement(['Normal', 'Alerta', 'Emergência']),
            'status' => fake()->randomElement(['Ativo', 'Inativo', 'Manutenção']),
            'unidade_saude_id' => function() {
                // Gera ou retorna um ID de unidade de saúde existente
                return UnidadeSaude::factory()->create()->id;
            }
        ];
    }
    
    /**
     * Define um estado para pontos de cuidado em emergência
     */
    public function emergencia()
    {
        return $this->state(function (array $attributes) {
            return [
                'nivel_prontidao' => 'Emergência',
                'capacidade_atual' => $attributes['capacidade_maxima'] * 0.9, // 90% da capacidade
            ];
        });
    }
    
    /**
     * Define um estado para pontos de cuidado com ambulâncias
     */
    public function comAmbulancias()
    {
        return $this->state(function (array $attributes) {
            return [
                'tem_ambulancia' => true,
                'ambulancias_disponiveis' => fake()->numberBetween(2, 10),
            ];
        });
    }
}
