<?php

namespace App\Policies;

use App\Models\GabineteProvincial;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\Access\HandlesAuthorization;

class GabineteProvincialPolicy
{
    use HandlesAuthorization;

    /**
     * Determina se o usuário pode ver qualquer modelo (lista de gabinetes provinciais).
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('gabinetes.listar');
    }

    /**
     * Determina se o usuário pode ver o modelo (detalhes do gabinete provincial).
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\GabineteProvincial  $gabineteProvincial
     * @return bool
     */
    public function view(User $user, GabineteProvincial $gabineteProvincial): bool
    {
        return $user->hasPermissionTo('gabinetes.visualizar');
    }

    /**
     * Determina se o usuário pode criar modelos (novos gabinetes provinciais).
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('gabinetes.criar');
    }

    /**
     * Determina se o usuário pode atualizar o modelo (editar gabinete provincial).
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\GabineteProvincial  $gabineteProvincial
     * @return bool
     */
    public function update(User $user, GabineteProvincial $gabineteProvincial): bool
    {
        return $user->hasPermissionTo('gabinetes.editar');
    }

    /**
     * Determina se o usuário pode excluir o modelo (soft delete do gabinete provincial).
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\GabineteProvincial  $gabineteProvincial
     * @return bool
     */
    public function delete(User $user, GabineteProvincial $gabineteProvincial): bool
    {
        return $user->hasPermissionTo('gabinetes.eliminar');
    }

    /**
     * Determina se o usuário pode restaurar o modelo (restaurar gabinete provincial excluído).
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\GabineteProvincial  $gabineteProvincial
     * @return bool
     */
    public function restore(User $user, GabineteProvincial $gabineteProvincial): bool
    {
        return $user->hasPermissionTo('gabinetes.editar') && 
               $user->hasPermissionTo('gabinetes.eliminar');
    }

    /**
     * Determina se o usuário pode excluir permanentemente o modelo (exclusão permanente).
     * Apenas administradores devem ter essa permissão.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\GabineteProvincial  $gabineteProvincial
     * @return bool
     */
    public function forceDelete(User $user, GabineteProvincial $gabineteProvincial): bool
    {
        return $user->hasRole('Administrador');
    }

    /**
     * Determina se o usuário pode exportar dados de gabinetes provinciais.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function export(User $user): bool
    {
        return $user->hasPermissionTo('relatorios.exportar') && 
               $user->hasPermissionTo('gabinetes.listar');
    }
}
