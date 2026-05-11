<?php

namespace App\Http\Controllers;

use App\Models\CostItem;
use App\Models\MonthlySnapshot;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Subscription;
use App\Support\AuditLogger;
use App\Support\FinanceSnapshotService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class FinanceController extends Controller
{
    public function index(Request $request, FinanceSnapshotService $snapshotService): View
    {
        $period = (string) $request->input('period', now()->format('Y-m'));

        try {
            $periodStart = Carbon::createFromFormat('Y-m', $period)->startOfMonth();
        } catch (\Throwable) {
            $periodStart = now()->startOfMonth();
            $period = $periodStart->format('Y-m');
        }

        $periodEnd = $periodStart->copy()->endOfMonth();
        $projectionDate = $periodStart->copy()->startOfDay();

        $filters = [
            'q' => trim((string) $request->input('q', '')),
            'service_status' => $this->resolveEnumFilter((string) $request->input('service_status', 'all'), ['all', 'active', 'paused', 'archived'], 'all'),
            'profitability' => $this->resolveEnumFilter((string) $request->input('profitability', 'all'), ['all', 'positive', 'negative', 'breakeven', 'with_income', 'without_income'], 'all'),
            'sort' => $this->resolveEnumFilter((string) $request->input('sort', 'margin_desc'), ['margin_desc', 'income_desc', 'name_asc'], 'margin_desc'),
            'snapshot_only' => $request->boolean('snapshot_only'),
            'limit' => $this->resolveLimit((int) $request->input('limit', 20)),
        ];

        $services = Service::query()
            ->select(['id', 'name', 'status', 'type', 'provider', 'owner_name'])
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $search = '%'.$filters['q'].'%';

                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('name', 'like', $search)
                        ->orWhere('provider', 'like', $search)
                        ->orWhere('type', 'like', $search)
                        ->orWhere('owner_name', 'like', $search);
                });
            })
            ->when($filters['service_status'] !== 'all', fn ($query) => $query->where('status', $filters['service_status']))
            ->orderBy('name')
            ->get();

        $serviceIds = $services->pluck('id')->all();

        $incomeReal = (float) Payment::query()
            ->whereBetween('paid_at', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->sum('amount');

        $projectedRecurringIncome = (float) Subscription::query()
            ->where('is_active', true)
            ->get()
            ->sum(function (Subscription $subscription) use ($projectionDate): float {
                return $this->projectedRecurringAmountForSubscription($subscription, $projectionDate);
            });

        $projectedCosts = (float) CostItem::query()
            ->where('is_active', true)
            ->get()
            ->sum(fn (CostItem $cost) => $cost->monthlyAmount());

        $netProjected = $projectedRecurringIncome - $projectedCosts;
        $realVsCost = $incomeReal - $projectedCosts;

        $incomeByServiceMap = empty($serviceIds)
            ? collect()
            : Payment::query()
                ->selectRaw('service_id, COALESCE(SUM(amount),0) as amount_total')
                ->whereIn('service_id', $serviceIds)
                ->whereBetween('paid_at', [$periodStart->toDateString(), $periodEnd->toDateString()])
                ->groupBy('service_id')
                ->pluck('amount_total', 'service_id')
                ->map(fn ($amount) => (float) $amount);

        $recurringIncomeByServiceMap = empty($serviceIds)
            ? collect()
            : Subscription::query()
                ->where('is_active', true)
                ->whereIn('service_id', $serviceIds)
                ->get()
                ->groupBy('service_id')
                ->map(function (Collection $subscriptions) use ($projectionDate): float {
                    return (float) $subscriptions->sum(function (Subscription $subscription) use ($projectionDate): float {
                        return $this->projectedRecurringAmountForSubscription($subscription, $projectionDate);
                    });
                });

        $costInsightsByService = $this->allocatedCostInsightsByService($serviceIds);

        $currentSnapshots = MonthlySnapshot::query()
            ->with('service:id,name')
            ->where('period', $period)
            ->when(! empty($serviceIds), fn ($query) => $query->whereIn('service_id', $serviceIds))
            ->orderByDesc('net_margin')
            ->get();

        $snapshotByService = $currentSnapshots->keyBy('service_id');

        $recentPaymentsByService = empty($serviceIds)
            ? collect()
            : Payment::query()
                ->with('subscription:id,name')
                ->whereIn('service_id', $serviceIds)
                ->whereBetween('paid_at', [$periodStart->toDateString(), $periodEnd->toDateString()])
                ->orderByDesc('paid_at')
                ->orderByDesc('id')
                ->get()
                ->groupBy('service_id')
                ->map(fn (Collection $payments) => $payments->take(5)->values());

        $serviceRows = $services
            ->map(function (Service $service) use ($incomeByServiceMap, $recurringIncomeByServiceMap, $costInsightsByService, $snapshotByService, $recentPaymentsByService): array {
                $serviceId = (int) $service->id;
                $incomeRealForService = (float) ($incomeByServiceMap[$serviceId] ?? 0);
                $incomeRecurringForService = (float) ($recurringIncomeByServiceMap[$serviceId] ?? 0);
                $costInsight = $costInsightsByService[$serviceId] ?? [
                    'direct_cost' => 0,
                    'shared_cost' => 0,
                    'items' => [],
                ];

                $directCost = (float) ($costInsight['direct_cost'] ?? 0);
                $sharedCost = (float) ($costInsight['shared_cost'] ?? 0);
                $totalCost = $directCost + $sharedCost;
                $netRealVsCost = $incomeRealForService - $totalCost;
                $netProjected = $incomeRecurringForService - $totalCost;
                $snapshot = $snapshotByService->get($serviceId);

                return [
                    'service_id' => $serviceId,
                    'name' => (string) $service->name,
                    'status' => (string) ($service->status ?? '-'),
                    'type' => (string) ($service->type ?? '-'),
                    'provider' => (string) ($service->provider ?? '-'),
                    'owner_name' => (string) ($service->owner_name ?? '-'),
                    'income_real' => round($incomeRealForService, 2),
                    'income_recurring_projected' => round($incomeRecurringForService, 2),
                    'direct_cost' => round($directCost, 2),
                    'shared_cost' => round($sharedCost, 2),
                    'total_cost' => round($totalCost, 2),
                    'net_real_vs_cost' => round($netRealVsCost, 2),
                    'net_projected' => round($netProjected, 2),
                    'snapshot' => $snapshot ? [
                        'income_total' => round((float) $snapshot->income_total, 2),
                        'direct_cost_total' => round((float) $snapshot->direct_cost_total, 2),
                        'shared_cost_total' => round((float) $snapshot->shared_cost_total, 2),
                        'net_margin' => round((float) $snapshot->net_margin, 2),
                    ] : null,
                    'cost_items' => collect($costInsight['items'] ?? [])->values()->all(),
                    'recent_payments' => ($recentPaymentsByService->get($serviceId) ?? collect())
                        ->map(fn (Payment $payment) => [
                            'date' => $payment->paid_at?->format('Y-m-d'),
                            'amount' => round((float) $payment->amount, 2),
                            'method' => $payment->methodLabel(),
                            'status' => (string) ($payment->status ?? Payment::STATUS_CONFIRMED),
                            'reference' => (string) ($payment->reference ?? '-'),
                            'subscription' => (string) ($payment->subscription?->name ?? '-'),
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->filter(fn (array $row) => $this->matchesProfitabilityFilter($row, $filters['profitability']))
            ->filter(function (array $row) use ($filters): bool {
                if (! $filters['snapshot_only']) {
                    return true;
                }

                return $row['snapshot'] !== null;
            });

        $serviceRows = match ($filters['sort']) {
            'income_desc' => $serviceRows->sortByDesc('income_real')->values(),
            'name_asc' => $serviceRows->sortBy('name')->values(),
            default => $serviceRows->sortByDesc('net_real_vs_cost')->values(),
        };

        $serviceRows = $serviceRows
            ->take($filters['limit'])
            ->values();

        $incomeByService = $serviceRows
            ->map(fn (array $row) => (object) [
                'id' => $row['service_id'],
                'name' => $row['name'],
                'income_total' => $row['income_real'],
            ]);

        $costByCategory = CostItem::query()
            ->where('is_active', true)
            ->get()
            ->groupBy('category')
            ->map(fn ($items, $category) => (object) [
                'category' => $category,
                'amount_total' => $items->sum(fn (CostItem $cost) => $cost->monthlyAmount()),
            ])
            ->sortByDesc('amount_total')
            ->values();

        $snapshotHistory = $snapshotService->historicalSummary();

        return view('finance.index', compact(
            'period',
            'filters',
            'incomeReal',
            'projectedRecurringIncome',
            'projectedCosts',
            'netProjected',
            'realVsCost',
            'incomeByService',
            'costByCategory',
            'currentSnapshots',
            'snapshotHistory',
            'serviceRows'
        ));
    }

    public function generateSnapshot(Request $request, FinanceSnapshotService $snapshotService): RedirectResponse
    {
        $period = (string) $request->input('period', now()->subMonthNoOverflow()->format('Y-m'));
        $processedServices = $snapshotService->generateForPeriod($period);

        AuditLogger::log('generated', 'monthly_snapshot', null, [
            'period' => $period,
            'services_count' => $processedServices,
        ]);

        return redirect()
            ->route('finanzas.index', ['period' => $period])
            ->with('status', "Snapshots generados para {$period}. Servicios procesados: {$processedServices}.");
    }

    private function resolveEnumFilter(string $value, array $allowed, string $default): string
    {
        return in_array($value, $allowed, true) ? $value : $default;
    }

    private function resolveLimit(int $limit): int
    {
        $allowed = [10, 20, 50, 100];

        return in_array($limit, $allowed, true) ? $limit : 20;
    }

    /**
     * @param list<int> $serviceIds
     * @return array<int, array{direct_cost: float, shared_cost: float, items: array<int, array<string, mixed>>}>
     */
    private function allocatedCostInsightsByService(array $serviceIds): array
    {
        if (empty($serviceIds)) {
            return [];
        }

        $serviceLookup = array_fill_keys($serviceIds, true);
        $result = [];

        $costItems = CostItem::query()
            ->where('is_active', true)
            ->with(['allocations' => function ($query) {
                $query->where('is_active', true);
            }])
            ->get();

        foreach ($costItems as $costItem) {
            $allocations = $costItem->allocations
                ->filter(fn ($allocation) => isset($serviceLookup[(int) $allocation->service_id]))
                ->values();

            if ($allocations->isEmpty()) {
                continue;
            }

            $amount = $costItem->monthlyAmount();

            if ($amount <= 0) {
                continue;
            }

            $bucket = $costItem->cost_type === 'shared' ? 'shared_cost' : 'direct_cost';
            $mode = (string) ($allocations->first()->allocation_mode ?? 'equal');

            if ($mode === 'weight') {
                $weights = $allocations
                    ->map(fn ($allocation) => max((float) ($allocation->weight ?? 0), 0))
                    ->values();

                $totalWeight = (float) $weights->sum();

                if ($totalWeight > 0) {
                    foreach ($allocations as $allocation) {
                        $weight = max((float) ($allocation->weight ?? 0), 0);
                        $share = $amount * ($weight / $totalWeight);

                        $this->addCostShareToServiceResult($result, (int) $allocation->service_id, $bucket, $share, $costItem);
                    }

                    continue;
                }
            }

            $equalShare = $amount / max($allocations->count(), 1);

            foreach ($allocations as $allocation) {
                $this->addCostShareToServiceResult($result, (int) $allocation->service_id, $bucket, $equalShare, $costItem);
            }
        }

        foreach ($result as $serviceId => $data) {
            $items = collect($data['items'] ?? [])
                ->sortByDesc('allocated_share')
                ->values()
                ->all();

            $result[$serviceId]['direct_cost'] = round((float) ($data['direct_cost'] ?? 0), 2);
            $result[$serviceId]['shared_cost'] = round((float) ($data['shared_cost'] ?? 0), 2);
            $result[$serviceId]['items'] = $items;
        }

        return $result;
    }

    /**
     * @param array<int, array{direct_cost?: float, shared_cost?: float, items?: array<int, array<string, mixed>>}> $result
     */
    private function addCostShareToServiceResult(array &$result, int $serviceId, string $bucket, float $share, CostItem $costItem): void
    {
        if (! isset($result[$serviceId])) {
            $result[$serviceId] = [
                'direct_cost' => 0,
                'shared_cost' => 0,
                'items' => [],
            ];
        }

        $result[$serviceId][$bucket] = (float) ($result[$serviceId][$bucket] ?? 0) + $share;

        $items = $result[$serviceId]['items'] ?? [];

        if (! isset($items[$costItem->id])) {
            $items[$costItem->id] = [
                'id' => (int) $costItem->id,
                'name' => (string) $costItem->name,
                'category' => $costItem->categoryLabel(),
                'cost_type' => (string) $costItem->cost_type,
                'monthly_total' => round((float) $costItem->monthlyAmount(), 2),
                'allocated_share' => 0,
            ];
        }

        $items[$costItem->id]['allocated_share'] = round((float) $items[$costItem->id]['allocated_share'] + $share, 2);

        $result[$serviceId]['items'] = $items;
    }

    private function projectedRecurringAmountForSubscription(Subscription $subscription, Carbon $projectionDate): float
    {
        if ($subscription->isInTrial($projectionDate)) {
            return 0;
        }

        return match ($subscription->billing_cycle) {
            'yearly' => (float) $subscription->amount / 12,
            default => (float) $subscription->amount,
        };
    }

    /**
     * @param array<string, mixed> $row
     */
    private function matchesProfitabilityFilter(array $row, string $profitability): bool
    {
        $net = (float) ($row['net_real_vs_cost'] ?? 0);
        $income = (float) ($row['income_real'] ?? 0);

        return match ($profitability) {
            'positive' => $net > 0,
            'negative' => $net < 0,
            'breakeven' => abs($net) < 0.01,
            'with_income' => $income > 0,
            'without_income' => $income <= 0,
            default => true,
        };
    }
}
