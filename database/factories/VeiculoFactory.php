<?php

namespace Database\Factories;

use App\Models\PontoCuidado;
use App\Models\UnidadeSaude;
use App\Models\Veiculo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Veiculo>
 */
class VeiculoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tiposVeiculos = ['ambulancia', 'transporte', 'apoio'];
        $statusVeiculos = ['disponivel', 'em_transito', 'em_manutencao', 'indisponivel'];
        
        // Gerar equipamentos aleatórios para ambulancias
        $equipamentos = [];
        $equipamentosDisponiveis = [
            'desfibrilador', 'respirador', 'kit_primeiros_socorros', 'maca', 'cadeira_rodas',
            'monitor_cardiaco', 'oxigenio', 'kit_emergencia_obstetricia', 'medicamentos_basicos'
        ];
        
        // Selecionar 3-6 equipamentos aleatórios
        $numEquipamentos = $this->faker->numberBetween(3, 6);
        $equipamentosIndices = array_rand($equipamentosDisponiveis, $numEquipamentos);
        if (!is_array($equipamentosIndices)) {
            $equipamentosIndices = [$equipamentosIndices];
        }
        foreach ($equipamentosIndices as $idx) {
            $equipamentos[] = $equipamentosDisponiveis[$idx];
        }
        
        // Gerar equipe médica aleatória
        $equipe = [];
        $roles = ['motorista', 'enfermeiro', 'medico', 'tecnico'];
        foreach ($roles as $role) {
            if ($this->faker->boolean(70)) { // 70% de chance para cada papel estar presente
                $equipe[$role] = $this->faker->name;
            }
        }
        
        // Definir coordenadas dentro de Angola (aproximadamente Luanda)
        $latitude = $this->faker->latitude(-8.7, -8.9);
        $longitude = $this->faker->longitude(13.1, 13.3);
        
        return [
            'placa' => strtoupper($this->faker->randomLetter . $this->faker->randomLetter) . '-' . 
                      $this->faker->numerify('####'),
            'modelo' => $this->faker->randomElement(['Toyota Hiace', 'Mitsubishi L300', 'Mercedes Sprinter', 'Ford Transit', 'Renault Master']),
            'ano' => $this->faker->numberBetween(2015, 2025),
            'tipo' => $this->faker->randomElement($tiposVeiculos),
            'status' => $this->faker->randomElement($statusVeiculos),
            'descricao' => $this->faker->sentence(),
            'capacidade_pacientes' => $this->faker->numberBetween(1, 6),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'ultima_atualizacao_localizacao' => $this->faker->dateTimeBetween('-2 days', 'now'),
            'equipamentos' => $equipamentos,
            'equipe_medica' => $equipe,
            'tem_gps' => $this->faker->boolean(80), // 80% de chance de ter GPS
            'nivel_combustivel' => $this->faker->numberBetween(10, 100),
            'ponto_cuidado_id' => null, // será definido após a criação de um ponto de cuidado
            'unidade_saude_id' => null, // será definido após a criação de uma unidade de saúde
            'responsavel' => $this->faker->name,
            'contato_responsavel' => '9' . $this->faker->numerify('########') // formato angolano
        ];
    }
    
    /**
     * Definir o veículo como ambulancia.
     */
    public function ambulancia()
    {
        return $this->state(function (array $attributes) {
            return [
                'tipo' => 'ambulancia',
                'capacidade_pacientes' => $this->faker->numberBetween(2, 6),
            ];
        });
    }
    
    /**
     * Definir o veículo como disponível.
     */
    public function disponivel()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'disponivel',
            ];
        });
    }
    
    /**
     * Atribuir um ponto de cuidado ao veículo.
     */
    public function comPontoCuidado(PontoCuidado $pontoCuidado = null)
    {
        return $this->state(function (array $attributes) use ($pontoCuidado) {
            if (!$pontoCuidado) {
                $pontoCuidado = PontoCuidado::factory()->create();
            }
            
            return [
                'ponto_cuidado_id' => $pontoCuidado->id,
            ];
        });
    }
    
    /**
     * Atribuir uma unidade de saúde ao veículo.
     */
    public function comUnidadeSaude(UnidadeSaude $unidadeSaude = null)
    {
        return $this->state(function (array $attributes) use ($unidadeSaude) {
            if (!$unidadeSaude) {
                $unidadeSaude = UnidadeSaude::factory()->create();
            }
            
            return [
                'unidade_saude_id' => $unidadeSaude->id,
            ];
        });
    }
    
    /**
     * Definir o veículo como em trânsito.
     */
    public function emTransito()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'em_transito',
            ];
        });
    }
}