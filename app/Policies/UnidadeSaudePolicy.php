<?php

namespace App\Policies;

use App\Models\UnidadeSaude;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UnidadeSaudePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('ver unidades-saude');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\UnidadeSaude  $unidadeSaude
     * @return bool
     */
    public function view(User $user, UnidadeSaude $unidadeSaude): bool
    {
        return $user->hasPermissionTo('ver unidades-saude');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('criar unidades-saude');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\UnidadeSaude  $unidadeSaude
     * @return bool
     */
    public function update(User $user, UnidadeSaude $unidadeSaude): bool
    {
        return $user->hasPermissionTo('editar unidades-saude');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\UnidadeSaude  $unidadeSaude
     * @return bool
     */
    public function delete(User $user, UnidadeSaude $unidadeSaude): bool
    {
        return $user->hasPermissionTo('eliminar unidades-saude');
    }

    public function restore(User $user, UnidadeSaude $unidadeSaude): bool
    {
        return $user->hasPermissionTo('restaurar unidades-saude');
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\UnidadeSaude  $unidadeSaude
     * @return bool
     */
    public function forceDelete(User $user, UnidadeSaude $unidadeSaude): bool
    {
        return $user->hasPermissionTo('eliminar-permanente unidades-saude');
    }
}
