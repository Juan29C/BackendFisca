<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage; 
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\TemplateProcessor;

class WordController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }


    public function generarDesdePlantilla(Request $request)
    {
        $payload = $request->validate([
            'template' => ['required', 'string'],
            'data'     => ['required', 'array'],
        ]);

        $tplPath = storage_path('app/plantillas/' . $payload['template']);
        if (!file_exists($tplPath)) {
            return response()->json(['message' => 'Plantilla no encontrada'], 404);
        }

        $tp = new TemplateProcessor($tplPath);

        foreach ($payload['data'] as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            }
            $tp->setValue($key, (string) $value);
        }
        $tp->setValue('fecha', now()->format('d/m/Y'));

        $fileName = 'doc_' . Str::random(6) . '.docx';

        // --- OPCIÓN A: guardar DIRECTO en public disk ---
        $publicDir = storage_path('app/public/word');
        if (!is_dir($publicDir)) {
            mkdir($publicDir, 0775, true);
        }
        $publicPath = $publicDir . '/' . $fileName;

        // Guardar el DOCX final en /storage/app/public/word/...
        $tp->saveAs($publicPath);

        // Construir URL pública: /storage/word/...
        $url = Storage::url('word/' . $fileName);

        return response()->json([
            'ok'  => true,
            'url' => $url,
            'file'=> $fileName,
        ]);
    }
}
