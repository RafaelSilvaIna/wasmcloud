<?php
declare(strict_types=1);

namespace Helpers\Ads;

final class AdsCampaignValidator
{
    public static function draftToken(string $value): ?string
    {
        $value = strtolower(trim($value));
        return preg_match('/^[a-f0-9]{64}$/', $value) ? $value : null;
    }

    public static function creativeType(string $value): ?string
    {
        return in_array($value, ['image', 'video'], true) ? $value : null;
    }

    public static function description(string $value): ?string
    {
        $value = trim(strip_tags($value));
        return mb_strlen($value) >= 8 && mb_strlen($value) <= 500 ? $value : null;
    }

    public static function redirectUrl(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $value)
            ? $value
            : '';
    }

    public static function canSkip(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? true;
    }

    public static function campaignNameFromDescription(string $description): string
    {
        $plain = preg_replace('/\s+/', ' ', trim($description));
        return mb_substr((string) $plain, 0, 140);
    }
}
