<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    /**
     * Cuando cambie el estado del expediente, registramos una entrada en historial.
     * Puedes llamar a este hook desde un Observer si prefieres separar responsabilidades.
     */
    protected static function booted()
    {
        static::updated(function (Expediente $expediente) {
            if ($expediente->wasChanged('id_estado')) {
                $estado = $expediente->estado()->first();

                $map = [
                    'Recibido'    => ['titulo' => 'Recepción del expediente', 'tpl' => 'Recepción desde :origen - Bloque :bloque'],
                    'En trámite'  => ['titulo' => 'Expediente en trámite',     'tpl' => 'El expediente avanza a "En trámite"'],
                    'Derivado'    => ['titulo' => 'Derivación',                'tpl' => 'Derivado al área :area'],
                    'Resolución'  => ['titulo' => 'Emisión de Resolución',     'tpl' => 'Se emitió resolución :numero'],
                    'Cerrado'     => ['titulo' => 'Cierre de expediente',      'tpl' => 'Expediente cerrado'],
                ];

                $nombreEstado = $estado?->nombre ?? 'Estado actualizado';
                $cfg = $map[$nombreEstado] ?? ['titulo' => $nombreEstado, 'tpl' => 'Estado cambiado a '.$nombreEstado];

                $meta = [
                    'origen' => request()->input('origen'),      
                    'bloque' => request()->input('bloque'),       
                    'area'   => request()->input('area'),
                    'numero' => request()->input('numero_resolucion'),
                ];

                $descripcion = self::renderTpl($cfg['tpl'], $meta);

                $expediente->historial()->create([
                    'id_estado'  => $expediente->id_estado,
                    'titulo'     => $cfg['titulo'],
                    'descripcion'=> $descripcion,
                    'meta'       => array_filter($meta, fn($v) => !is_null($v) && $v !== ''),
                ]);
            }
        });
    }

    protected static function renderTpl(string $tpl, array $vars = []): string
    {
        return preg_replace_callback('/\:([a-zA-Z0-9_]+)/', function ($m) use ($vars) {
            $key = $m[1];
            return isset($vars[$key]) && $vars[$key] !== '' ? (string)$vars[$key] : $m[0];
        }, $tpl);
    }
}
