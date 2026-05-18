<?php
declare(strict_types=1);

namespace Helpers\Ads;

final class AdsValidator
{
    public static function brand(string $value): ?string
    {
        $value = trim(strip_tags($value));
        return mb_strlen($value) >= 2 && mb_strlen($value) <= 120 ? $value : null;
    }

    public static function cnpj(?string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);
        if ($digits === '') return null;
        if (strlen($digits) !== 14 || preg_match('/^(\d)\1{13}$/', $digits)) {
            return '';
        }

        $weightsFirst = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $weightsSecond = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $calculateDigit = static function (string $base, array $weights): int {
            $sum = 0;
            foreach ($weights as $index => $weight) {
                $sum += ((int) $base[$index]) * $weight;
            }
            $remainder = $sum % 11;
            return $remainder < 2 ? 0 : 11 - $remainder;
        };

        $first = $calculateDigit(substr($digits, 0, 12), $weightsFirst);
        $second = $calculateDigit(substr($digits, 0, 12) . $first, $weightsSecond);

        return $digits[12] === (string) $first && $digits[13] === (string) $second ? $digits : '';
    }

    public static function email(string $value): ?string
    {
        $value = strtolower(trim($value));
        return filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : null;
    }

    public static function password(string $value): bool
    {
        return strlen($value) >= 8
            && preg_match('/[A-Z]/', $value) === 1
            && preg_match('/[a-z]/', $value) === 1
            && preg_match('/\d/', $value) === 1;
    }

    public static function logoUrl(string $value): ?string
    {
        $value = trim($value);
        if (!filter_var($value, FILTER_VALIDATE_URL)) return null;
        return preg_match('#^https?://#i', $value) ? $value : null;
    }

    public static function website(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') return null;
        return filter_var($value, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $value) ? $value : '';
    }

    public static function contactName(string $value): ?string
    {
        $value = trim(strip_tags($value));
        return mb_strlen($value) >= 2 && mb_strlen($value) <= 120 ? $value : null;
    }

    public static function phone(string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', $value);
        if (strlen($digits) === 10 || strlen($digits) === 11) {
            $digits = '55' . $digits;
        }
        return strlen($digits) >= 12 && strlen($digits) <= 15 ? '+' . $digits : null;
    }

    public static function industry(string $value): ?string
    {
        $allowed = ['retail','entertainment','technology','education','finance','food','health','services','other'];
        return in_array($value, $allowed, true) ? $value : null;
    }

    public static function companySize(string $value): ?string
    {
        return in_array($value, ['solo','small','medium','large'], true) ? $value : null;
    }

    public static function description(?string $value): ?string
    {
        $value = trim(strip_tags((string) $value));
        if ($value === '') return null;
        return mb_strlen($value) <= 280 ? $value : '';
    }
}
