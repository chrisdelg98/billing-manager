<?php

namespace App\Http\Controllers;

use App\Models\CostItem;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FinanceController extends Controller
{
    public function index(): View
    {
        $period = request('period', now()->format('Y-m'));

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
                return match ($subscription->billing_cycle) {
                    'yearly' => (float) $subscription->amount / 12,
                    default => (float) $subscription->amount,
                };
            });

        $projectedCosts = (float) CostItem::query()
            ->where('is_active', true)
            ->get()
            ->sum(function (CostItem $cost) {
                return match ($cost->billing_cycle) {
                    'yearly' => (float) $cost->amount / 12,
                    default => (float) $cost->amount,
                };
            });

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
            ->select('category', DB::raw('SUM(amount) as amount_total'))
            ->where('is_active', true)
            ->groupBy('category')
            ->orderByDesc('amount_total')
            ->get();

        return view('finance.index', compact(
            'period',
            'incomeReal',
            'projectedRecurringIncome',
            'projectedCosts',
            'netProjected',
            'realVsCost',
            'incomeByService',
            'costByCategory'
        ));
    }
}
