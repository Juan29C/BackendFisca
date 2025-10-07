<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TiposDocumento extends Model
{
    use HasFactory;

    protected $table = 'tipos_documentos';

    protected $primaryKey = 'id';

    protected $fillable = [
        'descripcion',
    ];

    public function documentos()
    {
        return $this->hasMany(Documento::class, 'id_tipo', 'id');
    }
}
