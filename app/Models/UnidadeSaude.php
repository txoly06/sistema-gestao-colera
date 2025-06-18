<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @OA\Schema(
 *     schema="UnidadeSaude",
 *     title="Unidade de Saúde",
 *     description="Modelo de Unidade de Saúde",
 *     @OA\Property(property="id", type="integer", format="int64", example=1),
 *     @OA\Property(property="gabinete_provincial_id", type="integer", format="int64", example=2),
 *     @OA\Property(property="nome", type="string", example="Hospital Provincial de Luanda"),
 *     @OA\Property(property="diretor_medico", type="string", example="Dra. Ana Silva"),
 *     @OA\Property(property="tipo", type="string", example="Hospital_Geral"),
 *     @OA\Property(property="endereco", type="string", example="Av. Principal, 123"),
 *     @OA\Property(property="telefone", type="string", example="+244 923456789"),
 *     @OA\Property(property="email", type="string", format="email", example="hospital@saude.gov.ao"),
 *     @OA\Property(property="latitude", type="number", format="float", example=-8.839),
 *     @OA\Property(property="longitude", type="number", format="float", example=13.289),
 *     @OA\Property(property="capacidade", type="integer", example=200),
 *     @OA\Property(property="tem_isolamento", type="boolean", example=true),
 *     @OA\Property(property="capacidade_isolamento", type="integer", example=20),
 *     @OA\Property(property="casos_ativos", type="integer", example=45),
 *     @OA\Property(property="leitos_ocupados", type="integer", example=120),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true)
 * )
 */
class UnidadeSaude extends Model
{
    use HasFactory, SoftDeletes;
    
    /**
     * O nome da tabela associada ao modelo.
     *
     * @var string
     */
    protected $table = 'unidades_saude';
    
    /**
     * Os atributos que são atribuíveis em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'gabinete_provincial_id',
        'nome',
        'diretor_medico',
        'tipo',
        'endereco',
        'telefone',
        'email',
        'latitude',
        'longitude',
        'capacidade',
        'tem_isolamento',
        'capacidade_isolamento',
        'casos_ativos',
        'leitos_ocupados',
        'status',
        'nivel_alerta'
    ];
    
    /**
     * Os atributos que devem ser convertidos para tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'capacidade' => 'integer',
        'tem_isolamento' => 'boolean',
        'capacidade_isolamento' => 'integer',
        'casos_ativos' => 'integer',
        'leitos_ocupados' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
    
    /**
     * Obter o gabinete provincial associado à unidade de saúde.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function gabineteProvincial(): BelongsTo
    {
        return $this->belongsTo(GabineteProvincial::class, 'gabinete_provincial_id');
    }
    
    /**
     * Calcular a taxa de ocupação da unidade.
     *
     * @return float|null
     */
    public function taxaOcupacao(): ?float
    {
        if (!$this->capacidade || $this->capacidade <= 0) {
            return null;
        }
        
        return round(($this->leitos_ocupados / $this->capacidade) * 100, 2);
    }
}
