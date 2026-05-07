<?php

namespace App\Support;

use App\Models\Subscription;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SubscriptionLicenseService
{
    /**
     * @return array{license_code: string, license_secret: string}
     */
    public function rotateSecret(Subscription $subscription): array
    {
        $plainSecret = Str::random(48);
        $licenseCode = $subscription->license_code ?: $this->generateUniqueCode();

        $subscription->forceFill([
            'license_code' => $licenseCode,
            'license_secret_hash' => Hash::make($plainSecret),
            'license_secret_hint' => substr($plainSecret, -4),
            'license_key_rotated_at' => now(),
            'license_key_revoked_at' => null,
        ])->save();

        return [
            'license_code' => $licenseCode,
            'license_secret' => $plainSecret,
        ];
    }

    private function generateUniqueCode(): string
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $candidate = 'LIC-'.Str::upper(Str::random(12));

            $exists = Subscription::query()
                ->where('license_code', $candidate)
                ->exists();

            if (! $exists) {
                return $candidate;
            }
        }

        return 'LIC-'.Str::upper(Str::uuid()->toString());
    }
}
