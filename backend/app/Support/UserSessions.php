<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Signs a user out everywhere (database sessions + any API tokens), optionally keeping
 * the current browser session. Used after a password change so a stolen session or an
 * old device cannot stay signed in.
 */
class UserSessions
{
    public static function endOthers(User $user, ?string $keepSessionId = null): void
    {
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->getKey())
                ->when($keepSessionId, fn ($q) => $q->where('id', '!=', $keepSessionId))
                ->delete();
        }

        $user->tokens()->delete();
    }
}
