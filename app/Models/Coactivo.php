<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coactivo extends Model
{
    use HasFactory;

    protected $table = 'coactivos';

    protected $primaryKey = 'id_coactivo';

    protected $fillable = [
        'codigo_expediente_coactivo',
        'id_expediente',
        'ejecutor_coactivo',
        'auxiliar_coactivo',
        'fecha_inicio',
        'monto_deuda',
        'monto_costas',
        'monto_gastos_admin',
        'monto_pagado',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'monto_deuda' => 'decimal:2',
        'monto_costas' => 'decimal:2',
        'monto_gastos_admin' => 'decimal:2',
        'monto_pagado' => 'decimal:2',
    ];

    // Relaciones

    public function expediente()
    {
        return $this->belongsTo(Expediente::class, 'id_expediente', 'id');
    }

    public function estadoCoactivo()
    {
        return $this->belongsTo(EstadoCoactivo::class, 'estado', 'nombre');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleCoactivo::class, 'id_coactivo', 'id_coactivo');
    }

    public function documentos()
    {
        return $this->hasMany(DocumentoCoactivo::class, 'id_coactivo', 'id_coactivo');
    }
}
