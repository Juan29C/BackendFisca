<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstadoExpediente extends Model
{
    use HasFactory;

    protected $table = 'estado_expediente';

    protected $primaryKey = 'id';

    protected $fillable = [
        'nombre',
        'descripcion',
    ];


    public function expedientes()
    {
        return $this->hasMany(Expediente::class, 'id_estado', 'id');
    }
}
