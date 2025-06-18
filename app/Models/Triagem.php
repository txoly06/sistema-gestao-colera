<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Triagem extends Model
{
    /** @use HasFactory<\Database\Factories\TriagemFactory> */
    use HasFactory, SoftDeletes, LogsActivity;
    
    /**
     * A tabela associada ao modelo.
     *
     * @var string
     */
    protected $table = 'triagens';
    
    /**
     * Os atributos que são atribuíveis em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'paciente_id',
        'unidade_saude_id',
        'ponto_cuidado_id',
        'responsavel_id',
        'nivel_urgencia',
        'status',
        'sintomas',
        'indice_desidratacao',
        'temperatura',
        'frequencia_cardiaca',
        'frequencia_respiratoria',
        'probabilidade_colera',
        'recomendacoes',
        'observacoes',
        'encaminhamentos',
        'data_inicio_sintomas',
        'data_conclusao',
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
                'paciente_id', 'unidade_saude_id', 'ponto_cuidado_id',
                'responsavel_id', 'nivel_urgencia', 'status', 'sintomas',
                'indice_desidratacao', 'temperatura', 'probabilidade_colera',
                'recomendacoes', 'encaminhamentos', 'data_conclusao'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(function(string $eventName) {
                return "Triagem de paciente #" . $this->paciente_id . " foi {$eventName}";
            });
    }
    
    /**
     * Os atributos que devem ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'sintomas' => 'array',
        'recomendacoes' => 'array',
        'encaminhamentos' => 'array',
        'indice_desidratacao' => 'float',
        'temperatura' => 'float',
        'frequencia_cardiaca' => 'integer',
        'frequencia_respiratoria' => 'integer',
        'probabilidade_colera' => 'float',
        'data_inicio_sintomas' => 'datetime',
        'data_conclusao' => 'datetime',
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
     * Relacionamento com Unidade de Saúde
     */
    public function unidadeSaude(): BelongsTo
    {
        return $this->belongsTo(UnidadeSaude::class);
    }
    
    /**
     * Relacionamento com Ponto de Cuidado
     */
    public function pontoCuidado(): BelongsTo
    {
        return $this->belongsTo(PontoCuidado::class);
    }
    
    /**
     * Relacionamento com o responsável pela triagem (profissional de saúde)
     */
    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsavel_id');
    }
    
    /**
     * Cálculo da probabilidade de cólera baseado nos sintomas e outros fatores
     * 
     * @return float Probabilidade entre 0 e 100
     */
    public function calcularProbabilidadeColera(): float
    {
        // Implementação do algoritmo de avaliação de risco
        // Será implementado no serviço TriagemService
        return 0.0;
    }
    
    /**
     * Scope para filtrar por nível de urgência
     */
    public function scopeNivelUrgencia($query, $nivel)
    {
        return $query->where('nivel_urgencia', $nivel);
    }
    
    /**
     * Scope para filtrar por status
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }
    
    /**
     * Scope para encontrar casos suspeitos de cólera
     */
    public function scopeSuspeitaColera($query, $probabilidadeMinima = 50)
    {
        return $query->where('probabilidade_colera', '>=', $probabilidadeMinima);
    }
    
    /**
     * Scope para encontrar triagens recentes
     */
    public function scopeRecentes($query, $dias = 3)
    {
        return $query->where('created_at', '>=', now()->subDays($dias));
    }
    
    /**
     * Scope para encontrar triagens críticas que precisam de atenção
     */
    public function scopeCriticas($query)
    {
        return $query->whereIn('nivel_urgencia', ['alto', 'critico']);
    }
}
