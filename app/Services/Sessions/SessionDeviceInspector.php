<?php

namespace App\Services\Sessions;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use UAParser\Parser;

class SessionDeviceInspector
{
    private Parser $parser;

    public function __construct()
    {
        $this->parser = Parser::create();
    }

    /**
     * @param  array<string, mixed>  $session
     * @return array<string, mixed>
     */
    public function inspect(array $session, string $currentSessionId): array
    {
        $userAgent = (string) Arr::get($session, 'user_agent', '');
        $client = $this->parser->parse($userAgent);
        $ipAddress = Arr::get($session, 'ip_address');
        $location = $this->locationFor($ipAddress);
        $browser = $client->ua->toString() ?: 'Navegador desconhecido';
        $os = $client->os->toString() ?: 'Sistema desconhecido';
        $deviceName = $this->deviceName($client->device->family, $browser, $os);

        return [
            'id' => $session['id'],
            'is_current' => hash_equals($currentSessionId, (string) $session['id']),
            'device_name' => $deviceName,
            'device_type' => $this->deviceType($userAgent),
            'browser' => $browser,
            'os' => $os,
            'ip_address' => $ipAddress ?: 'Nao identificado',
            'location' => $location,
            'last_activity' => $session['last_activity'],
            'last_activity_human' => $this->lastActivityLabel((int) $session['last_activity']),
            'status' => hash_equals($currentSessionId, (string) $session['id']) ? 'Sessao atual' : 'Conectado',
        ];
    }

    private function deviceName(?string $family, string $browser, string $os): string
    {
        if ($family && $family !== 'Other') {
            return $family;
        }

        return "{$browser} em {$os}";
    }

    private function deviceType(string $userAgent): string
    {
        $normalized = strtolower($userAgent);

        if (str_contains($normalized, 'mobile') || str_contains($normalized, 'android') || str_contains($normalized, 'iphone')) {
            return 'mobile';
        }

        if (str_contains($normalized, 'ipad') || str_contains($normalized, 'tablet')) {
            return 'tablet';
        }

        return 'desktop';
    }

    private function locationFor(?string $ipAddress): string
    {
        if (! $ipAddress || ! filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            return 'Localizacao indisponivel';
        }

        if (! filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return 'Rede local';
        }

        return Cache::remember("session-location:{$ipAddress}", now()->addHours(12), function () use ($ipAddress): string {
            $endpoint = sprintf((string) config('services.ip_geolocation.endpoint'), $ipAddress);

            try {
                $payload = Http::timeout(4)->get($endpoint)->json();
            } catch (\Throwable) {
                return 'Localizacao indisponivel';
            }

            if (! is_array($payload) || data_get($payload, 'success') === false) {
                return 'Localizacao indisponivel';
            }

            $region = data_get($payload, 'region') ?: data_get($payload, 'regionName');
            $city = data_get($payload, 'city');
            $country = data_get($payload, 'country');

            return collect([$region, $city, $country])
                ->filter()
                ->implode(' - ') ?: 'Localizacao indisponivel';
        });
    }

    private function lastActivityLabel(int $timestamp): string
    {
        $seconds = max(0, Carbon::createFromTimestamp($timestamp)->diffInSeconds(now()));

        if ($seconds < 15) {
            return 'agora';
        }

        if ($seconds < 60) {
            return "ha {$seconds}s";
        }

        $minutes = intdiv($seconds, 60);

        if ($minutes < 60) {
            return 'ha '.$minutes.' min';
        }

        $hours = intdiv($minutes, 60);

        if ($hours < 24) {
            return 'ha '.$hours.' h';
        }

        $days = intdiv($hours, 24);

        return 'ha '.$days.' d';
    }
}
