<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, LogsActivity, Notifiable, SoftDeletes;

    protected $fillable = ['role_id', 'name', 'email', 'phone', 'avatar', 'password', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

    protected $attributes = ['is_active' => true];

    /** Per-request memo of permission slugs. */
    protected ?array $permissionCache = null;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
            'notification_preferences' => 'array',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function counsellor(): HasOne
    {
        return $this->hasOne(Counsellor::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role?->slug === Role::SUPER_ADMIN;
    }

    /** @return list<string> */
    public function permissionSlugs(): array
    {
        if ($this->permissionCache === null) {
            $this->permissionCache = $this->isSuperAdmin()
                ? Permission::query()->pluck('slug')->all()
                : ($this->role?->permissions()->pluck('slug')->all() ?? []);
        }

        return $this->permissionCache;
    }

    public function hasPermission(string $slug): bool
    {
        return $this->isSuperAdmin() || in_array($slug, $this->permissionSlugs(), true);
    }
}
