<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    protected $table = 'rol';
    public $timestamps = false;

    protected $fillable = ['tipo'];

    public function usuarios()
    {
        return $this->hasMany(User::class, 'id_rol', 'id');
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($rol) {
            if (in_array($rol->id, [1, 2])) {
                //throw new \Exception("No se pueden eliminar los roles vitales del sistema.");
                abort(403, 'No se pueden eliminar los roles vitales del sistema.');
            }
        });
    }
}