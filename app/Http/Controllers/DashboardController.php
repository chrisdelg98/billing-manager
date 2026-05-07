<?php

namespace App\Http\Controllers;

use App\Models\CostItem;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Subscription;

class DashboardController extends Controller
{
    public function index()
    {
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $servicesCount = Service::query()->count();
        $activeSubscriptionsCount = Subscription::query()->where('is_active', true)->count();

        $incomeThisMonth = (float) Payment::query()
            ->whereBetween('paid_at', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->sum('amount');

        $costProjectionMonth = (float) CostItem::query()
            ->where('is_active', true)
            ->get()
            ->sum(fn (CostItem $cost) => $cost->monthlyAmount());

        $netProjectionMonth = $incomeThisMonth - $costProjectionMonth;

        $upcomingSubscriptions = Subscription::query()
            ->with('service')
            ->whereNotNull('next_renewal_at')
            ->whereDate('next_renewal_at', '>=', now()->toDateString())
            ->orderBy('next_renewal_at')
            ->limit(6)
            ->get();

        $upcomingCosts = CostItem::query()
            ->whereNotNull('next_renewal_at')
            ->whereDate('next_renewal_at', '>=', now()->toDateString())
            ->orderBy('next_renewal_at')
            ->limit(6)
            ->get();

        return view('dashboard', compact(
            'servicesCount',
            'activeSubscriptionsCount',
            'incomeThisMonth',
            'costProjectionMonth',
            'netProjectionMonth',
            'upcomingSubscriptions',
            'upcomingCosts'
        ));
    }
}
