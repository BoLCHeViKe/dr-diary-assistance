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
}