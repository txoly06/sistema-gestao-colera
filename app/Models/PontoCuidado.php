<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PontoCuidado extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * A tabela associada com o modelo.
     *
     * @var string
     */
    protected $table = 'ponto_cuidados';

    /**
     * Os atributos que são atribuíveis em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nome',
        'descricao',
        'endereco',
        'telefone',
        'email',
        'responsavel',
        'capacidade_maxima',
        'capacidade_atual',
        'provincia',
        'municipio',
        'latitude',
        'longitude',
        'tem_ambulancia',
        'ambulancias_disponiveis',
        'nivel_prontidao',
        'status',
        'unidade_saude_id',
    ];

    /**
     * Os atributos que devem ser convertidos.
     *
     * @var array
     */
    protected $casts = [
        'tem_ambulancia' => 'boolean',
        'capacidade_maxima' => 'integer',
        'capacidade_atual' => 'integer',
        'ambulancias_disponiveis' => 'integer',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Obtém a unidade de saúde associada ao ponto de cuidado.
     */
    public function unidadeSaude(): BelongsTo
    {
        return $this->belongsTo(UnidadeSaude::class);
    }

    /**
     * Verifica se o ponto de cuidado está ativo.
     *
     * @return bool
     */
    public function isAtivo(): bool
    {
        return $this->status === 'Ativo';
    }

    /**
     * Verifica se o ponto de cuidado está em estado de emergência.
     *
     * @return bool
     */
    public function isEmergencia(): bool
    {
        return $this->nivel_prontidao === 'Emergência';
    }

    /**
     * Verifica se o ponto de cuidado tem capacidade disponível.
     *
     * @return bool
     */
    public function temCapacidadeDisponivel(): bool
    {
        return $this->capacidade_atual < $this->capacidade_maxima;
    }
}
