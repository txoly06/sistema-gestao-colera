<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @OA\Schema(
 *     schema="GabineteProvincial",
 *     title="Gabinete Provincial",
 *     description="Modelo de Gabinete Provincial",
 *     @OA\Property(property="id", type="integer", format="int64", example=1),
 *     @OA\Property(property="nome", type="string", example="Gabinete Provincial de Saúde de Luanda"),
 *     @OA\Property(property="provincia", type="string", example="Luanda"),
 *     @OA\Property(property="endereco", type="string", example="Av. Revolução de Outubro, 112"),
 *     @OA\Property(property="telefone", type="string", example="+244 923456789"),
 *     @OA\Property(property="email", type="string", format="email", example="gabinete.luanda@saude.gov.ao"),
 *     @OA\Property(property="diretor", type="string", example="Dr. António Santos"),
 *     @OA\Property(property="latitude", type="number", format="float", example=-8.838333),
 *     @OA\Property(property="longitude", type="number", format="float", example=13.234444),
 *     @OA\Property(property="ativo", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true)
 * )
 */
class GabineteProvincial extends Model
{
    use HasFactory, SoftDeletes;
    
    /**
     * O nome da tabela associada ao modelo.
     *
     * @var string
     */
    protected $table = 'gabinetes_provinciais';
    
    /**
     * Os atributos que são atribuíveis em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nome',
        'provincia',
        'endereco',
        'telefone',
        'email',
        'diretor',
        'latitude',
        'longitude',
        'ativo',
    ];
    
    /**
     * Os atributos que devem ser convertidos para tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'ativo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
    
    /**
     * Os atributos que devem ser ocultos para serialização.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'deleted_at',
    ];
    
    /**
     * Definição do relacionamento com unidades de saúde.
     * Um gabinete provincial pode ter várias unidades de saúde.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function unidadesSaude()
    {
        return $this->hasMany(UnidadeSaude::class);
    }
}
