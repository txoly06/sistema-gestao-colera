<?php

namespace App\Policies;

use App\Models\PontoCuidado;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PontoCuidadoPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('ver pontos-cuidado');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PontoCuidado $pontoCuidado): bool
    {
        return $user->can('ver pontos-cuidado');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('criar pontos-cuidado');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PontoCuidado $pontoCuidado): bool
    {
        return $user->can('editar pontos-cuidado');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PontoCuidado $pontoCuidado): bool
    {
        return $user->can('eliminar pontos-cuidado');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PontoCuidado $pontoCuidado): bool
    {
        return $user->can('editar pontos-cuidado');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PontoCuidado $pontoCuidado): bool
    {
        return $user->can('eliminar pontos-cuidado');
    }
    
    /**
     * Determine whether the user can update prontidao.
     */
    public function updateProntidao(User $user, PontoCuidado $pontoCuidado): bool
    {
        return $user->can('atualizar-prontidao pontos-cuidado');
    }

    /**
     * Determine whether the user can update capacidade.
     */
    public function updateCapacidade(User $user, PontoCuidado $pontoCuidado): bool
    {
        return $user->can('atualizar-capacidade pontos-cuidado');
    }
}
