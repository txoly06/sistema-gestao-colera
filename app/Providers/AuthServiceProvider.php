<?php

namespace App\Providers;

use App\Models\GabineteProvincial;
use App\Models\Paciente;
use App\Models\PontoCuidado;
use App\Models\UnidadeSaude;
use App\Policies\GabineteProvincialPolicy;
use App\Policies\PacientePolicy;
use App\Policies\PontoCuidadoPolicy;
use App\Policies\UnidadeSaudePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * As mapeamentos de políticas para a aplicação.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        GabineteProvincial::class => GabineteProvincialPolicy::class,
        UnidadeSaude::class => UnidadeSaudePolicy::class,
        Paciente::class => PacientePolicy::class,
        PontoCuidado::class => PontoCuidadoPolicy::class,
    ];

    /**
     * Registrar serviços de autenticação/autorização.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Define um gate 'administrador' para verificação rápida
        Gate::define('administrador', function ($user) {
            return $user->hasRole('Administrador');
        });

        // Define um gate 'gestor' para verificação rápida
        Gate::define('gestor', function ($user) {
            return $user->hasRole('Gestor') || $user->hasRole('Administrador');
        });

        // Define um gate 'profissional-saude' para verificação rápida
        Gate::define('profissional-saude', function ($user) {
            return $user->hasRole('Profissional de Saúde') || $user->hasRole('Gestor') || $user->hasRole('Administrador');
        });
    }
}
