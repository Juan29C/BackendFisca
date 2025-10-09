<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistorialExpediente extends Model
{
    use HasFactory;

    protected $table = 'historial_expediente';

    protected $fillable = [
        'id_expediente',
        'id_estado',
        'titulo',
        'descripcion',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function expediente()
    {
        return $this->belongsTo(Expediente::class, 'id_expediente', 'id');
    }

    public function estado()
    {
        return $this->belongsTo(EstadoExpediente::class, 'id_estado', 'id');
    }
}
