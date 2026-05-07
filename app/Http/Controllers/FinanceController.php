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

        $incomeReal = (float) Payment::query()
            ->whereBetween('paid_at', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->sum('amount');

        $projectedRecurringIncome = (float) Subscription::query()
            ->where('is_active', true)
            ->get()
            ->sum(function (Subscription $subscription) {
                if ($subscription->isInTrial()) {
                    return 0;
                }

                return match ($subscription->billing_cycle) {
                    'yearly' => (float) $subscription->amount / 12,
                    default => (float) $subscription->amount,
                };
            });

        $projectedCosts = (float) CostItem::query()
            ->where('is_active', true)
            ->get()
            ->sum(fn (CostItem $cost) => $cost->monthlyAmount());

        $netProjected = $projectedRecurringIncome - $projectedCosts;
        $realVsCost = $incomeReal - $projectedCosts;

        $incomeByService = Service::query()
            ->select('services.id', 'services.name')
            ->selectSub(function ($query) use ($periodStart, $periodEnd) {
                $query->from('payments')
                    ->selectRaw('COALESCE(SUM(amount),0)')
                    ->whereColumn('payments.service_id', 'services.id')
                    ->whereBetween('paid_at', [$periodStart->toDateString(), $periodEnd->toDateString()]);
            }, 'income_total')
            ->orderByDesc('income_total')
            ->limit(10)
            ->get();

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

        $currentSnapshots = MonthlySnapshot::query()
            ->with('service:id,name')
            ->where('period', $period)
            ->orderByDesc('net_margin')
            ->get();

        $snapshotHistory = $snapshotService->historicalSummary();

        return view('finance.index', compact(
            'period',
            'incomeReal',
            'projectedRecurringIncome',
            'projectedCosts',
            'netProjected',
            'realVsCost',
            'incomeByService',
            'costByCategory',
            'currentSnapshots',
            'snapshotHistory'
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
}
