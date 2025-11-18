<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentoCoactivo extends Model
{
    use HasFactory;

    protected $table = 'documento_coactivo';

    protected $primaryKey = 'id_doc_coactivo';

    protected $fillable = [
        'id_coactivo',
        'id_tipo_doc_coactivo',
        'codigo_doc',
        'fecha_doc',
        'descripcion',
        'ruta',
    ];

    protected $casts = [
        'fecha_doc' => 'date',
    ];

    // Relaciones

    public function coactivo()
    {
        return $this->belongsTo(Coactivo::class, 'id_coactivo', 'id_coactivo');
    }

    public function tipoDocumento()
    {
        return $this->belongsTo(TipoDocumentoCoactivo::class, 'id_tipo_doc_coactivo', 'id_tipo_doc_coactivo');
    }
}
