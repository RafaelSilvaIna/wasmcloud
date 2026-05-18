<?php
declare(strict_types=1);

namespace Services\Ads;

final class VidsStClient
{
    private const BASE_URL = 'https://vids.st/api/index.php';
    private const DEFAULT_API_KEY = '61e4239a3612e57454f70e4b2633e6ea';

    public function __construct(private ?string $apiKey = null)
    {
        $envKey = trim((string) getenv('VIDS_ST_API_KEY'));
        $this->apiKey ??= $envKey !== '' ? $envKey : self::DEFAULT_API_KEY;
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== null && $this->apiKey !== '';
    }

    public function uploadServer(): array
    {
        return $this->request('upload/server');
    }

    public function uploadToken(): array
    {
        return $this->request('upload/token');
    }

    public function completeUpload(string $token): array
    {
        return $this->request('upload/complete', ['token' => $token]);
    }

    public function fileInfo(string $fileId): array
    {
        return $this->request('file/info', ['file_id' => $fileId]);
    }

    public function deleteFile(string $fileId): array
    {
        return $this->request('file/delete', ['file_id' => $fileId]);
    }

    private function request(string $action, array $query = []): array
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('A integração de vídeo ainda não foi configurada no servidor.');
        }
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('Extensão cURL indisponível para integração de vídeo.');
        }

        $url = self::BASE_URL . '?' . http_build_query(['key' => $this->apiKey, 'action' => $action] + $query);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'PipoCineAdsUploader/1.0 (+https://pipocine.site)',
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode < 200 || $httpCode >= 300) {
            throw new \RuntimeException('Não foi possível comunicar com o serviço de vídeo.');
        }

        $json = json_decode((string) $response, true);
        if (!is_array($json)) {
            throw new \RuntimeException('Resposta inválida do serviço de vídeo.');
        }
        if (($json['success'] ?? true) === false) {
            throw new \RuntimeException((string) ($json['message'] ?? $json['error'] ?? 'Falha no serviço de vídeo.'));
        }

        return $json;
    }
}
