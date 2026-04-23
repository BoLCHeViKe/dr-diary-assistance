<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Models\Prestacion;

class LineaFactura extends Model
{
    protected $table = 'lineafactura';
    public $timestamps = false;

    protected $primaryKey = ['num_linea', 'num_fact'];
    public $incrementing = false;
    protected $appends = ['prestacion'];

    protected $fillable = [
        'num_fact',
        'cantidad',
        'codigo_esp',
        'id_prest',
        'precio',   // el trigger lo sobrescribe, pero debe estar aquí
        'total'     // ídem
    ];

    // Una línea pertenece a una factura
    public function factura()
    {
        return $this->belongsTo(Factura::class, 'num_fact', 'num_fact');
    }
    public function getPrestacionAttribute()
    {
        return Prestacion::where('codigo_esp', $this->codigo_esp)
                        ->where('id_prest', $this->id_prest)
                        ->first();
    }    


}