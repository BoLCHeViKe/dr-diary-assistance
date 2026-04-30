<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    protected $table = 'rol';
    public $timestamps = false;

    protected $fillable = [
        'tipo',
        'perm_agenda', 'perm_hc', 'perm_multi_agenda',
        'perm_facturacion', 'perm_estadisticas',
        'perm_gest_roles', 'perm_gest_usuarios',
        'perm_gest_prestaciones', 'perm_gest_especialidades',
    ];

    protected $casts = [
        'perm_agenda'              => 'boolean',
        'perm_hc'                  => 'boolean',
        'perm_multi_agenda'        => 'boolean',
        'perm_facturacion'         => 'boolean',
        'perm_estadisticas'        => 'boolean',
        'perm_gest_roles'          => 'boolean',
        'perm_gest_usuarios'       => 'boolean',
        'perm_gest_prestaciones'   => 'boolean',
        'perm_gest_especialidades' => 'boolean',
    ];

    // Permisos mínimos inamovibles del rol MÉDICO (id=2)
    public const MEDICO_MIN_PERMS = [
        'perm_agenda'      => true,
        'perm_hc'          => true,
        'perm_facturacion' => true,
    ];

    public function usuarios()
    {
        return $this->hasMany(User::class, 'id_rol', 'id');
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($rol) {
            if (in_array($rol->id, [1, 2])) {
                //throw new \Exception("No se pueden eliminar los roles vitales del sistema.");
                abort(403, 'No se pueden eliminar los roles vitales del sistema.');
            }
        });
    }
}