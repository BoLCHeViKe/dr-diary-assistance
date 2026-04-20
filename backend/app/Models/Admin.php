<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Admin extends Model
{
    protected $table = 'admins';
    protected $primaryKey = 'id';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['id', 'num_auto'];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id', 'id');
    }
}