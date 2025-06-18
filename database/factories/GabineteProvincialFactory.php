<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GabineteProvincial>
 */
class GabineteProvincialFactory extends Factory
{
    /**
     * Define o estado padrão do modelo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => 'Gabinete Provincial de ' . fake()->randomElement(['Luanda', 'Benguela', 'Huambo', 'Huíla', 'Uíge', 'Malanje', 'Bié']),
            'endereco' => fake()->address(),
            'telefone' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'diretor' => fake()->name(),
            'provincia' => fake()->randomElement(['Luanda', 'Benguela', 'Huambo', 'Huíla', 'Uíge', 'Malanje', 'Bié']),
            'latitude' => fake()->latitude(-8, -18),
            'longitude' => fake()->longitude(11, 24),
            'ativo' => true
        ];
    }
    
    /**
     * Define um estado para gabinetes inativos
     */
    public function inativo()
    {
        return $this->state(function (array $attributes) {
            return [
                'ativo' => false
            ];
        });
    }
}
