<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Agenda extends Model
{
    protected $table = 'agenda';
    protected $primaryKey = 'id_agenda';
    public $timestamps = false;

    protected $fillable = [
        'fecha',
        'h_inicio',
        'h_fin',
        'min_intervalo',
        'id_med'
    ];

    protected $casts = ['id_med' => 'integer'];

    public function medico()
    {
        return $this->belongsTo(Medico::class, 'id_med', 'id');
    }

    public function medicoUsuario()
    {
        return $this->belongsTo(User::class, 'id_med', 'id');
    }

    public function citas()
    {
        return $this->hasMany(Cita::class, 'id_agenda', 'id_agenda');
    }
}