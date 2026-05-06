<?php

namespace App\Http\Controllers;

use App\Models\CostItem;
use App\Models\Service;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CostAllocationController extends Controller
{
    public function edit(CostItem $costItem): View|RedirectResponse
    {
        if ($costItem->cost_type !== 'shared') {
            return redirect()->route('costos.index')->with('status', 'Solo los costos compartidos admiten asignaciones por servicio.');
        }

        $services = Service::query()->orderBy('name')->get(['id', 'name']);

        $allocations = $costItem->allocations()
            ->where('is_active', true)
            ->orderBy('service_id')
            ->get()
            ->keyBy('service_id');

        $selectedServiceIds = $allocations->keys()->map(fn ($id) => (int) $id)->all();
        $allocationMode = old('allocation_mode', $allocations->first()->allocation_mode ?? 'equal');

        return view('costs.allocations', compact(
            'costItem',
            'services',
            'allocations',
            'selectedServiceIds',
            'allocationMode',
        ));
    }

    public function update(Request $request, CostItem $costItem): RedirectResponse
    {
        if ($costItem->cost_type !== 'shared') {
            return redirect()->route('costos.index')->with('status', 'Solo los costos compartidos admiten asignaciones por servicio.');
        }

        $data = $request->validate([
            'allocation_mode' => ['required', 'in:equal,weight'],
            'service_ids' => ['required', 'array', 'min:1'],
            'service_ids.*' => ['required', 'integer', 'exists:services,id'],
            'weights' => ['nullable', 'array'],
            'weights.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $serviceIds = collect($data['service_ids'])->map(fn ($id) => (int) $id)->unique()->values();

        if ($data['allocation_mode'] === 'weight') {
            foreach ($serviceIds as $serviceId) {
                $weight = (float) ($data['weights'][$serviceId] ?? 0);

                if ($weight <= 0) {
                    throw ValidationException::withMessages([
                        "weights.{$serviceId}" => 'Cada servicio seleccionado debe tener un peso mayor a 0.',
                    ]);
                }
            }
        }

        DB::transaction(function () use ($costItem, $serviceIds, $data): void {
            $costItem->allocations()->whereNotIn('service_id', $serviceIds)->delete();

            foreach ($serviceIds as $serviceId) {
                $costItem->allocations()->updateOrCreate(
                    ['service_id' => $serviceId],
                    [
                        'allocation_mode' => $data['allocation_mode'],
                        'weight' => $data['allocation_mode'] === 'weight'
                            ? (float) ($data['weights'][$serviceId] ?? 0)
                            : null,
                        'is_active' => true,
                    ]
                );
            }
        });

        AuditLogger::log('updated', 'cost_allocation', $costItem->id, [
            'allocation_mode' => $data['allocation_mode'],
            'services_count' => $serviceIds->count(),
        ]);

        return redirect()
            ->route('costos.index')
            ->with('status', 'Asignaciones del costo compartido actualizadas correctamente.');
    }
}
