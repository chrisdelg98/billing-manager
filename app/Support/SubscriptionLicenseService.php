<?php

namespace App\Support;

use App\Models\Subscription;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class SubscriptionLicenseService
{
    private const SECRET_CIPHER = 'aes-256-cbc';

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
            'license_secret_encrypted' => $this->encryptSecret($plainSecret),
            'license_secret_hint' => substr($plainSecret, -4),
            'license_key_rotated_at' => now(),
            'license_key_revoked_at' => null,
        ])->save();

        return [
            'license_code' => $licenseCode,
            'license_secret' => $plainSecret,
        ];
    }

    public function revealSecret(Subscription $subscription): string
    {
        $encryptedSecret = (string) ($subscription->license_secret_encrypted ?? '');

        if ($encryptedSecret === '') {
            throw new RuntimeException('No hay un secreto cifrado disponible para esta suscripcion. Rota el secreto primero.');
        }

        return $this->decryptSecret($encryptedSecret);
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

    private function encryptSecret(string $secret): string
    {
        $key = $this->resolveCipherKey();
        $ivLength = openssl_cipher_iv_length(self::SECRET_CIPHER);
        $iv = random_bytes($ivLength);

        $encrypted = openssl_encrypt($secret, self::SECRET_CIPHER, $key, OPENSSL_RAW_DATA, $iv);

        if ($encrypted === false) {
            throw new RuntimeException('No fue posible cifrar el secreto de licencia.');
        }

        return 'v1:'.base64_encode($iv.$encrypted);
    }

    private function decryptSecret(string $payload): string
    {
        $key = $this->resolveCipherKey();

        if (! str_starts_with($payload, 'v1:')) {
            throw new RuntimeException('El formato del secreto cifrado no es compatible.');
        }

        $decoded = base64_decode(substr($payload, 3), true);

        if ($decoded === false) {
            throw new RuntimeException('El secreto cifrado es invalido.');
        }

        $ivLength = openssl_cipher_iv_length(self::SECRET_CIPHER);

        if (strlen($decoded) <= $ivLength) {
            throw new RuntimeException('El secreto cifrado esta incompleto.');
        }

        $iv = substr($decoded, 0, $ivLength);
        $encrypted = substr($decoded, $ivLength);
        $decrypted = openssl_decrypt($encrypted, self::SECRET_CIPHER, $key, OPENSSL_RAW_DATA, $iv);

        if ($decrypted === false || $decrypted === '') {
            throw new RuntimeException('No fue posible descifrar el secreto. Verifica LICENSE_API_CIPHER_SECRET.');
        }

        return $decrypted;
    }

    private function resolveCipherKey(): string
    {
        $secret = trim((string) config('services.license_api.cipher_secret', ''));

        if ($secret === '') {
            throw new RuntimeException('Configura LICENSE_API_CIPHER_SECRET en tu .env para poder cifrar y revelar secretos.');
        }

        return hash('sha256', $secret, true);
    }
}
