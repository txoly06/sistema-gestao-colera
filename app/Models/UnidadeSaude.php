<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
