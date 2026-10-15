<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;
    use HasRoles {
        syncRoles as protected syncRolesWithoutIntegrity;
        removeRole as protected removeRoleWithoutIntegrity;
        assignRole as protected assignRoleWithoutIntegrity;
    }

    protected $attributes = [
        'is_active' => true,
    ];

    private bool $syncingValidatedRoles = false;

    public function delete()
    {
        // HasRoles detaches pivots during deleting; validate before those events.
        \App\Services\IntegrityRules::assertDeletable($this);

        return parent::delete();
    }

    public function syncRoles(...$roles): static
    {
        $ids = $this->collectRoles(...$roles);
        \App\Services\IntegrityRules::assertRoleChange($this, $ids);

        $this->syncingValidatedRoles = true;
        try {
            return $this->syncRolesWithoutIntegrity(...$roles);
        } finally {
            $this->syncingValidatedRoles = false;
        }
    }

    public function assignRole(...$roles): static
    {
        $current = $this->exists ? $this->roles()->pluck('roles.id')->all() : [];
        $ids = array_values(array_unique(array_merge($current, $this->collectRoles(...$roles))));
        if (! $this->syncingValidatedRoles) {
            \App\Services\IntegrityRules::assertRoleChange($this, $ids);
        }

        return $this->assignRoleWithoutIntegrity(...$roles);
    }

    public function removeRole(...$roles): static
    {
        $removed = $this->collectRoles(...$roles);
        $remaining = $this->roles()->whereNotIn('roles.id', $removed)->pluck('roles.id')->all();
        if (! $this->syncingValidatedRoles) {
            \App\Services\IntegrityRules::assertRoleChange($this, $remaining);
        }

        return $this->removeRoleWithoutIntegrity(...$roles);
    }

    protected $fillable = [
        'name',
        'email',
        'locale',
        'service_setting_id',
        'branch_id',
        'password',
        'is_active',
        'last_login_at',
    ];

    public function serviceSetting(): BelongsTo
    {
        return $this->belongsTo(ServiceSetting::class, 'service_setting_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function scopeWithRoleNames(Builder $query, array $names): Builder
    {
        // Reporting must also work when an operational role has been removed.
        return $query->whereHas('roles', fn (Builder $roles) => $roles
            ->where('guard_name', 'web')->whereIn('name', $names));
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->is_active) {
            if (\Illuminate\Support\Facades\Auth::check() && \Illuminate\Support\Facades\Auth::id() === $this->id) {
                \Illuminate\Support\Facades\Auth::logout();
                if (session()->isStarted()) {
                    session()->invalidate();
                    session()->regenerateToken();
                }
            }
            return false;
        }

        if ($this->hasAnyRole(['admin', 'cs', 'teller'])) {
            return true;
        }

        return $this->can('access_admin_panel') || $this->can('view_any_queue');
    }

    public function canImpersonate(): bool
    {
        return $this->is_active && $this->hasRole('admin')
            && ! \STS\FilamentImpersonate\Facades\Impersonation::isImpersonating();
    }

    public function canBeImpersonated(): bool
    {
        return (bool) $this->is_active;
    }
}
