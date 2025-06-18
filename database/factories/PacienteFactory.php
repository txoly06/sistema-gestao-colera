<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Paciente>
 */
class PacienteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => $this->faker->name(),
            'bi' => $this->faker->unique()->numerify('########LA###'),
            'data_nascimento' => $this->faker->date(),
            'sexo' => $this->faker->randomElement(['Masculino', 'Feminino']),
            'telefone' => $this->faker->phoneNumber(),
            'endereco' => $this->faker->address(),
            'provincia' => $this->faker->state(),
            'email' => $this->faker->safeEmail(),
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            'historico_saude' => $this->faker->paragraph(),
            'grupo_sanguineo' => $this->faker->randomElement(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']),
            'tem_alergias' => $this->faker->boolean(),
            'alergias' => $this->faker->sentence(),
            'estado' => $this->faker->randomElement(['Ativo', 'Em_Tratamento', 'Recuperado', 'Óbito']),
            'unidade_saude_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
