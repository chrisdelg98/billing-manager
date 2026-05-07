<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Support\AuditLogger;
use App\Support\SubscriptionLicenseService;
use Illuminate\Http\RedirectResponse;

class SubscriptionLicenseController extends Controller
{
    public function rotate(Subscription $subscription, SubscriptionLicenseService $licenseService): RedirectResponse
    {
        if (! $subscription->license_api_enabled) {
            return back()->withErrors([
                'license_api' => 'Activa primero la API de licencia para esta suscripcion.',
            ]);
        }

        $credentials = $licenseService->rotateSecret($subscription);

        AuditLogger::log('rotated_secret', 'subscription_license', $subscription->id, [
            'license_code' => $credentials['license_code'],
        ]);

        return back()
            ->with('status', 'Se genero un nuevo secreto de API para la suscripcion.')
            ->with('license_plain_secret', $credentials['license_secret']);
    }

    public function revoke(Subscription $subscription): RedirectResponse
    {
        if (! $subscription->license_api_enabled) {
            return back()->withErrors([
                'license_api' => 'La API de licencia no esta activa en esta suscripcion.',
            ]);
        }

        $subscription->forceFill([
            'license_key_revoked_at' => now(),
        ])->save();

        AuditLogger::log('revoked', 'subscription_license', $subscription->id, [
            'license_code' => $subscription->license_code,
        ]);

        return back()->with('status', 'Acceso API revocado para esta suscripcion.');
    }

    public function reactivate(Subscription $subscription): RedirectResponse
    {
        if (! $subscription->license_api_enabled) {
            return back()->withErrors([
                'license_api' => 'La API de licencia no esta activa en esta suscripcion.',
            ]);
        }

        $subscription->forceFill([
            'license_key_revoked_at' => null,
        ])->save();

        AuditLogger::log('reactivated', 'subscription_license', $subscription->id, [
            'license_code' => $subscription->license_code,
        ]);

        return back()->with('status', 'Acceso API reactivado para esta suscripcion.');
    }
}
