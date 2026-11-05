<?php

namespace App\Policies;

use App\Models\User;

/**
 * Route middleware already requires users.manage; these rules add the
 * hierarchy checks (Super Admin accounts are only editable by Super Admins).
 */
class UserPolicy
{
    public function update(User $actor, User $target): bool
    {
        return $actor->hasPermission('users.manage')
            && (! $target->isSuperAdmin() || $actor->isSuperAdmin());
    }

    public function delete(User $actor, User $target): bool
    {
        return $this->update($actor, $target) && ! $actor->is($target);
    }
}
