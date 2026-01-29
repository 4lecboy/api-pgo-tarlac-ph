<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'extension',
        'email',
        'password',
        'role',
        'department',
        'position',
        'employee_id',
        'status',
        'sms_credits',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'password' => 'hashed',
        'role' => \App\Enums\UserRole::class,
    ];

    // JWT methods
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->role->value,
            'department' => $this->department,
        ];
    }

    // RBAC Helpers
    public function isSuperAdmin(): bool
    {
        return $this->role === \App\Enums\UserRole::SUPER_ADMIN;
    }

    public function isAdmin(): bool
    {
        return $this->role === \App\Enums\UserRole::ADMIN;
    }

    public function isUser(): bool
    {
        return $this->role === \App\Enums\UserRole::USER;
    }

    public function hasDepartmentAccess(string $department): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Normalize for comparison
        return strtolower($this->department ?? '') === strtolower($department);
    }
}
