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
        'fecha_vencimiento',
        'id_administrado',
        'id_estado',
    ];

    public $timestamps = false; 
    
    protected $casts = [
        'fecha_inicio' => 'date', 
        'fecha_vencimiento' => 'date',
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

    
}
