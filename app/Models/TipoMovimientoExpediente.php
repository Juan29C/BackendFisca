<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoMovimientoExpediente extends Model
{
    use HasFactory;

    protected $table = 'tipo_movimiento_expediente';

    protected $primaryKey = 'id';

    protected $fillable = [
        'nombre',
        'descripcion',
    ];
}
