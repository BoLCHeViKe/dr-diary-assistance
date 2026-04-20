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
        'nhc',
        'tto',
        'f_consulta',
        'sinto',
        'diag'
    ];

    public function hc()
    {
        return $this->belongsTo(Hc::class, 'nhc', 'nhc');
    }
}