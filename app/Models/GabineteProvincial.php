<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
