<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResolucionDocumento extends Model
{
    use HasFactory;

    protected $table = 'resolucion_documento';

    public $incrementing = false;
    public $timestamps = false;

    protected $primaryKey = null;

    protected $fillable = [
        'id_resolucion',
        'id_documento',
    ];
}
