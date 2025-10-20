<?php

namespace App\Models;

use App\Enums\EstadoExpedienteEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

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

    public $timestamps = false; 
    
    protected $casts = [
        'fecha_inicio' => 'date', 
        'id_estado'    => EstadoExpedienteEnum::class,
    ];

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

    public function historial()
    {
        return $this->hasMany(HistorialExpediente::class, 'id_expediente', 'id')
                    ->latest('created_at');
    }

    protected static function booted()
    {
        static::updated(function (Expediente $expediente) {
            if (!$expediente->wasChanged('id_estado')) {
                return;
            }

            $idEstadoAttr = $expediente->getAttribute('id_estado');
            $idEstado = $idEstadoAttr instanceof EstadoExpedienteEnum
                ? $idEstadoAttr->value
                : (int) $idEstadoAttr;

            $estado = $expediente->estado()->first(); 
            $nombreEstado = $estado?->nombre ?? 'Estado actualizado';

            $map = [
                'En Proceso'                                 => ['titulo' => 'En Proceso',                                 'tpl' => 'Estado cambiado a En Proceso'],
                'Esperando Apelación'                        => ['titulo' => 'Esperando Apelación',                        'tpl' => 'Queda a la espera de apelación'],
                'Evaluando Reconsideración'                  => ['titulo' => 'Evaluando Reconsideración',                  'tpl' => 'En evaluación de reconsideración'],
                'Elevado a Coactivo'                         => ['titulo' => 'Elevado a Coactivo',                         'tpl' => 'Elevado a Ejecución Coactiva'],
                'Elevado a Gerencia de Seguridad Ciudadana'  => ['titulo' => 'Elevado a Gerencia Seg. Ciudadana',         'tpl' => 'Elevado a Gerencia de Seguridad Ciudadana'],
                'Archivado'                                  => ['titulo' => 'Archivado',                                  'tpl' => 'Expediente archivado'],
            ];
            $cfg = $map[$nombreEstado] ?? ['titulo' => $nombreEstado, 'tpl' => 'Estado cambiado a '.$nombreEstado];

            // Variables opcionales desde la request (si existen)
            $metaInput = [
                'origen' => request()->input('origen'),
                'bloque' => request()->input('bloque'),
                'area'   => request()->input('area'),
                'numero' => request()->input('numero_resolucion'),
            ];

            // Render básico "plantilla"
            $descripcion = preg_replace_callback('/\:([a-zA-Z0-9_]+)/', function ($m) use ($metaInput) {
                $key = $m[1];
                return isset($metaInput[$key]) && $metaInput[$key] !== '' ? (string) $metaInput[$key] : $m[0];
            }, $cfg['tpl']);

            // Solo incluimos columnas que EXISTAN en la tabla
            $payload = [
                'id_estado' => $idEstado,
                'titulo'    => $cfg['titulo'],
            ];

            if (Schema::hasColumn('historial_expediente', 'descripcion')) {
                $payload['descripcion'] = $descripcion;
            }
            if (Schema::hasColumn('historial_expediente', 'meta')) {
                $payload['meta'] = array_filter($metaInput, fn($v) => $v !== null && $v !== '');
            }

            $expediente->historial()->create($payload);
        });
    }
}
