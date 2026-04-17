<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Factura extends Model
{
    protected $table = 'factura';
    protected $primaryKey = 'num_fact';
    public $timestamps = false; // Tus migraciones no tienen timestamps en factura

    public function lineas() {
        return $this->hasMany(LineaFactura::class, 'num_fact', 'num_fact');
    }
}