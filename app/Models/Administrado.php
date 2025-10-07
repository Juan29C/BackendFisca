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
        'nombre_completo',
        'razon_social',
        'domicilio',
        'vinculo',
    ];


    public function expedientes()
    {
        return $this->hasMany(Expediente::class, 'id_administrado', 'id');
    }
}
