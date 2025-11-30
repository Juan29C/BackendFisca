<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EntidadBancaria extends Model
{
    use HasFactory;

    protected $table = 'entidades_bancarias';

    protected $primaryKey = 'id_entidad_bancaria';

    protected $fillable = [
        'nombre',
        'ruc',
        'direccion',
    ];
}
