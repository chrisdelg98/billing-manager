<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LicenseAccessLog;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class LicenseStatusController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $licenseCode = trim((string) $request->header('X-License-Code', ''));
        $licenseSecret = trim((string) $request->header('X-License-Secret', ''));

        $validator = Validator::make([
            'license_code' => $licenseCode,
            'license_secret' => $licenseSecret,
        ], [
            'license_code' => ['required', 'string', 'max:64'],
            'license_secret' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            $this->logAccess($request, null, $licenseCode ?: null, 'invalid_request', 422);

            return response()->json([
                'ok' => false,
                'error' => [
                    'code' => 'invalid_request',
                    'message' => 'Debes enviar X-License-Code y X-License-Secret.',
                ],
            ], 422);
        }

        $subscription = Subscription::query()
            ->with('service')
            ->where('license_code', $licenseCode)
            ->first();

        if (! $subscription || ! $subscription->license_api_enabled) {
            $this->logAccess($request, $subscription, $licenseCode, 'not_found', 404);

            return response()->json([
                'ok' => false,
                'error' => [
                    'code' => 'not_found',
                    'message' => 'Licencia no encontrada.',
                ],
            ], 404);
        }

        if (empty($subscription->license_secret_hash) || ! Hash::check($licenseSecret, (string) $subscription->license_secret_hash)) {
            $this->logAccess($request, $subscription, $licenseCode, 'invalid_credentials', 401);

            return response()->json([
                'ok' => false,
                'error' => [
                    'code' => 'invalid_credentials',
                    'message' => 'Credenciales de licencia no validas.',
                ],
            ], 401);
        }

        if ($subscription->license_key_revoked_at) {
            $this->logAccess($request, $subscription, $licenseCode, 'revoked', 403);

            return response()->json([
                'ok' => false,
                'error' => [
                    'code' => 'revoked',
                    'message' => 'La licencia esta revocada.',
                ],
            ], 403);
        }

        $status = $this->resolveStatus($subscription);

        $subscription->forceFill([
            'license_last_used_at' => now(),
        ])->save();

        $this->logAccess($request, $subscription, $licenseCode, $status['status'], 200);

        $latestCoveredPeriod = $subscription->payments()
            ->whereNotNull('covered_period_start')
            ->latest('covered_period_start')
            ->value('covered_period_start');

        return response()->json([
            'ok' => true,
            'license_code' => $subscription->license_code,
            'status' => $status['status'],
            'status_label' => $this->buildStatusLabel($status),
            'can_access' => $status['can_access'],
            'reason_code' => $status['reason_code'],
            'days_remaining' => $status['days_remaining'],
            'expires_on' => $status['expires_on'],
            'checked_at' => now()->toIso8601String(),
            'service' => [
                'name' => $subscription->service?->name,
            ],
            'subscription' => [
                'id' => $subscription->id,
                'name' => $subscription->name,
                'billing_cycle' => $subscription->billing_cycle,
                'amount' => (string) $subscription->amount,
                'currency' => $subscription->currency,
                'is_active' => (bool) $subscription->is_active,
                'has_trial' => (bool) $subscription->has_trial,
                'trial_ends_at' => $subscription->trial_ends_at?->toDateString(),
                'trial_days_remaining' => $status['trial_days_remaining'],
                'next_renewal_at' => $subscription->next_renewal_at?->toDateString(),
                'renewal_days_remaining' => $status['renewal_days_remaining'],
            ],
            'coverage' => [
                'last_covered_period' => $latestCoveredPeriod ? substr((string) $latestCoveredPeriod, 0, 7) : null,
            ],
        ]);
    }

    private function resolveStatus(Subscription $subscription): array
    {
        $today = now()->startOfDay();

        $trialDaysRemaining = $subscription->trial_ends_at
            ? $today->diffInDays($subscription->trial_ends_at->copy()->startOfDay(), false)
            : null;

        $renewalDaysRemaining = $subscription->next_renewal_at
            ? $today->diffInDays($subscription->next_renewal_at->copy()->startOfDay(), false)
            : null;

        if (! $subscription->is_active) {
            return [
                'status' => 'suspended',
                'can_access' => false,
                'reason_code' => 'subscription_inactive',
                'days_remaining' => 0,
                'expires_on' => null,
                'trial_days_remaining' => $trialDaysRemaining,
                'renewal_days_remaining' => $renewalDaysRemaining,
            ];
        }

        if ($subscription->has_trial && $subscription->trial_ends_at && $subscription->trial_ends_at->copy()->startOfDay()->gte($today)) {
            return [
                'status' => 'trial_active',
                'can_access' => true,
                'reason_code' => 'trial_window',
                'days_remaining' => max(0, (int) ($trialDaysRemaining ?? 0)),
                'expires_on' => $subscription->trial_ends_at->toDateString(),
                'trial_days_remaining' => $trialDaysRemaining,
                'renewal_days_remaining' => $renewalDaysRemaining,
            ];
        }

        if (! $subscription->next_renewal_at) {
            return [
                'status' => 'active',
                'can_access' => true,
                'reason_code' => 'no_renewal_limit',
                'days_remaining' => null,
                'expires_on' => null,
                'trial_days_remaining' => $trialDaysRemaining,
                'renewal_days_remaining' => null,
            ];
        }

        if ($subscription->next_renewal_at->copy()->startOfDay()->lt($today)) {
            return [
                'status' => 'overdue',
                'can_access' => false,
                'reason_code' => 'renewal_overdue',
                'days_remaining' => 0,
                'expires_on' => $subscription->next_renewal_at->toDateString(),
                'trial_days_remaining' => $trialDaysRemaining,
                'renewal_days_remaining' => $renewalDaysRemaining,
            ];
        }

        return [
            'status' => 'active',
            'can_access' => true,
            'reason_code' => 'paid_current',
            'days_remaining' => max(0, (int) ($renewalDaysRemaining ?? 0)),
            'expires_on' => $subscription->next_renewal_at->toDateString(),
            'trial_days_remaining' => $trialDaysRemaining,
            'renewal_days_remaining' => $renewalDaysRemaining,
        ];
    }

    private function buildStatusLabel(array $status): string
    {
        $statusCode = (string) ($status['status'] ?? '');
        $daysRemaining = isset($status['days_remaining']) && $status['days_remaining'] !== null
            ? (int) $status['days_remaining']
            : null;

        if ($statusCode === 'trial_active') {
            if ($daysRemaining === null || $daysRemaining <= 0) {
                return 'Prueba vence hoy';
            }

            return $daysRemaining === 1
                ? 'Prueba vence en 1 dia'
                : "Prueba vence en {$daysRemaining} dias";
        }

        if ($statusCode === 'active') {
            if ($daysRemaining === null) {
                return 'Activa sin fecha de vencimiento';
            }

            if ($daysRemaining <= 0) {
                return 'Vence hoy';
            }

            return $daysRemaining === 1
                ? 'Vence en 1 dia'
                : "Vence en {$daysRemaining} dias";
        }

        if ($statusCode === 'overdue') {
            return 'Vencida';
        }

        if ($statusCode === 'suspended') {
            return 'Suscripcion inactiva';
        }

        return 'Estado desconocido';
    }

    private function logAccess(Request $request, ?Subscription $subscription, ?string $licenseCode, string $resultStatus, int $httpStatus): void
    {
        LicenseAccessLog::query()->create([
            'subscription_id' => $subscription?->id,
            'license_code' => $licenseCode,
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'result_status' => $resultStatus,
            'http_status' => $httpStatus,
            'checked_at' => now(),
        ]);
    }
}
