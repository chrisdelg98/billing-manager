<?php

namespace App\Http\Controllers;

use App\Models\ServiceCatalogOption;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ServiceCatalogController extends Controller
{
    public function index(): View
    {
        $typeOptions = ServiceCatalogOption::query()
            ->ofType(ServiceCatalogOption::TYPE_SERVICE)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $providerOptions = ServiceCatalogOption::query()
            ->ofType(ServiceCatalogOption::TYPE_PROVIDER)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('catalogs.services', compact('typeOptions', 'providerOptions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $this->ensureUniqueName($data['catalog_type'], $data['name']);

        $option = ServiceCatalogOption::query()->create($data);

        AuditLogger::log('created', 'service_catalog_option', $option->id, [
            'catalog_type' => $option->catalog_type,
            'name' => $option->name,
        ]);

        return redirect()->route('catalogos.servicios.index')->with('status', 'Elemento de catalogo creado correctamente.');
    }

    public function update(Request $request, ServiceCatalogOption $catalogoServicio): RedirectResponse
    {
        $data = $this->validatedData($request, $catalogoServicio->catalog_type);
        $this->ensureUniqueName($catalogoServicio->catalog_type, $data['name'], $catalogoServicio->id);

        $catalogoServicio->update($data);

        AuditLogger::log('updated', 'service_catalog_option', $catalogoServicio->id, [
            'catalog_type' => $catalogoServicio->catalog_type,
            'name' => $catalogoServicio->name,
        ]);

        return redirect()->route('catalogos.servicios.index')->with('status', 'Elemento de catalogo actualizado correctamente.');
    }

    public function destroy(ServiceCatalogOption $catalogoServicio): RedirectResponse
    {
        $id = $catalogoServicio->id;
        $name = $catalogoServicio->name;
        $catalogType = $catalogoServicio->catalog_type;

        $catalogoServicio->delete();

        AuditLogger::log('deleted', 'service_catalog_option', $id, [
            'catalog_type' => $catalogType,
            'name' => $name,
        ]);

        return redirect()->route('catalogos.servicios.index')->with('status', 'Elemento de catalogo eliminado correctamente.');
    }

    public function reorder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'catalog_type' => ['required', 'in:'.ServiceCatalogOption::TYPE_SERVICE.','.ServiceCatalogOption::TYPE_PROVIDER],
            'ordered_ids' => ['required', 'array', 'min:1'],
            'ordered_ids.*' => ['required', 'integer', 'distinct'],
        ]);

        $existingIds = ServiceCatalogOption::query()
            ->where('catalog_type', $data['catalog_type'])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        $orderedIds = collect($data['ordered_ids'])
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($existingIds->count() !== $orderedIds->count() || $existingIds->diff($orderedIds)->isNotEmpty()) {
            return response()->json([
                'message' => 'El orden recibido no coincide con los elementos del catalogo.',
            ], 422);
        }

        DB::transaction(function () use ($data, $orderedIds): void {
            foreach ($orderedIds as $index => $optionId) {
                ServiceCatalogOption::query()
                    ->where('catalog_type', $data['catalog_type'])
                    ->whereKey($optionId)
                    ->update([
                        'sort_order' => ($index + 1) * 10,
                        'updated_at' => now(),
                    ]);
            }
        });

        AuditLogger::log('reordered', 'service_catalog_option', null, [
            'catalog_type' => $data['catalog_type'],
            'items_count' => $orderedIds->count(),
        ]);

        return response()->json([
            'message' => 'Orden actualizado correctamente.',
        ]);
    }

    private function validatedData(Request $request, ?string $forceCatalogType = null): array
    {
        $data = $request->validate([
            'catalog_type' => ['nullable', 'in:'.ServiceCatalogOption::TYPE_SERVICE.','.ServiceCatalogOption::TYPE_PROVIDER],
            'name' => ['required', 'string', 'max:120'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['catalog_type'] = $forceCatalogType ?? (string) ($data['catalog_type'] ?? '');
        $data['name'] = trim((string) $data['name']);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        return $data;
    }

    private function ensureUniqueName(string $catalogType, string $name, ?int $ignoreId = null): void
    {
        $query = ServiceCatalogOption::query()
            ->where('catalog_type', $catalogType)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)]);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'name' => 'Ya existe este valor en el catalogo seleccionado.',
            ]);
        }
    }
}
