<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sintoma extends Model
{
    /** @use HasFactory<\Database\Factories\SintomaFactory> */
    use HasFactory;
    
    /**
     * A tabela associada ao modelo.
     *
     * @var string
     */
    protected $table = 'sintomas';
    
    /**
     * Os atributos que são atribuíveis em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nome',
        'descricao',
        'gravidade',
        'especifico_colera',
        'categoria',
        'sintomas_relacionados'
    ];
    
    /**
     * Os atributos que devem ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'gravidade' => 'integer',
        'especifico_colera' => 'boolean',
        'sintomas_relacionados' => 'array',
    ];
    
    /**
     * Scope para encontrar apenas sintomas específicos de cólera.
     */
    public function scopeEspecificoColera($query)
    {
        return $query->where('especifico_colera', true);
    }
    
    /**
     * Scope para filtrar por categoria de sintomas.
     */
    public function scopeFiltrarPorCategoria($query, $categoria)
    {
        return $query->where('categoria', $categoria);
    }
    
    /**
     * Scope para filtrar por gravidade mínima.
     */
    public function scopeGravidadeMinima($query, $gravidadeMinima)
    {
        return $query->where('gravidade', '>=', $gravidadeMinima);
    }
    
    /**
     * Obter sintomas por categorias específicas.
     * 
     * @param array $categorias Lista de categorias
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function porCategorias(array $categorias)
    {
        return static::whereIn('categoria', $categorias)->get();
    }
    
    /**
     * Obter sintomas de alto risco para cólera (especifico_colera=true e gravidade>=4).
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function altoRiscoParaColera()
    {
        return static::where('especifico_colera', true)
                   ->where('gravidade', '>=', 4)
                   ->orderBy('gravidade', 'desc')
                   ->get();
    }
    /**
     * Scope para ordenar por gravidade (descendente).
     */
    public function scopeOrdenarPorGravidade($query)
    {
        return $query->orderBy('gravidade', 'desc');
    }
}
