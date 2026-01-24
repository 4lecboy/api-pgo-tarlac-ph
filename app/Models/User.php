<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable implements JWTSubject
{
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
    ];

    protected $hidden = [
        'password',
    ];

    // JWT methods
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
