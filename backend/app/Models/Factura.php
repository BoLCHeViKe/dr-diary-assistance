<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Factura extends Model
{
    protected $table = 'factura';
    protected $primaryKey = 'num_fact';
    public $timestamps = false;

    protected $fillable = [
        'fecha',
        'estado',
        'id_paciente',
        'fact_ref'  // nullable, solo para abonos
    ];

    // Una factura tiene muchas líneas
    public function lineas()
    {
        return $this->hasMany(LineaFactura::class, 'num_fact', 'num_fact');
    }

    // Una factura pertenece a un paciente
    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'id_paciente', 'id_paciente');
    }

    // Si es un abono, referencia a la factura original
    public function facturaOriginal()
    {
        return $this->belongsTo(Factura::class, 'fact_ref', 'num_fact');
    }

    // Una factura puede tener abonos asociados
    public function abonos()
    {
        return $this->hasMany(Factura::class, 'fact_ref', 'num_fact');
    }
}