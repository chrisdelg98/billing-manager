<?php

namespace App\Http\Controllers;

use App\Models\ServiceCatalogOption;
use App\Models\Service;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(Request $request): View
    {
        $query = Service::query()->latest();

        if ($request->filled('q')) {
            $search = trim((string) $request->string('q'));
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('provider', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%")
                    ->orWhere('owner_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $status = (string) $request->string('status');

            if (in_array($status, ['active', 'paused', 'archived'], true)) {
                $query->where('status', $status);
            }
        }

        if ($request->filled('type')) {
            $query->where('type', trim((string) $request->string('type')));
        }

        if ($request->filled('provider')) {
            $query->where('provider', trim((string) $request->string('provider')));
        }

        if ($request->filled('owner_name')) {
            $owner = trim((string) $request->string('owner_name'));
            $query->where('owner_name', 'like', "%{$owner}%");
        }

        $services = $query->paginate(10)->withQueryString();
        $typeOptions = $this->filterOptionsFor('type', ServiceCatalogOption::TYPE_SERVICE);
        $providerOptions = $this->filterOptionsFor('provider', ServiceCatalogOption::TYPE_PROVIDER);

        return view('services.index', compact('services', 'typeOptions', 'providerOptions'));
    }

    public function create(): View
    {
        return view('services.create', $this->catalogOptions());
    }

    public function store(Request $request): RedirectResponse
    {
        $service = Service::query()->create($this->validatedData($request));
        AuditLogger::log('created', 'service', $service->id, ['name' => $service->name]);

        return redirect()->route('servicios.index')->with('status', 'Servicio creado correctamente.');
    }

    public function edit(Service $service): View
    {
        return view('services.edit', array_merge(compact('service'), $this->catalogOptions()));
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $service->update($this->validatedData($request));
        AuditLogger::log('updated', 'service', $service->id, ['name' => $service->name]);

        return redirect()->route('servicios.index')->with('status', 'Servicio actualizado correctamente.');
    }

    public function destroy(Service $service): RedirectResponse
    {
        if ($service->subscriptions()->exists() || $service->payments()->exists()) {
            return redirect()->route('servicios.index')->withErrors([
                'delete' => 'No puedes eliminar un servicio con suscripciones o pagos asociados.',
            ]);
        }

        $name = $service->name;
        $id = $service->id;
        $service->delete();

        AuditLogger::log('deleted', 'service', $id, ['name' => $name]);

        return redirect()->route('servicios.index')->with('status', 'Servicio eliminado correctamente.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['nullable', 'string', 'max:80'],
            'provider' => ['nullable', 'string', 'max:120'],
            'status' => ['required', 'in:active,paused,archived'],
            'owner_name' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function catalogOptions(): array
    {
        return [
            'typeOptions' => $this->activeCatalogNames(ServiceCatalogOption::TYPE_SERVICE),
            'providerOptions' => $this->activeCatalogNames(ServiceCatalogOption::TYPE_PROVIDER),
        ];
    }

    private function activeCatalogNames(string $catalogType): Collection
    {
        return ServiceCatalogOption::query()
            ->ofType($catalogType)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name');
    }

    private function filterOptionsFor(string $column, string $catalogType): Collection
    {
        $existingValues = Service::query()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->orderBy($column)
            ->distinct()
            ->pluck($column);

        return $existingValues
            ->merge($this->activeCatalogNames($catalogType))
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => $value !== '')
            ->unique()
            ->values();
    }
}
