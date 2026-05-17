<?php

declare(strict_types=1);

namespace Services\Suporte;

use Models\Suporte\SupportImageModel;
use Helpers\Suporte\SupportSession;

final class SupportImageService
{
    private const IMGBB_KEY = '538999ea6353b2b12c58af1f65f3cd8c';
    private const MAX_SIZE    = 5 * 1024 * 1024; // 5 MB
    private const ALLOWED_EXT = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private const ALLOWED_MIME = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
    ];

    public function __construct(private SupportImageModel $imageModel) {}

    /**
     * Process an uploaded image ($_FILES key = 'image').
     * Returns the generated token string on success.
     * Throws \RuntimeException on failure.
     */
    public function upload(array $file, int $chatId): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Falha no upload da imagem.');
        }

        if ($file['size'] > self::MAX_SIZE) {
            throw new \RuntimeException('Imagem muito grande. Maximo 5 MB.');
        }

        // MIME detection: prefer finfo, fallback to getimagesize (core PHP)
        if (function_exists('finfo_open')) {
            $fi   = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($fi, $file['tmp_name']);
            finfo_close($fi);
        } else {
            $info = @getimagesize($file['tmp_name']);
            $mime = $info['mime'] ?? null;
        }

        if (!in_array($mime, self::ALLOWED_MIME, true)) {
            throw new \RuntimeException('Tipo de imagem invalido.');
        }

        // Extension
        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXT, true)) {
            $ext = match ($mime) {
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/gif'  => 'gif',
                'image/webp' => 'webp',
                default      => 'jpg',
            };
        }

        $token = bin2hex(random_bytes(32));
        $imageUrl = $this->uploadToImgBb($file['tmp_name']);

        $this->imageModel->register($token, $imageUrl, $chatId, $mime, (int) $file['size']);

        return $token;
    }

    /**
     * Serve an image by token.
     * Sends headers + raw file output and exits.
     * Returns false if token is invalid or expired.
     */
    public function serve(string $token): bool
    {
        $image = $this->imageModel->findByToken($token);
        if (!$image) {
            return false;
        }

        if (preg_match('#^https?://#i', (string) $image['file_path'])) {
            header('Location: ' . $image['file_path'], true, 302);
            header('Cache-Control: private, max-age=86400');
            return true;
        }

        if (!is_file($image['file_path'])) {
            return false;
        }

        header('Content-Type: ' . $image['mime_type']);
        header('Content-Length: ' . filesize($image['file_path']));
        header('Cache-Control: private, max-age=86400');
        header('X-Content-Type-Options: nosniff');
        readfile($image['file_path']);
        return true;
    }

    private function uploadToImgBb(string $tmpName): string
    {
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('Extensao cURL indisponivel para upload de imagens.');
        }

        if (!is_file($tmpName)) {
            throw new \RuntimeException('Arquivo de imagem invalido.');
        }

        $ch = curl_init('https://api.imgbb.com/1/upload?key=' . self::IMGBB_KEY);
        $payload = [
            'image' => curl_file_create($tmpName),
        ];

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 20,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode < 200 || $httpCode >= 300) {
            throw new \RuntimeException('Nao foi possivel enviar a imagem para o ImgBB.');
        }

        $json = json_decode($response, true);
        $url = $json['data']['display_url'] ?? $json['data']['url'] ?? null;

        if (empty($json['success']) || !$url) {
            throw new \RuntimeException('Resposta invalida do ImgBB.');
        }

        return (string) $url;
    }

    /** Run cleanup of expired images. Called probabilistically from the hook. */
    public function cleanup(): void
    {
        $this->imageModel->deleteExpired();

        // Also purge orphan folders older than 2 days
        $base = dirname($this->storageDir());
        if (!is_dir($base)) return;
        foreach (scandir($base) as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            $path = $base . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($path) && $entry < date('Y-m-d', strtotime('-1 day'))) {
                $this->rmdirRecursive($path);
            }
        }
    }

    private function storageDir(): string
    {
        $base = defined('HTDOCS_ROOT')
            ? HTDOCS_ROOT . '/storage/support'
            : dirname(__DIR__, 3) . '/storage/support';

        $dir = $base . '/' . date('Y-m-d');
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }
        return $dir;
    }

    private function rmdirRecursive(string $dir): void
    {
        foreach (scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            $path = $dir . '/' . $entry;
            is_dir($path) ? $this->rmdirRecursive($path) : @unlink($path);
        }
        @rmdir($dir);
    }
}
