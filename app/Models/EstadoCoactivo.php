<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstadoCoactivo extends Model
{
    use HasFactory;

    protected $table = 'estados_coactivo';

    protected $primaryKey = 'id';

    protected $fillable = [
        'nombre',
        'descripcion',
    ];

    // Relaciones

    public function coactivos()
    {
        return $this->hasMany(Coactivo::class, 'estado', 'nombre');
    }
}
