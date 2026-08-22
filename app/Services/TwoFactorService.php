<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;

class TwoFactorService
{
    /**
     * Generate a random 16-character Base32 secret for TOTP.
     */
    public static function generateSecretKey(): string
    {
        $validChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < 16; $i++) {
            $secret .= $validChars[random_int(0, 31)];
        }
        return $secret;
    }

    /**
     * Generate standard OTPAuth URL for Authenticator Apps (Google Authenticator, Microsoft Authenticator, Authy).
     */
    public static function getOtpAuthUrl(User $user, string $secret): string
    {
        $issuer = 'INNOVATEP INFOTEP';
        $account = $user->email;
        return "otpauth://totp/" . rawurlencode($issuer) . ":" . rawurlencode($account) . "?secret={$secret}&issuer=" . rawurlencode($issuer) . "&algorithm=SHA1&digits=6&period=30";
    }

    /**
     * Generate QR Code Image URL.
     */
    public static function getQrCodeUrl(string $otpAuthUrl): string
    {
        return "https://api.qrserver.com/v1/create-qr-code/?size=200x200&margin=8&data=" . urlencode($otpAuthUrl);
    }

    /**
     * Verify a 6-digit TOTP code against a Base32 secret.
     */
    public static function verifyTotpCode(string $secret, string $code): bool
    {
        $code = trim($code);
        if (strlen($code) !== 6 || !ctype_digit($code)) {
            return false;
        }

        $timeSlice = floor(time() / 30);

        // Check current time slice and adjacent (clock drift tolerance ±1 slice = 30s)
        for ($i = -1; $i <= 1; $i++) {
            $calculatedCode = self::calculateCode($secret, $timeSlice + $i);
            if (hash_equals((string)$calculatedCode, (string)$code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculate TOTP code for a given timestamp slice.
     */
    private static function calculateCode(string $secret, int $timeSlice): string
    {
        $secretKey = self::base32Decode($secret);
        $time = pack('N*', 0) . pack('N*', $timeSlice);
        $hmac = hash_hmac('sha1', $time, $secretKey, true);
        $offset = ord(substr($hmac, -1)) & 0x0F;
        $hashPart = substr($hmac, $offset, 4);
        $value = unpack('N', $hashPart)[1] & 0x7FFFFFFF;
        $modulo = $value % 1000000;
        return str_pad((string)$modulo, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Decode a Base32 string to binary.
     */
    private static function base32Decode(string $b32): string
    {
        $b32 = strtoupper($b32);
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $binary = '';
        foreach (str_split($b32) as $char) {
            $pos = strpos($alphabet, $char);
            if ($pos !== false) {
                $binary .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
            }
        }
        $bytes = '';
        foreach (str_split($binary, 8) as $byte) {
            if (strlen($byte) === 8) {
                $bytes .= chr(bindec($byte));
            }
        }
        return $bytes;
    }

    /**
     * Generate 6-digit numeric OTP for Email 2FA.
     */
    public static function generateEmailCode(): string
    {
        return str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }
}
