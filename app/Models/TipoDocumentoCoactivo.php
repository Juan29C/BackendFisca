<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoDocumentoCoactivo extends Model
{
    use HasFactory;

    protected $table = 'tipos_documentos_coactivo';

    protected $primaryKey = 'id_tipo_doc_coactivo';

    protected $fillable = [
        'descripcion',
    ];

    // Relaciones

    public function documentos()
    {
        return $this->hasMany(DocumentoCoactivo::class, 'id_tipo_doc_coactivo', 'id_tipo_doc_coactivo');
    }
}
