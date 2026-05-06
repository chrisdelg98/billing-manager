<?php

namespace App\Http\Controllers;

use App\Models\CostItem;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CostItemController extends Controller
{
    public function index(Request $request): View
    {
        $query = CostItem::query()->latest();

        if ($request->filled('q')) {
            $search = trim((string) $request->string('q'));
            $query->where('name', 'like', "%{$search}%");
        }

        $costItems = $query->paginate(10)->withQueryString();

        return view('costs.index', compact('costItems'));
    }

    public function create(): View
    {
        return view('costs.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $costItem = CostItem::query()->create($this->validatedData($request));
        AuditLogger::log('created', 'cost_item', $costItem->id, ['name' => $costItem->name]);

        return redirect()->route('costos.index')->with('status', 'Costo creado correctamente.');
    }

    public function edit(CostItem $costItem): View
    {
        return view('costs.edit', compact('costItem'));
    }

    public function update(Request $request, CostItem $costItem): RedirectResponse
    {
        $costItem->update($this->validatedData($request));
        AuditLogger::log('updated', 'cost_item', $costItem->id, ['name' => $costItem->name]);

        return redirect()->route('costos.index')->with('status', 'Costo actualizado correctamente.');
    }

    public function destroy(CostItem $costItem): RedirectResponse
    {
        $id = $costItem->id;
        $name = $costItem->name;
        $costItem->delete();

        AuditLogger::log('deleted', 'cost_item', $id, ['name' => $name]);

        return redirect()->route('costos.index')->with('status', 'Costo eliminado correctamente.');
    }

    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'category' => ['required', 'in:hosting,license,infra,other'],
            'cost_type' => ['required', 'in:direct,shared'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'billing_cycle' => ['required', 'in:monthly,yearly,custom'],
            'next_renewal_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['currency'] = strtoupper((string) $data['currency']);
        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        return $data;
    }
}
