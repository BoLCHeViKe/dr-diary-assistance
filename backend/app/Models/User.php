<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
//use Database\Factories\UserFactory;
//use Illuminate\Database\Eloquent\Attributes\Fillable;
//use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

//#[Fillable(['nombre', 'apellido1','apellido2','email','password','dni','id_rol'])]
#//[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'nombre',
        'apellido1',
        'apellido2',
        'email',
        'password',
        'dni',
        'id_rol',
    ];
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
        ];
    }
    /**
     * Obtener el nombre completo del usuario.
     * Uso: $user->full_name
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->nombre} {$this->apellido1} {$this->apellido2}";
    }
}
