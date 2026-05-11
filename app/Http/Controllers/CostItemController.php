<?php

namespace App\Http\Controllers;

use App\Models\CostItem;
use App\Models\Service;
use App\Models\ServiceCatalogOption;
use App\Models\Subscription;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CostItemController extends Controller
{
    public function index(Request $request): View
    {
        $query = CostItem::query()->latest();
        $today = now()->startOfDay();

        if ($request->filled('q')) {
            $search = trim((string) $request->string('q'));
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('cost_type', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $status = (string) $request->string('status');

            if ($status === 'active') {
                $query->where('is_active', true);
            }

            if ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if ($request->filled('category')) {
            $rawCategory = trim((string) $request->string('category'));
            $normalizedCategory = $this->normalizeCategoryInput($rawCategory);

            $query->where(function ($builder) use ($rawCategory, $normalizedCategory): void {
                $builder->where('category', $rawCategory);

                if ($normalizedCategory !== $rawCategory) {
                    $builder->orWhere('category', $normalizedCategory);
                }
            });
        }

        if ($request->filled('cost_type')) {
            $costType = (string) $request->string('cost_type');

            if (in_array($costType, ['direct', 'shared'], true)) {
                $query->where('cost_type', $costType);
            }
        }

        if ($request->filled('billing_cycle')) {
            $billingCycle = (string) $request->string('billing_cycle');

            if (in_array($billingCycle, ['monthly', 'yearly', 'custom'], true)) {
                $query->where('billing_cycle', $billingCycle);
            }
        }

        if ($request->filled('renewal_window')) {
            $window = (string) $request->string('renewal_window');

            if ($window === 'overdue') {
                $query
                    ->whereNotNull('next_renewal_at')
                    ->whereDate('next_renewal_at', '<', $today->toDateString());
            }

            if ($window === 'next_7') {
                $query
                    ->whereNotNull('next_renewal_at')
                    ->whereBetween('next_renewal_at', [
                        $today->toDateString(),
                        $today->copy()->addDays(7)->toDateString(),
                    ]);
            }

            if ($window === 'next_30') {
                $query
                    ->whereNotNull('next_renewal_at')
                    ->whereBetween('next_renewal_at', [
                        $today->toDateString(),
                        $today->copy()->addDays(30)->toDateString(),
                    ]);
            }

            if ($window === 'no_date') {
                $query->whereNull('next_renewal_at');
            }
        }

        if ($request->filled('next_from')) {
            $query->whereDate('next_renewal_at', '>=', (string) $request->string('next_from'));
        }

        if ($request->filled('next_to')) {
            $query->whereDate('next_renewal_at', '<=', (string) $request->string('next_to'));
        }

        $costItems = $query->paginate(10)->withQueryString();
        $categoryOptions = $this->costCategoryFilterOptions();

        return view('costs.index', compact('costItems', 'categoryOptions'));
    }

    public function create(): View
    {
        $currencyOptions = $this->activeCurrencyOptions();
        $categoryOptions = $this->costCategoryFilterOptions();
        $services = $this->serviceOptions();
        $subscriptions = $this->subscriptionOptions();

        return view('costs.create', compact('currencyOptions', 'categoryOptions', 'services', 'subscriptions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $costItem = CostItem::query()->create($this->validatedData($request));
        AuditLogger::log('created', 'cost_item', $costItem->id, ['name' => $costItem->name]);

        return redirect()->route('costos.index')->with('status', 'Costo creado correctamente.');
    }

    public function edit(CostItem $costItem): View
    {
        $currencyOptions = $this->activeCurrencyOptions();
        $categoryOptions = $this->costCategoryFilterOptions();
        $services = $this->serviceOptions();
        $subscriptions = $this->subscriptionOptions();

        return view('costs.edit', compact('costItem', 'currencyOptions', 'categoryOptions', 'services', 'subscriptions'));
    }

    public function update(Request $request, CostItem $costItem): RedirectResponse
    {
        $costItem->update($this->validatedData($request));

        if ($costItem->cost_type !== 'shared') {
            $costItem->allocations()->delete();
        }

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
        $request->merge([
            'currency' => strtoupper((string) $request->input('currency', '')),
            'category' => $this->normalizeCategoryInput((string) $request->input('category', '')),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'category' => [
                'required',
                'string',
                'max:120',
                Rule::exists('service_catalog_options', 'name')->where(fn ($query) => $query
                    ->where('catalog_type', ServiceCatalogOption::TYPE_COST_CATEGORY)
                    ->where('is_active', true)),
            ],
            'cost_type' => ['required', 'in:direct,shared'],
            'target_scope' => ['nullable', 'in:general,service,subscription'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'subscription_id' => ['nullable', 'integer', 'exists:subscriptions,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => [
                'required',
                'string',
                'size:3',
                Rule::exists('service_catalog_options', 'name')->where(fn ($query) => $query
                    ->where('catalog_type', ServiceCatalogOption::TYPE_CURRENCY)
                    ->where('is_active', true)),
            ],
            'billing_cycle' => ['required', 'in:monthly,yearly,custom'],
            'billing_interval_months' => ['nullable', 'integer', 'min:1', 'max:600'],
            'billing_custom_every' => ['nullable', 'integer', 'min:1', 'max:120'],
            'billing_custom_unit' => ['nullable', 'in:month,year'],
            'next_renewal_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['currency'] = strtoupper((string) $data['currency']);
        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        $data = $this->resolvedTargetScopeData($data);
        $data['billing_interval_months'] = $this->resolvedBillingIntervalMonths($data);
        unset($data['billing_custom_every'], $data['billing_custom_unit']);

        return $data;
    }

    private function normalizeCategoryInput(string $value): string
    {
        $trimmed = trim($value);

        return match (mb_strtolower($trimmed)) {
            'hosting' => 'Hosting',
            'license' => 'Licencia',
            'infra' => 'Infraestructura',
            'other' => 'Otro',
            default => $trimmed,
        };
    }

    private function resolvedBillingIntervalMonths(array $data): int
    {
        if ($data['billing_cycle'] === 'monthly') {
            return 1;
        }

        if ($data['billing_cycle'] === 'yearly') {
            return 12;
        }

        $customEvery = (int) ($data['billing_custom_every'] ?? 0);
        $customUnit = (string) ($data['billing_custom_unit'] ?? 'month');

        if ($customEvery > 0) {
            return $customUnit === 'year' ? $customEvery * 12 : $customEvery;
        }

        $intervalMonths = (int) ($data['billing_interval_months'] ?? 0);

        if ($intervalMonths < 1) {
            throw ValidationException::withMessages([
                'billing_interval_months' => 'Define cada cuantos meses se cobra este costo personalizado.',
            ]);
        }

        return $intervalMonths;
    }

    private function resolvedTargetScopeData(array $data): array
    {
        $scope = (string) ($data['target_scope'] ?? 'general');
        $scope = in_array($scope, ['general', 'service', 'subscription'], true) ? $scope : 'general';

        if ($data['cost_type'] === 'shared') {
            $data['target_scope'] = 'general';
            $data['service_id'] = null;
            $data['subscription_id'] = null;

            return $data;
        }

        if ($scope === 'service') {
            if (empty($data['service_id'])) {
                throw ValidationException::withMessages([
                    'service_id' => 'Selecciona el servicio al que aplica este costo directo.',
                ]);
            }

            $data['target_scope'] = 'service';
            $data['service_id'] = (int) $data['service_id'];
            $data['subscription_id'] = null;

            return $data;
        }

        if ($scope === 'subscription') {
            if (empty($data['subscription_id'])) {
                throw ValidationException::withMessages([
                    'subscription_id' => 'Selecciona la suscripcion a la que aplica este costo directo.',
                ]);
            }

            $data['target_scope'] = 'subscription';
            $data['service_id'] = null;
            $data['subscription_id'] = (int) $data['subscription_id'];

            return $data;
        }

        $data['target_scope'] = 'general';
        $data['service_id'] = null;
        $data['subscription_id'] = null;

        return $data;
    }

    private function activeCurrencyOptions(): Collection
    {
        return ServiceCatalogOption::query()
            ->ofType(ServiceCatalogOption::TYPE_CURRENCY)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name');
    }

    private function costCategoryFilterOptions(): Collection
    {
        $catalogOptions = ServiceCatalogOption::query()
            ->ofType(ServiceCatalogOption::TYPE_COST_CATEGORY)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name');

        $existingCategories = CostItem::query()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->orderBy('category')
            ->distinct()
            ->pluck('category');

        return $catalogOptions
            ->merge($existingCategories)
            ->map(fn ($category) => trim((string) $category))
            ->filter(fn ($category) => $category !== '')
            ->unique()
            ->values();
    }

    private function serviceOptions(): Collection
    {
        return Service::query()
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function subscriptionOptions(): Collection
    {
        return Subscription::query()
            ->with('service:id,name')
            ->orderBy('name')
            ->get(['id', 'service_id', 'name', 'is_active']);
    }
}
