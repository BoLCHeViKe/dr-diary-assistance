<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Medico extends Model
{
    protected $table = 'medico';
    protected $primaryKey = 'id';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['id', 'num_col'];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id', 'id');
    }

    public function agendas()
    {
        return $this->hasMany(Agenda::class, 'id_med', 'id');
    }
}