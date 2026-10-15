<?php

namespace App\Models;

use App\Services\IntegrityRules;
use Illuminate\Validation\ValidationException;

class Role extends \Spatie\Permission\Models\Role
{
    public static function create(array $attributes = [])
    {
        if (blank($attributes['name'] ?? null) && filled($attributes['display_name'] ?? null)) {
            $role = new static($attributes);
            $role->save();

            return $role;
        }

        return parent::create($attributes);
    }

    public function getDisplayNameAttribute(?string $value): string
    {
        return filled($value) ? $value : match ($this->name) {
            'cs' => 'Customer Service',
            'teller' => 'Teller',
            'admin' => 'Administrator',
            'admin_cabang' => 'Admin Cabang',
            default => ucfirst(str_replace('_', ' ', (string) $this->name)),
        };
    }

    public function delete()
    {
        // Guard before Spatie's deleting events detach user/permission pivots.
        IntegrityRules::assertDeletable($this);

        return parent::delete();
    }

    public function save(array $options = [])
    {
        if (! $this->exists && blank($this->name) && filled($this->getAttributes()['display_name'] ?? null)) {
            $base = \Illuminate\Support\Str::limit(\Illuminate\Support\Str::slug($this->display_name, '_'), 200, '') ?: 'role';
            $code = $base;
            for ($suffix = 2; static::where('name', $code)->where('guard_name', $this->guard_name)->exists(); $suffix++) {
                $code = $base.'_'.$suffix;
            }
            $this->name = $code;
        }
        if ($this->exists && $this->isDirty(['name', 'guard_name']) && IntegrityRules::isSystemRole($this)) {
            throw ValidationException::withMessages(['name' => 'Nama dan guard peran sistem tidak dapat diubah.']);
        }
        if ($this->exists && $this->isDirty('guard_name') && IntegrityRules::deletionReason($this)) {
            throw ValidationException::withMessages(['guard_name' => 'Guard peran yang masih dipakai tidak dapat diubah.']);
        }

        return parent::save($options);
    }
}
