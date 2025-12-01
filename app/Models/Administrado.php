<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Administrado extends Model
{
    use HasFactory;

    protected $table = 'administrado';

    protected $primaryKey = 'id';

    protected $fillable = [
        'tipo',
        'dni',
        'ruc',
        'nombres',       
        'apellidos',
        'razon_social',
        'domicilio',
        'telefono',
        'email',
        'vinculo',
    ];

    public $timestamps = false; 


    public function expedientes()
    {
        return $this->hasMany(Expediente::class, 'id_administrado', 'id');
    }
}
