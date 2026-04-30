<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Especialidad extends Model
{
    protected $table = 'especialidad';
    protected $primaryKey = 'codigo_esp';
    public $incrementing = false;
    protected $keyType = 'string'; // ← CHAR(4), no es entero
    public $timestamps = false;

    protected $fillable = [
        'codigo_esp',
        'nombre',
        'activo',
    ];

    protected $casts = ['activo' => 'boolean'];

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    public function prestaciones()
    {
        return $this->hasMany(Prestacion::class, 'codigo_esp', 'codigo_esp');
    }
}