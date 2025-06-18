<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class Paciente extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * A tabela associada ao modelo.
     *
     * @var string
     */
    protected $table = 'pacientes';

    /**
     * Os atributos assignáveis em massa.
     *
     * @var array
     */
    protected $fillable = [
        'nome',
        'bi',
        'data_nascimento',
        'sexo',
        'telefone_encrypted',
        'endereco',
        'provincia',
        'latitude',
        'longitude',
        'qr_code',
        'email_encrypted',
        'historico_saude_encrypted',
        'grupo_sanguineo',
        'tem_alergias',
        'alergias_encrypted',
        'estado',
        'unidade_saude_id',
    ];

    /**
     * Os atributos que devem ser convertidos.
     *
     * @var array
     */
    protected $casts = [
        'data_nascimento' => 'date',
        'tem_alergias' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
        'deleted_at' => 'datetime',
    ];

    /**
     * Atributos que não devem ser retornados em arrays/JSON.
     *
     * @var array
     */
    protected $hidden = [
        'telefone_encrypted',
        'email_encrypted',
        'historico_saude_encrypted',
        'alergias_encrypted',
    ];

    /**
     * Atributos appended ao resultado.
     *
     * @var array
     */
    protected $appends = [
        'telefone',
        'email',
        'historico_saude',
        'alergias',
    ];

    /**
     * Get the decrypted phone number.
     *
     * @return string|null
     */
    public function getTelefoneAttribute()
    {
        return $this->telefone_encrypted ? Crypt::decrypt($this->telefone_encrypted) : null;
    }

    /**
     * Set the encrypted phone number.
     *
     * @param string $value
     * @return void
     */
    public function setTelefoneAttribute($value)
    {
        $this->attributes['telefone_encrypted'] = $value ? Crypt::encrypt($value) : null;
    }

    /**
     * Get the decrypted email.
     *
     * @return string|null
     */
    public function getEmailAttribute()
    {
        return $this->email_encrypted ? Crypt::decrypt($this->email_encrypted) : null;
    }

    /**
     * Set the encrypted email.
     *
     * @param string $value
     * @return void
     */
    public function setEmailAttribute($value)
    {
        $this->attributes['email_encrypted'] = $value ? Crypt::encrypt($value) : null;
    }

    /**
     * Get the decrypted historico_saude.
     *
     * @return string|null
     */
    public function getHistoricoSaudeAttribute()
    {
        return $this->historico_saude_encrypted ? Crypt::decrypt($this->historico_saude_encrypted) : null;
    }

    /**
     * Set the encrypted historico_saude.
     *
     * @param string $value
     * @return void
     */
    public function setHistoricoSaudeAttribute($value)
    {
        $this->attributes['historico_saude_encrypted'] = $value ? Crypt::encrypt($value) : null;
    }

    /**
     * Get the decrypted alergias.
     *
     * @return string|null
     */
    public function getAlergiasAttribute()
    {
        return $this->alergias_encrypted ? Crypt::decrypt($this->alergias_encrypted) : null;
    }

    /**
     * Set the encrypted alergias.
     *
     * @param string $value
     * @return void
     */
    public function setAlergiasAttribute($value)
    {
        $this->attributes['alergias_encrypted'] = $value ? Crypt::encrypt($value) : null;
    }

    /**
     * Get the unidade de saúde that the patient belongs to.
     */
    public function unidadeSaude()
    {
        return $this->belongsTo(UnidadeSaude::class, 'unidade_saude_id');
    }

    /**
     * Get the fichas clínicas for the paciente.
     */
    public function fichasClinicas()
    {
        return $this->hasMany(FichaClinica::class);
    }

    /**
     * Get the casos de cólera for the paciente.
     */
    public function casosCólera()
    {
        return $this->hasMany(CasoColera::class);
    }
}
