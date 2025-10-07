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
            'template' => ['required', 'regex:/^[a-z0-9_\-]+$/i'], // clave
            'data'     => ['required', 'array'],
        ]);

        $map = config('templates'); // clave => ruta relativa dentro de storage/app/plantillas
        $key = $payload['template'];

        if (!isset($map[$key])) {
            return response()->json(['message' => 'Plantilla no registrada'], 404);
        }

        $tplPath = storage_path('app/plantillas/' . $map[$key]);
        if (!file_exists($tplPath)) {
            return response()->json(['message' => 'Archivo de plantilla no encontrado en el servidor'], 404);
        }

        $tp = new TemplateProcessor($tplPath);

        foreach ($payload['data'] as $k => $v) {
            $tp->setValue($k, is_scalar($v) ? (string)$v : json_encode($v, JSON_UNESCAPED_UNICODE));
        }
        $tp->setValue('fecha', now()->format('d/m/Y'));

        $fileName = 'doc_' . \Illuminate\Support\Str::random(6) . '.docx';

        $publicDir = storage_path('app/public/word');
        if (!is_dir($publicDir)) {
            mkdir($publicDir, 0775, true);
        }
        $publicPath = $publicDir . '/' . $fileName;

        $tp->saveAs($publicPath);

        return response()->json([
            'ok'   => true,
            'url'  => \Illuminate\Support\Facades\Storage::url('word/' . $fileName),
            'file' => $fileName,
            // opcional: 'download_name' => "Resolucion_{$payload['data']['nro']}.docx"
        ]);
    }
}
