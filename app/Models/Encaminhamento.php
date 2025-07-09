<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Encaminhamento extends Model
{
    /** @use HasFactory<\Database\Factories\EncaminhamentoFactory> */
    use HasFactory, SoftDeletes, LogsActivity;
    
    /**
     * A tabela associada ao modelo.
     *
     * @var string
     */
    protected $table = 'encaminhamentos';
    
    /**
     * Os atributos que são atribuíveis em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'triagem_id',
        'paciente_id',
        'unidade_origem_id',
        'unidade_destino_id',
        'ponto_cuidado_origem_id',
        'ponto_cuidado_destino_id',
        'veiculo_id',
        'responsavel_id',
        'status',
        'motivo',
        'prioridade',
        'observacoes',
        'data_solicitacao',
        'previsao_partida',
        'previsao_chegada',
        'data_inicio_transporte',
        'data_chegada',
        'recursos_necessarios',
        'tipo_encaminhamento'
    ];
    
    /**
     * Configurações do Activity Log.
     *
     * @return \Spatie\Activitylog\LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'triagem_id', 'paciente_id', 'unidade_origem_id',
                'unidade_destino_id', 'veiculo_id', 'status',
                'prioridade', 'data_solicitacao', 'data_inicio_transporte',
                'data_chegada'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(function(string $eventName) {
                return "Encaminhamento de paciente #" . $this->paciente_id . " foi {$eventName}";
            });
    }
    
    /**
     * Os atributos que devem ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'recursos_necessarios' => 'array',
        'data_solicitacao' => 'datetime',
        'previsao_partida' => 'datetime',
        'previsao_chegada' => 'datetime',
        'data_inicio_transporte' => 'datetime',
        'data_chegada' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];
    
    /**
     * Relacionamento com Paciente
     */
    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }
    
    /**
     * Relacionamento com Triagem
     */
    public function triagem(): BelongsTo
    {
        return $this->belongsTo(Triagem::class);
    }
    
    /**
     * Relacionamento com UnidadeSaude de origem
     */
    public function unidadeOrigem(): BelongsTo
    {
        return $this->belongsTo(UnidadeSaude::class, 'unidade_origem_id');
    }
    
    /**
     * Relacionamento com UnidadeSaude de destino
     */
    public function unidadeDestino(): BelongsTo
    {
        return $this->belongsTo(UnidadeSaude::class, 'unidade_destino_id');
    }
    
    /**
     * Relacionamento com PontoCuidado de origem
     */
    public function pontoCuidadoOrigem(): BelongsTo
    {
        return $this->belongsTo(PontoCuidado::class, 'ponto_cuidado_origem_id');
    }
    
    /**
     * Relacionamento com PontoCuidado de destino
     */
    public function pontoCuidadoDestino(): BelongsTo
    {
        return $this->belongsTo(PontoCuidado::class, 'ponto_cuidado_destino_id');
    }
    
    /**
     * Relacionamento com Veículo
     */
    public function veiculo(): BelongsTo
    {
        return $this->belongsTo(Veiculo::class);
    }
    
    /**
     * Relacionamento com o responsável pelo encaminhamento
     */
    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsavel_id');
    }
    
    /**
     * Scope para filtrar por status
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }
    
    /**
     * Scope para encontrar encaminhamentos pendentes
     */
    public function scopePendentes($query)
    {
        return $query->whereIn('status', ['pendente', 'aprovado']);
    }
    
    /**
     * Scope para encontrar encaminhamentos em andamento
     */
    public function scopeEmAndamento($query)
    {
        return $query->where('status', 'em_transporte');
    }
    
    /**
     * Scope para filtrar por prioridade
     */
    public function scopePrioridade($query, $prioridade)
    {
        return $query->where('prioridade', $prioridade);
    }
    
    /**
     * Scope para encontrar encaminhamentos com prioridade alta
     */
    public function scopeUrgentes($query)
    {
        return $query->whereIn('prioridade', ['alta', 'emergencia']);
    }
}
