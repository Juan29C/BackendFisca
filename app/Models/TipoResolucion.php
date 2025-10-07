<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoResolucion extends Model
{
    use HasFactory;

    protected $table = 'tipo_resolucion';

    protected $primaryKey = 'id';

    protected $fillable = [
        'nombre',
        'descripcion',
    ];



    public function resoluciones()
    {
        return $this->hasMany(Resolucion::class, 'id_tipo_resolucion', 'id');
    }
}
