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
            ->with(['allocations' => function ($query) {
                $query->where('is_active', true);
            }])
            ->get();

        foreach ($costItems as $costItem) {
            $allocations = $costItem->allocations;

            if ($allocations->isEmpty()) {
                continue;
            }

            $amount = $this->toMonthlyAmount((float) $costItem->amount, (string) $costItem->billing_cycle);

            if ($amount <= 0) {
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

    private function applyEqualSplit(Collection $allocations, float $amount, string $bucket, array &$allocatedTotals): void
    {
        $count = max($allocations->count(), 1);
        $share = $amount / $count;

        foreach ($allocations as $allocation) {
            $allocatedTotals[$allocation->service_id][$bucket] =
                ($allocatedTotals[$allocation->service_id][$bucket] ?? 0) + $share;
        }
    }

    private function toMonthlyAmount(float $amount, string $billingCycle): float
    {
        return match ($billingCycle) {
            'yearly' => $amount / 12,
            default => $amount,
        };
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
