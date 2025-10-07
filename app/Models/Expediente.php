<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expediente extends Model
{
    use HasFactory;

    protected $table = 'expediente';

    protected $primaryKey = 'id';

    protected $fillable = [
        'codigo_expediente',
        'fecha_inicio',
        'id_administrado',
        'id_estado',
    ];

    // Relaciones

    public function administrado()
    {
        return $this->belongsTo(Administrado::class, 'id_administrado', 'id');
    }

    public function estado()
    {
        return $this->belongsTo(EstadoExpediente::class, 'id_estado', 'id');
    }

    public function documentos()
    {
        return $this->hasMany(Documento::class, 'id_expediente', 'id');
    }

    public function resoluciones()
    {
        return $this->hasMany(Resolucion::class, 'id_expediente', 'id');
    }
}
