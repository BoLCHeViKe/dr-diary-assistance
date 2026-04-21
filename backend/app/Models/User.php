<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
//use Database\Factories\UserFactory;
//use Illuminate\Database\Eloquent\Attributes\Fillable;
//use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

//#[Fillable(['nombre', 'apellido1','apellido2','email','password','dni','id_rol'])]
#//[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens,HasFactory, Notifiable;

    protected $fillable = [
        'nombre',
        'apellido1',
        'apellido2',
        'email',
        'password',
        'dni',
        'fecha_nac',
        'telf',
        'direccion',
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
    public function rol()
    {
        return $this->belongsTo(Rol::class, 'id_rol', 'id');
    }

    public function medico()
    {
        return $this->hasOne(Medico::class, 'id', 'id');
    }

    public function admin()
    {
        return $this->hasOne(Admin::class, 'id', 'id');
    }
}
