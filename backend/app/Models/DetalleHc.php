<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class DetalleHc extends Model
{
    protected $table = 'detallehc';
    protected $primaryKey = ['num_orden', 'nhc'];
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'num_orden',// Lo añadimos por si acaso, aunque lo gestione el trigger
        'nhc',
        'id_cita',// <-- ¡FUNDAMENTAL añadirlo aquí!
        'tto',
        'f_consulta',
        'sinto',
        'diag'
    ];

    public function hc()
    {
        return $this->belongsTo(Hc::class, 'nhc', 'nhc');
    }
    public function cita()
    {
        return $this->belongsTo(Cita::class, 'id_cita', 'id_cita');
    }

}