<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Veiculo extends Model
{
    use HasFactory, SoftDeletes;
    
    /**
     * A tabela associada ao modelo.
     *
     * @var string
     */
    protected $table = 'veiculos';
    
    /**
     * Os atributos que são atribuíveis em massa.
     *
     * @var array
     */
    protected $fillable = [
        'placa',
        'modelo',
        'ano',
        'tipo',
        'status',
        'descricao',
        'capacidade_pacientes',
        'latitude',
        'longitude',
        'ultima_atualizacao_localizacao',
        'equipamentos',
        'equipe_medica',
        'tem_gps',
        'nivel_combustivel',
        'ponto_cuidado_id',
        'unidade_saude_id',
        'responsavel',
        'contato_responsavel',
    ];
    
    /**
     * Os atributos que devem ser convertidos.
     *
     * @var array
     */
    protected $casts = [
        'equipamentos' => 'array',
        'equipe_medica' => 'array',
        'tem_gps' => 'boolean',
        'ultima_atualizacao_localizacao' => 'datetime',
        'deleted_at' => 'datetime',
    ];
    
    /**
     * Obter o ponto de cuidado ao qual o veículo está associado.
     */
    public function pontoCuidado()
    {
        return $this->belongsTo(PontoCuidado::class);
    }
    
    /**
     * Obter a unidade de saúde à qual o veículo está associado.
     */
    public function unidadeSaude()
    {
        return $this->belongsTo(UnidadeSaude::class);
    }
    
    /**
     * Verificar se o veículo está disponível.
     */
    public function isDisponivel()
    {
        return $this->status === 'disponivel';
    }
    
    /**
     * Atualizar a localização do veículo.
     */
    public function atualizarLocalizacao($latitude, $longitude)
    {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->ultima_atualizacao_localizacao = now();
        $this->save();
        
        return $this;
    }
    
    /**
     * Atualizar o status do veículo.
     */
    public function atualizarStatus($status)
    {
        $statusesValidos = ['disponivel', 'em_transito', 'em_manutencao', 'indisponivel'];
        
        if (!in_array($status, $statusesValidos)) {
            throw new \InvalidArgumentException('Status inválido');
        }
        
        $this->status = $status;
        $this->save();
        
        return $this;
    }
}
