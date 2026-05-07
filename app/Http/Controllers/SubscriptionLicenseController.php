<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Support\AuditLogger;
use App\Support\SubscriptionLicenseService;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class SubscriptionLicenseController extends Controller
{
    public function rotate(Subscription $subscription, SubscriptionLicenseService $licenseService): RedirectResponse
    {
        if (! $subscription->license_api_enabled) {
            return back()->withErrors([
                'license_api' => 'Activa primero la API de licencia para esta suscripcion.',
            ]);
        }

        try {
            $credentials = $licenseService->rotateSecret($subscription);
        } catch (RuntimeException $exception) {
            return back()->withErrors([
                'license_api' => $exception->getMessage(),
            ]);
        }

        AuditLogger::log('rotated_secret', 'subscription_license', $subscription->id, [
            'license_code' => $credentials['license_code'],
        ]);

        return back()
            ->with('status', 'Se genero un nuevo secreto de API para la suscripcion.')
            ->with('license_plain_secret', $credentials['license_secret']);
    }

    public function reveal(Subscription $subscription, SubscriptionLicenseService $licenseService): RedirectResponse
    {
        if (! $subscription->license_api_enabled) {
            return back()->withErrors([
                'license_api' => 'La API de licencia no esta activa en esta suscripcion.',
            ]);
        }

        try {
            $secret = $licenseService->revealSecret($subscription);
        } catch (RuntimeException $exception) {
            return back()->withErrors([
                'license_api' => $exception->getMessage(),
            ]);
        }

        AuditLogger::log('revealed_secret', 'subscription_license', $subscription->id, [
            'license_code' => $subscription->license_code,
        ]);

        return back()
            ->with('status', 'Se mostro el secreto actual de la licencia.')
            ->with('license_plain_secret', $secret);
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
