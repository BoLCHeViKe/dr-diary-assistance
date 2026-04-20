<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prestacion extends Model
{
    protected $table = 'prestacion';
    
    // IMPORTANTE: Eloquent no soporta claves compuestas para find(), 
    // pero ponerlas aquí ayuda a documentar. 
    protected $primaryKey = ['codigo_esp', 'id_prest'];
    
    public $incrementing = false;
    public $timestamps = false;

    // AÑADE ESTA LÍNEA: Indica que la clave no es un número correlativo
    protected $keyType = 'string';

    protected $fillable = [
        'codigo_esp',
        'id_prest',
        'nombre',
        'descripcion',
        'precio'
    ];

    /**
     * Relación con Especialidad
     */
    public function especialidad()
    {
        return $this->belongsTo(Especialidad::class, 'codigo_esp', 'codigo_esp');
    }

}