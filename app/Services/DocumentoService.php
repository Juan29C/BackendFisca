<?php

namespace App\Services;

use App\Models\Documento;
use App\Repositories\DocumentoRepository;
use App\Repositories\ResolucionRepository;
use Illuminate\Database\Eloquent\Collection;

class DocumentoService
{
    public function __construct(
        private ResolucionRepository $repo,
        private WordService $word,
        private DocumentoRepository $repository
    ) {}

    public function getAll(): Collection
    {
        return $this->repository->listAll();
    }

    public function getById(int $id): ?Documento
    {
        return $this->repository->find($id);
    }

    public function create(array $data): Documento
    {
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): ?Documento
    {
        return $this->repository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }

    public function generar(string $templateKey, array $payload): string
    {
        $map = config('templates');
        if (!isset($map[$templateKey])) {
            throw new \InvalidArgumentException('Plantilla no registrada.');
        }
        $templatePath = $map[$templateKey];

        // 1) Variables para la plantilla
        $vars = [];

        // 1a) TÍTULO por SP si envían 'codigo_titulo'; fallback si mandan 'titulo'
        if (!empty($payload['codigo_titulo'])) {
            $vars['titulo'] = $this->repo->numeroResolucionSimple((int)$payload['codigo_titulo']);
        } elseif (!empty($payload['titulo'])) {
            $vars['titulo'] = (string)$payload['titulo'];
        }

        // 1b) DESCRIPCIÓN (hoy por request; mañana por SP usando descripcionVisto())
        if (!empty($payload['descripcion'])) {
            $vars['descripcion'] = (string)$payload['descripcion'];
        } elseif (!empty($payload['id_visto'])) {
            $vars['descripcion'] = $this->repo->descripcionVisto((int)$payload['id_visto']) ?? '';
        }

        // 1c) Otros campos que quieras mapear
        if (!empty($payload['fecha_emision'])) {
            $vars['fecha_emision'] = (string)$payload['fecha_emision'];
        }

        // 2) (Opcional) tablas/bloques repetibles a futuro
        $options = [];
        // $options['tabla_detalle'] = $payload['detalle'] ?? [];

        // 3) Generar y devolver URL pública
        return $this->word->fromTemplate($templatePath, $vars, $options);
    }
}