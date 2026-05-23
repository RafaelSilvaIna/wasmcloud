<?php

declare(strict_types=1);

namespace Models\Cdn;

final class CdnTokenModel
{
    private string $storageDir;

    public function __construct(?string $storageDir = null)
    {
        $this->storageDir = $storageDir ?: dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'cdn-tokens';
    }

    public function create(array $payload): string
    {
        $this->ensureStorageDir();

        $token = $this->newToken();
        $now = time();
        $payload['created_at'] = $payload['created_at'] ?? $now;
        $payload['last_access_at'] = $now;

        $target = $this->pathFor($token);
        $tmp = $target . '.tmp.' . getmypid() . '.' . bin2hex(random_bytes(4));
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new \RuntimeException('Nao foi possivel serializar o token da CDN.');
        }

        if (file_put_contents($tmp, $json, LOCK_EX) === false) {
            throw new \RuntimeException('Nao foi possivel gravar o token da CDN.');
        }

        if (!@rename($tmp, $target)) {
            @unlink($tmp);
            throw new \RuntimeException('Nao foi possivel publicar o token da CDN.');
        }

        @chmod($target, 0660);
        return $token;
    }

    public function find(string $token): ?array
    {
        if (!$this->isValidToken($token)) {
            return null;
        }

        $path = $this->pathFor($token);
        if (!is_file($path)) {
            return null;
        }

        $raw = file_get_contents($path);
        $payload = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($payload)) {
            @unlink($path);
            return null;
        }

        if ((int) ($payload['exp'] ?? 0) < time()) {
            @unlink($path);
            return null;
        }

        return $payload;
    }

    public function delete(string $token): void
    {
        if ($this->isValidToken($token)) {
            @unlink($this->pathFor($token));
        }
    }

    public function cleanupExpired(int $limit = 100): int
    {
        if (!is_dir($this->storageDir)) {
            return 0;
        }

        $now = time();
        $removed = 0;
        $files = glob($this->storageDir . DIRECTORY_SEPARATOR . '*.json') ?: [];

        foreach ($files as $file) {
            if ($removed >= $limit) {
                break;
            }

            $raw = file_get_contents($file);
            $payload = is_string($raw) ? json_decode($raw, true) : null;
            $expiresAt = is_array($payload) ? (int) ($payload['exp'] ?? 0) : 0;

            if ($expiresAt <= 0 || $expiresAt < $now) {
                @unlink($file);
                $removed++;
            }
        }

        return $removed;
    }

    private function ensureStorageDir(): void
    {
        if (!is_dir($this->storageDir) && !mkdir($this->storageDir, 0775, true) && !is_dir($this->storageDir)) {
            throw new \RuntimeException('Nao foi possivel criar o storage de tokens da CDN.');
        }
    }

    private function pathFor(string $token): string
    {
        return $this->storageDir . DIRECTORY_SEPARATOR . hash('sha256', $token) . '.json';
    }

    private function newToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    private function isValidToken(string $token): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9_-]{32,128}$/', $token);
    }
}
