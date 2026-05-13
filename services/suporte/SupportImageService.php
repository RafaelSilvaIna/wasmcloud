<?php

declare(strict_types=1);

namespace Services\Suporte;

use Models\Suporte\SupportImageModel;
use Helpers\Suporte\SupportSession;

final class SupportImageService
{
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

        $token    = bin2hex(random_bytes(32));
        $dir      = $this->storageDir();
        $filename = $token . '.' . $ext;
        $fullPath = $dir . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            throw new \RuntimeException('Nao foi possivel salvar a imagem.');
        }

        $this->imageModel->register($token, $fullPath, $chatId, $mime, (int) $file['size']);

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
        if (!$image || !is_file($image['file_path'])) {
            return false;
        }

        header('Content-Type: ' . $image['mime_type']);
        header('Content-Length: ' . filesize($image['file_path']));
        header('Cache-Control: private, max-age=86400');
        header('X-Content-Type-Options: nosniff');
        readfile($image['file_path']);
        return true;
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
