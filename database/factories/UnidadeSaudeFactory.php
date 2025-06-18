<?php

namespace Database\Factories;

use App\Models\GabineteProvincial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UnidadeSaude>
 */
class UnidadeSaudeFactory extends Factory
{
    /**
     * Define o estado padrão do modelo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => fake()->company() . ' - Unidade de Saúde',
            'tipo' => fake()->randomElement(['Hospital_Geral', 'Centro_Saude', 'Posto_Medico', 'Clinica', 'Outro']),
            'endereco' => fake()->address(),
            'latitude' => fake()->latitude(-8, -18),
            'longitude' => fake()->longitude(11, 24),
            'capacidade' => fake()->numberBetween(10, 500),
            'status' => fake()->randomElement(['Ativo', 'Inativo', 'Em_Manutencao', 'Sobrelotado']),
            'gabinete_provincial_id' => function() {
                return GabineteProvincial::factory()->create()->id;
            }
        ];
    }
    
    /**
     * Define um estado para unidades de saúde com capacidade completa
     */
    public function comCapacidadeCompleta()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'Sobrelotado',
                'capacidade' => 500
            ];
        });
    }
    
    /**
     * Define um estado para unidades de saúde hospitais
     */
    public function hospital()
    {
        return $this->state(function (array $attributes) {
            return [
                'tipo' => 'Hospital_Geral',
                'capacidade' => fake()->numberBetween(100, 500)
            ];
        });
    }
}
