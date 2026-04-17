<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class LineaFactura extends Model
{
    protected $table = 'lineafactura';
    public $timestamps = false;
    // Como es clave primaria compuesta, Eloquent necesita ayuda:
    protected $primaryKey = null;
    public $incrementing = false;
}