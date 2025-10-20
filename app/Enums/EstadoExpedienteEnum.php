<?php

namespace App\Enums;

enum EstadoExpedienteEnum: int
{
    case EN_PROCESO                        = 1;
    case ESPERANDO_APELACION               = 2;
    case EVALUANDO_RECONSIDERACION         = 3;
    case ELEVADO_COACTIVO                  = 4;
    case ELEVADO_GERENCIA_SEGURIDAD_CIUD   = 5;
    case ARCHIVADO                         = 6;
}
