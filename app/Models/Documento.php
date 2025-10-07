<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Documento extends Model
{
    use HasFactory;

    protected $table = 'documento';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id_expediente',
        'id_tipo',
        'codigo_doc',
        'fecha_doc',
        'descripcion',
    ];

    // Relaciones

    public function expediente()
    {
        return $this->belongsTo(Expediente::class, 'id_expediente', 'id');
    }

    public function tipoDocumento()
    {
        return $this->belongsTo(TiposDocumento::class, 'id_tipo', 'id');
    }

    public function resoluciones()
    {
        return $this->belongsToMany(
            Resolucion::class,
            'resolucion_documento',
            'id_documento',
            'id_resolucion'
        );
    }
}
