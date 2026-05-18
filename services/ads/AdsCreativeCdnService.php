<?php
declare(strict_types=1);

namespace Services\Ads;

use Helpers\Cdn\CdnUrlGuard;
use Models\Ads\AdsCampaignModel;

require_once __DIR__ . '/../../helpers/cdn/CdnUrlGuard.php';

final class AdsCreativeCdnService
{
    private const RELAY_USER_AGENT = 'PipoCineAdsRelay/1.0 (+https://pipocine.site)';

    public function __construct(private readonly AdsCampaignModel $campaigns) {}

    public function serve(string $token): bool
    {
        $campaign = $this->campaigns->findByCdnToken($token);
        if (!$campaign || trim((string) ($campaign['creative_url'] ?? '')) === '') {
            return false;
        }

        $status = (string) ($campaign['status'] ?? 'draft');
        $ownerPreview = !empty($_SESSION['ads_account_id'])
            && (int) $_SESSION['ads_account_id'] === (int) ($campaign['ads_account_id'] ?? 0);
        if ($status !== 'active' && !$ownerPreview) {
            return false;
        }

        $sourceUrl = (string) $campaign['creative_url'];
        CdnUrlGuard::assertAllowedExternalUrl($sourceUrl);

        $file = $this->cacheFile($campaign, $sourceUrl);
        $this->serveFile(
            $file,
            (string) ($campaign['creative_mime_type'] ?: $this->guessMime($campaign)),
            $status === 'active'
        );
        return true;
    }

    private function cacheFile(array $campaign, string $sourceUrl): string
    {
        $extension = $this->extensionFor($campaign);
        $key = hash('sha256', 'ads-cdn-v1' . "\n" . $sourceUrl);
        $target = $this->cacheDir() . DIRECTORY_SEPARATOR . $key . '.' . $extension;
        if (is_file($target) && filesize($target) > 0) {
            return $target;
        }

        $lock = fopen($target . '.lock', 'c');
        if (!$lock) {
            throw new \RuntimeException('Não foi possível abrir o lock da CDN de anúncios.');
        }

        try {
            flock($lock, LOCK_EX);
            if (is_file($target) && filesize($target) > 0) {
                return $target;
            }

            $tmp = $target . '.tmp.' . getmypid();
            $this->download($sourceUrl, $tmp);
            if (!is_file($tmp) || filesize($tmp) <= 0) {
                @unlink($tmp);
                throw new \RuntimeException('A CDN não recebeu bytes válidos do criativo.');
            }
            if (!@rename($tmp, $target)) {
                @unlink($tmp);
                throw new \RuntimeException('Não foi possível publicar o cache do criativo.');
            }
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }

        return $target;
    }

    private function download(string $sourceUrl, string $target): void
    {
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('Extensão cURL indisponível para a CDN de anúncios.');
        }
        $fp = fopen($target, 'wb');
        if (!$fp) {
            throw new \RuntimeException('Não foi possível preparar o arquivo temporário da CDN.');
        }

        $ch = curl_init($sourceUrl);
        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_USERAGENT => self::RELAY_USER_AGENT,
            CURLOPT_HTTPHEADER => [
                'Accept: video/mp4,image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8',
            ],
        ]);
        $ok = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);

        if ($ok === false || $httpCode < 200 || $httpCode >= 300) {
            @unlink($target);
            throw new \RuntimeException('Não foi possível obter o criativo remoto.');
        }
    }

    private function serveFile(string $file, string $contentType, bool $publicCache): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        while (ob_get_level()) {
            ob_end_clean();
        }

        $size = filesize($file);
        $start = 0;
        $end = $size - 1;
        $status = 200;
        $range = (string) ($_SERVER['HTTP_RANGE'] ?? '');
        if ($range !== '' && preg_match('/bytes=(\d*)-(\d*)/', $range, $m)) {
            $start = $m[1] === '' ? 0 : (int) $m[1];
            if ($m[2] !== '') {
                $end = min((int) $m[2], $end);
            }
            if ($start > $end || $start >= $size) {
                http_response_code(416);
                header('Content-Range: bytes */' . $size);
                return;
            }
            $status = 206;
        }

        http_response_code($status);
        header('Content-Type: ' . $contentType);
        header('Content-Length: ' . ($end - $start + 1));
        header('Accept-Ranges: bytes');
        header('X-Content-Type-Options: nosniff');
        header('X-Pipocine-CDN: ads-cache');
        header('Cache-Control: ' . ($publicCache ? 'public, max-age=86400' : 'private, no-store'));
        if ($status === 206) {
            header("Content-Range: bytes {$start}-{$end}/{$size}");
        }
        if ($_SERVER['REQUEST_METHOD'] === 'HEAD') {
            return;
        }

        $handle = fopen($file, 'rb');
        if (!$handle) {
            throw new \RuntimeException('Não foi possível abrir o criativo em cache.');
        }
        fseek($handle, $start);
        $remaining = $end - $start + 1;
        while ($remaining > 0 && !feof($handle) && !connection_aborted()) {
            $chunk = fread($handle, min(256 * 1024, $remaining));
            if ($chunk === false || $chunk === '') {
                break;
            }
            echo $chunk;
            $remaining -= strlen($chunk);
            flush();
        }
        fclose($handle);
    }

    private function cacheDir(): string
    {
        $dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'ads-cdn-cache';
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException('Não foi possível criar o cache da CDN de anúncios.');
        }
        return $dir;
    }

    private function extensionFor(array $campaign): string
    {
        if (($campaign['creative_type'] ?? '') === 'video') {
            return 'mp4';
        }
        return match ((string) ($campaign['creative_mime_type'] ?? '')) {
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => 'jpg',
        };
    }

    private function guessMime(array $campaign): string
    {
        return ($campaign['creative_type'] ?? '') === 'video' ? 'video/mp4' : 'image/jpeg';
    }
}
