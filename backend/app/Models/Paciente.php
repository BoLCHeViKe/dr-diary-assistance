<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Paciente extends Model
{
    protected $table = 'paciente';
    protected $primaryKey = 'id_paciente';
    public $timestamps = false;

    protected $fillable = [
        'dni',
        'nombre',
        'apellido1',
        'apellido2',
        'fecha_nac',
        'telf',
        'nhc'
    ];

    public function hc()
    {
        return $this->belongsTo(Hc::class, 'nhc', 'nhc');
    }

    public function facturas()
    {
        return $this->hasMany(Factura::class, 'id_paciente', 'id_paciente');
    }

    public function citas()
    {
        return $this->hasMany(Cita::class, 'id_paciente', 'id_paciente');
    }
}