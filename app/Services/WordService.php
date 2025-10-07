<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\TemplateProcessor;

class WordService
{
    /**
     * Genera un DOCX desde una plantilla.
     *
     * @param string $templatePath Ruta relativa (ej: "aifis/resolucion_v1.docx")
     * @param array  $vars         Variables simples para reemplazar ${var}
     * @param array  $options      Opciones extra (ej: tablas)
     *
     * @return string URL pública del archivo generado
     */
    public function fromTemplate(string $templatePath, array $vars, array $options = []): string
    {
        $tplPath = storage_path('app/plantillas/'.$templatePath);
        if (!file_exists($tplPath)) {
            throw new \RuntimeException("Plantilla no encontrada: $templatePath");
        }

        $tp = new TemplateProcessor($tplPath);

        // Reemplazar variables simples
        foreach ($vars as $k => $v) {
            if (is_array($v) || is_object($v)) {
                $v = json_encode($v, JSON_UNESCAPED_UNICODE);
            }
            $tp->setValue($k, (string)$v);
        }

        // Campo default
        $tp->setValue('fecha', now()->format('d/m/Y'));

        // Guardar en storage/app/public/word
        $fileName  = 'doc_'.Str::random(6).'.docx';
        $publicDir = storage_path('app/public/word');
        if (!is_dir($publicDir)) mkdir($publicDir, 0775, true);
        $publicPath = $publicDir.'/'.$fileName;

        $tp->saveAs($publicPath);

        // URL pública accesible desde el front
        return Storage::url('word/'.$fileName);
    }
}
