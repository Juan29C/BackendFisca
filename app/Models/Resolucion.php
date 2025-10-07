<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resolucion extends Model
{
    use HasFactory;

    protected $table = 'resolucion';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id_expediente',
        'numero',
        'fecha',
        'lugar_emision',
        'texto',
        'id_tipo_resolucion',
    ];

    // Relaciones

    public function expediente()
    {
        return $this->belongsTo(Expediente::class, 'id_expediente', 'id');
    }

    public function tipoResolucion()
    {
        return $this->belongsTo(TipoResolucion::class, 'id_tipo_resolucion', 'id');
    }

    public function documentos()
    {
        return $this->belongsToMany(
            Documento::class,
            'resolucion_documento',
            'id_resolucion',
            'id_documento'
        );
    }
}
