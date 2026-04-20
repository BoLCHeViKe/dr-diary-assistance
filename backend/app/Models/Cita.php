<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Cita extends Model
{
    protected $table = 'cita';
    protected $primaryKey = 'id_cita';
    public $timestamps = false;
    public $incrementing = true; // id_cita SÍ es autoincremental

    protected $fillable = [
        'id_agenda',
        'h_cita',
        'estado',
        'codigo_esp',
        'id_prest',
        'id_paciente'
    ];

    public function agenda()
    {
        return $this->belongsTo(Agenda::class, 'id_agenda', 'id_agenda');
    }

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'id_paciente', 'id_paciente');
    }

}