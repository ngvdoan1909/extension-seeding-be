<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'password',
        'point',
        'role'
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'point' => $this->getPointAttribute(),
            'email' => $this->email,
        ];
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function deposits()
    {
        return $this->hasMany(Deposit::class, 'user_id', 'user_id');
    }

    public function withdraws()
    {
        return $this->hasMany(WithDraw::class, 'user_id', 'user_id');
    }

    public function getPointAttribute()
    {
        $deposit = $this->deposits()->sum('amount');
        $withdraw = $this->withdraws()->sum('amount');
        return $deposit - $withdraw;
    }
}
