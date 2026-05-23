<?php
declare(strict_types=1);

namespace Services\Player;

use Models\Player\PlayerLogModel;

final class PlayerLogService
{
    public function __construct(private PlayerLogModel $logs)
    {
        $this->logs->ensureSchema();
    }

    public function record(array $payload): array
    {
        $eventId = bin2hex(random_bytes(16));
        $diagnostics = $payload['diagnostics'] ?? [];
        $network = $payload['network'] ?? [];

        if (!is_array($diagnostics)) {
            $diagnostics = ['raw' => $diagnostics];
        }
        if (!is_array($network)) {
            $network = [];
        }

        $mediaUrl = (string) ($payload['media_url'] ?? '');

        $this->logs->record([
            'event_id' => $eventId,
            'severity' => $this->choice((string) ($payload['severity'] ?? 'error'), ['info', 'warning', 'error', 'fatal'], 'error'),
            'event_type' => $this->limit((string) ($payload['event_type'] ?? 'player_error'), 80),
            'stage' => $this->limit((string) ($payload['stage'] ?? 'unknown'), 80),
            'user_id' => isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null,
            'profile_id' => isset($_SESSION['profile_id']) ? (int) $_SESSION['profile_id'] : null,
            'content_id' => $this->nullableInt($payload['content_id'] ?? null),
            'content_title' => $this->limit((string) ($payload['content_title'] ?? ''), 255) ?: null,
            'content_type' => $this->limit((string) ($payload['content_type'] ?? ''), 20) ?: null,
            'season_number' => $this->nullableInt($payload['season'] ?? null),
            'episode_number' => $this->nullableInt($payload['episode'] ?? null),
            'audio' => $this->limit((string) ($payload['audio'] ?? ''), 12) ?: null,
            'error_title' => $this->limit((string) ($payload['error_title'] ?? ''), 180) ?: null,
            'error_message' => $this->limit((string) ($payload['error_message'] ?? ''), 500) ?: null,
            'technical_message' => $this->limit((string) ($payload['technical_message'] ?? ''), 4000) ?: null,
            'player_url' => $this->limit((string) ($payload['player_url'] ?? ''), 500) ?: null,
            'api_url' => $this->limit((string) ($payload['api_url'] ?? ''), 500) ?: null,
            'media_type' => $this->limit((string) ($payload['media_type'] ?? ''), 40) ?: null,
            'media_url_hash' => $mediaUrl !== '' ? hash('sha256', $mediaUrl) : null,
            'is_embedded_browser' => !empty($payload['is_embedded_browser']) ? 1 : 0,
            'is_vpn_suspected' => !empty($payload['is_vpn_suspected']) ? 1 : 0,
            'browser_name' => $this->limit((string) ($payload['browser_name'] ?? ''), 80) ?: null,
            'user_agent' => $this->limit((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 255) ?: null,
            'ip_address' => $this->clientIp(),
            'referer' => $this->limit((string) ($_SERVER['HTTP_REFERER'] ?? ''), 255) ?: null,
            'client_network' => $this->limit(json_encode($network, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '', 255) ?: null,
            'diagnostics_json' => json_encode($diagnostics, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);

        return ['success' => true, 'event_id' => $eventId];
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        $int = (int) $value;
        return $int > 0 ? $int : null;
    }

    private function choice(string $value, array $allowed, string $fallback): string
    {
        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    private function limit(string $value, int $max): string
    {
        $value = trim($value);
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $max, 'UTF-8');
        }
        return substr($value, 0, $max);
    }

    private function clientIp(): string
    {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP'] as $header) {
            $ip = trim((string) ($_SERVER[$header] ?? ''));
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }

        foreach (explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '')) as $candidate) {
            $ip = trim($candidate);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }

        $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }
}
