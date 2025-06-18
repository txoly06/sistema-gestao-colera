<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sintoma>
 */
class SintomaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Lista de sintomas comuns de cólera e outras doenças
        $categorias = ['gastrointestinal', 'desidratação', 'respiratório', 'neurológico', 'cardíaco'];
        $categoria = $this->faker->randomElement($categorias);
        
        // Nomes de sintomas por categoria
        $nomesPorCategoria = [
            'gastrointestinal' => [
                'Diarreia aquosa', 'Vômitos', 'Dor abdominal', 'Diarreia com muco', 
                'Náusea', 'Distúrgio abdominal', 'Flatulência'
            ],
            'desidratação' => [
                'Sede extrema', 'Boca e língua secas', 'Olhos encovados', 
                'Ausencia de lágrimas', 'Pele enrugada', 'Letargia', 'Fraqueza'
            ],
            'respiratório' => [
                'Respiração rápida', 'Falta de ar', 'Dor no peito'
            ],
            'neurológico' => [
                'Confusão mental', 'Irritação', 'Convulsões', 'Sonolência', 'Desorientação'
            ],
            'cardíaco' => [
                'Pulso fraco', 'Pulso acelerado', 'Pressão arterial baixa', 'Desmaio'
            ]
        ];
        
        // Determinando se é um sintoma específico de cólera
        $especificoColera = $categoria === 'gastrointestinal' || $categoria === 'desidratação' 
                           ? $this->faker->boolean(70) // 70% dos gastrointestinais/desidratação são de cólera
                           : $this->faker->boolean(10); // 10% dos outros são de cólera
        
        // Gravidade varia de 1 a 5
        $gravidade = $especificoColera ? $this->faker->numberBetween(3, 5) : $this->faker->numberBetween(1, 5);
        
        // Selecionando um nome aleatório da categoria
        $nome = $this->faker->randomElement($nomesPorCategoria[$categoria]);
        
        return [
            'nome' => $nome,
            'descricao' => $this->faker->sentence(10),
            'gravidade' => $gravidade,
            'especifico_colera' => $especificoColera,
            'categoria' => $categoria,
            'sintomas_relacionados' => $especificoColera ? $this->faker->randomElements([1, 2, 3, 4, 5], 2) : null,
        ];
    }
    
    /**
     * Estado para sintomas específicos de cólera
     */
    public function especificoColera()
    {
        return $this->state(function (array $attributes) {
            return [
                'especifico_colera' => true,
                'gravidade' => $this->faker->numberBetween(4, 5),
            ];
        });
    }
    
    /**
     * Estado para sintomas graves (gravidade 4-5)
     */
    public function grave()
    {
        return $this->state(function (array $attributes) {
            return [
                'gravidade' => $this->faker->numberBetween(4, 5),
            ];
        });
    }
}
