<?php

namespace App\Http\Controllers;

use App\Services\TiposDocumentoService;
use App\Http\Resources\TiposDocumentoResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TiposDocumentoController extends Controller
{
    protected TiposDocumentoService $service;

    public function __construct(TiposDocumentoService $service)
    {
        $this->service = $service;
    }

    /**
     * GET /api/tipos-documentos
     */
    public function index(): AnonymousResourceCollection
    {
        $tipos = $this->service->getAll(); // ya viene ordenado desde el repo
        return TiposDocumentoResource::collection($tipos);
    }
}
