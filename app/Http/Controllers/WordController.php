<?php

namespace App\Http\Controllers;

use App\Services\DocumentoService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\TemplateProcessor;

class WordController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function __construct(private DocumentoService $docs) {}


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

    public function listarPlantillas()
    {
        $map = config('templates');
        $items = collect($map)->map(fn($file, $key) => [
            'key'          => $key,
            'filename'     => $file,
            'display_name' => ucfirst($key),
        ])->values();

        return response()->json($items);
    }


    public function generarDesdePlantilla(Request $request)
    {
        $data = $request->validate([
            'template'       => ['required', 'regex:/^[a-z0-9_\-]+$/i'],
            'codigo_titulo'  => ['nullable', 'integer'], 
            'titulo'         => ['nullable', 'string'],  
            'descripcion'    => ['nullable', 'string'], 
            'id_visto'       => ['nullable', 'integer'], 
            'fecha_emision'  => ['nullable', 'string'],
        ]);

        $url = $this->docs->generar($data['template'], $data);

        return response()->json(['ok' => true, 'url' => $url], 201);
    }
    
}
