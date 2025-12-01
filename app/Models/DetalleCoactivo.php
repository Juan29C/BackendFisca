<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleCoactivo extends Model
{
    use HasFactory;

    protected $table = 'detalle_coactivo';

    protected $primaryKey = 'id_detalle';

    protected $fillable = [
        'id_coactivo',
        'res_sancion_codigo',
        'res_sancion_fecha',
        'res_consentida_codigo',
        'res_consentida_fecha',
        'papeleta_codigo',
        'papeleta_fecha',
        'tipo_papeleta',
        'codigo_infraccion',
        'descripcion_infraccion',
    ];

    protected $casts = [
        'res_sancion_fecha' => 'date',
        'res_consentida_fecha' => 'date',
        'papeleta_fecha' => 'date',
    ];

    // Relaciones

    public function coactivo()
    {
        return $this->belongsTo(Coactivo::class, 'id_coactivo', 'id_coactivo');
    }
}
