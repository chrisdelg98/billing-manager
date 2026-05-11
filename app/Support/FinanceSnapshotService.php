<?php

namespace App\Support;

use App\Models\CostAllocation;
use App\Models\CostItem;
use App\Models\MonthlySnapshot;
use App\Models\Payment;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FinanceSnapshotService
{
    public function generateForPeriod(string $period): int
    {
        [$period, $periodStart, $periodEnd] = $this->resolvePeriod($period);

        $incomeByService = Payment::query()
            ->select('service_id', DB::raw('SUM(amount) as amount_total'))
            ->whereBetween('paid_at', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->groupBy('service_id')
            ->pluck('amount_total', 'service_id');

        $allocatedCosts = $this->allocatedMonthlyCostsByService();
        $serviceIds = Service::query()->orderBy('id')->pluck('id');

        foreach ($serviceIds as $serviceId) {
            $incomeTotal = (float) ($incomeByService[$serviceId] ?? 0);
            $directCostTotal = (float) ($allocatedCosts[$serviceId]['direct'] ?? 0);
            $sharedCostTotal = (float) ($allocatedCosts[$serviceId]['shared'] ?? 0);
            $netMargin = $incomeTotal - $directCostTotal - $sharedCostTotal;

            MonthlySnapshot::query()->updateOrCreate(
                [
                    'period' => $period,
                    'service_id' => $serviceId,
                ],
                [
                    'income_total' => $incomeTotal,
                    'direct_cost_total' => $directCostTotal,
                    'shared_cost_total' => $sharedCostTotal,
                    'net_margin' => $netMargin,
                    'generated_at' => now(),
                ]
            );
        }

        return $serviceIds->count();
    }

    public function historicalSummary(int $limit = 12): Collection
    {
        return MonthlySnapshot::query()
            ->select('period')
            ->selectRaw('SUM(income_total) as income_total')
            ->selectRaw('SUM(direct_cost_total) as direct_cost_total')
            ->selectRaw('SUM(shared_cost_total) as shared_cost_total')
            ->selectRaw('SUM(net_margin) as net_margin')
            ->groupBy('period')
            ->orderByDesc('period')
            ->limit($limit)
            ->get();
    }

    private function allocatedMonthlyCostsByService(): array
    {
        $allocatedTotals = [];

        $costItems = CostItem::query()
            ->where('is_active', true)
            ->with([
                'allocations' => function ($query) {
                    $query->where('is_active', true);
                },
                'subscription:id,service_id',
            ])
            ->get();

        foreach ($costItems as $costItem) {
            $amount = $costItem->monthlyAmount();

            if ($amount <= 0) {
                continue;
            }

            if ((string) $costItem->cost_type === 'direct') {
                $serviceId = $this->resolveDirectCostServiceId($costItem);

                if ($serviceId !== null) {
                    $allocatedTotals[$serviceId]['direct'] =
                        ($allocatedTotals[$serviceId]['direct'] ?? 0) + $amount;
                }

                continue;
            }

            $allocations = $costItem->allocations;

            if ($allocations->isEmpty()) {
                continue;
            }

            $mode = (string) ($allocations->first()->allocation_mode ?? 'equal');
            $bucket = $costItem->cost_type === 'shared' ? 'shared' : 'direct';

            if ($mode === 'weight') {
                $weights = $allocations
                    ->map(fn (CostAllocation $allocation) => max((float) ($allocation->weight ?? 0), 0))
                    ->values();

                $totalWeight = (float) $weights->sum();

                if ($totalWeight <= 0) {
                    $this->applyEqualSplit($allocations, $amount, $bucket, $allocatedTotals);
                    continue;
                }

                foreach ($allocations as $allocation) {
                    $weight = max((float) ($allocation->weight ?? 0), 0);
                    $share = $amount * ($weight / $totalWeight);

                    $allocatedTotals[$allocation->service_id][$bucket] =
                        ($allocatedTotals[$allocation->service_id][$bucket] ?? 0) + $share;
                }

                continue;
            }

            $this->applyEqualSplit($allocations, $amount, $bucket, $allocatedTotals);
        }

        return $allocatedTotals;
    }

    private function resolveDirectCostServiceId(CostItem $costItem): ?int
    {
        if (! empty($costItem->service_id)) {
            return (int) $costItem->service_id;
        }

        if (! empty($costItem->subscription_id)) {
            $serviceId = (int) ($costItem->subscription?->service_id ?? 0);

            return $serviceId > 0 ? $serviceId : null;
        }

        return null;
    }

    private function applyEqualSplit(Collection $allocations, float $amount, string $bucket, array &$allocatedTotals): void
    {
        $count = max($allocations->count(), 1);
        $share = $amount / $count;

        foreach ($allocations as $allocation) {
            $allocatedTotals[$allocation->service_id][$bucket] =
                ($allocatedTotals[$allocation->service_id][$bucket] ?? 0) + $share;
        }
    }

    private function resolvePeriod(string $period): array
    {
        try {
            $periodStart = Carbon::createFromFormat('Y-m', $period)->startOfMonth();
        } catch (\Throwable) {
            $periodStart = now()->startOfMonth();
        }

        return [
            $periodStart->format('Y-m'),
            $periodStart,
            $periodStart->copy()->endOfMonth(),
        ];
    }
}
