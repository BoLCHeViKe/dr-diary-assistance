<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Hc extends Model
{
    protected $table = 'hc';
    protected $primaryKey = 'nhc';
    public $timestamps = false;

    protected $fillable = ['fecha_apert'];

    public function paciente()
    {
        return $this->hasOne(Paciente::class, 'nhc', 'nhc');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleHc::class, 'nhc', 'nhc');
    }
}