<?php

declare(strict_types=1);

namespace Services\Cdn;

use Helpers\Cdn\CdnFfmpeg;
use Helpers\Cdn\CdnHeaders;
use Helpers\Cdn\CdnUrlGuard;

require_once __DIR__ . '/../../helpers/cdn/CdnFfmpeg.php';
require_once __DIR__ . '/../../helpers/cdn/CdnHeaders.php';
require_once __DIR__ . '/../../helpers/cdn/CdnUrlGuard.php';

final class CdnStreamService
{
    private const CACHE_VERSION = 1;
    private const SOURCE_USER_AGENT = 'PipocineMediaProxy/1.0 (+https://pipocine.site)';

    public function streamVideoOnly(string $sourceUrl, ?string $sourceOrigin = null): void
    {
        CdnUrlGuard::assertAllowedExternalUrl($sourceUrl);
        $args = [
            '-map', '0:v:0',
            '-an',
            '-c:v', 'copy',
        ];

        $file = $this->cachedFilePath($sourceUrl, [
            ...$args,
            '-movflags', '+faststart',
            '-f', 'mp4',
        ], 'mp4');

        if (is_file($file) && filesize($file) > 0) {
            $this->serveFile($file, 'video/mp4', 'video-only');
            return;
        }

        CdnHeaders::stream('video/mp4', 'video-only');
        $this->runFfmpegPipe($sourceUrl, [
            ...$args,
            '-movflags', 'frag_keyframe+empty_moov+default_base_moof',
            '-f', 'mp4',
            'pipe:1',
        ], $sourceOrigin);
    }

    public function streamAudioOnly(string $sourceUrl, string $profile, ?string $sourceOrigin = null): void
    {
        CdnUrlGuard::assertAllowedExternalUrl($sourceUrl);

        $args = [
            '-map', '0:a:0',
            '-vn',
            '-c:a', 'aac',
            '-b:a', '128k',
        ];

        $filters = CdnFfmpeg::audioFilters($profile);
        if ($filters !== []) {
            $args[] = '-af';
            $args[] = implode(',', $filters);
        }

        $file = $this->cachedFilePath($sourceUrl, [
            ...$args,
            '-movflags', '+faststart',
            '-f', 'mp4',
        ], 'm4a');

        if (is_file($file) && filesize($file) > 0) {
            $this->serveFile($file, 'audio/mp4', 'audio-' . $profile);
            return;
        }

        CdnHeaders::stream('audio/mp4', 'audio-' . $profile);
        $this->runFfmpegPipe($sourceUrl, [
            ...$args,
            '-movflags', 'frag_keyframe+empty_moov+default_base_moof',
            '-f', 'mp4',
            'pipe:1',
        ], $sourceOrigin);
    }

    private function cachedFilePath(string $sourceUrl, array $outputArgs, string $extension): string
    {
        $key = hash('sha256', self::CACHE_VERSION . "\n" . $sourceUrl . "\n" . implode("\n", $outputArgs));
        return $this->cacheDir() . DIRECTORY_SEPARATOR . $key . '.' . $extension;
    }

    private function cachedFfmpegFile(string $sourceUrl, array $outputArgs, string $extension): string
    {
        $ffmpeg = CdnFfmpeg::binary();
        if (!$ffmpeg) {
            error_log('[CDN stream] FFmpeg binary not found. Configure FFMPEG_PATH or install ffmpeg.');
            http_response_code(503);
            echo 'FFmpeg indisponivel no servidor.';
            exit;
        }

        @set_time_limit(0);
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $target = $this->cachedFilePath($sourceUrl, $outputArgs, $extension);

        if (is_file($target) && filesize($target) > 0) {
            return $target;
        }

        $lockPath = $target . '.lock';
        $lock = fopen($lockPath, 'c');
        if (!$lock) {
            throw new \RuntimeException('Nao foi possivel abrir lock de cache da CDN.');
        }

        try {
            if (!flock($lock, LOCK_EX)) {
                throw new \RuntimeException('Nao foi possivel bloquear cache da CDN.');
            }

            if (is_file($target) && filesize($target) > 0) {
                return $target;
            }

            $tmp = $target . '.tmp.' . getmypid();
            if (is_file($tmp)) {
                @unlink($tmp);
            }

            $this->runFfmpegToFile($ffmpeg, $sourceUrl, $outputArgs, $tmp);

            if (!is_file($tmp) || filesize($tmp) <= 0) {
                @unlink($tmp);
                throw new \RuntimeException('FFmpeg nao gerou arquivo de midia valido.');
            }

            if (!@rename($tmp, $target)) {
                @unlink($tmp);
                throw new \RuntimeException('Nao foi possivel publicar cache de midia.');
            }
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }

        return $target;
    }

    private function runFfmpegPipe(string $sourceUrl, array $outputArgs, ?string $sourceOrigin = null): void
    {
        $ffmpeg = CdnFfmpeg::binary();
        if (!$ffmpeg) {
            error_log('[CDN stream] FFmpeg binary not found. Configure FFMPEG_PATH or install ffmpeg.');
            http_response_code(503);
            echo 'FFmpeg indisponivel no servidor.';
            return;
        }

        @set_time_limit(0);
        ignore_user_abort(false);
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        while (ob_get_level()) {
            ob_end_clean();
        }

        $baseArgs = [
            '-hide_banner',
            '-loglevel', 'error',
            '-reconnect', '1',
            '-reconnect_streamed', '1',
            '-reconnect_delay_max', '4',
            '-fflags', '+genpts+discardcorrupt',
            '-user_agent', self::SOURCE_USER_AGENT,
            '-headers', $this->sourceHeaders($sourceUrl, $sourceOrigin),
            '-i', $sourceUrl,
        ];

        $cmd = $this->buildCommand($ffmpeg, [...$baseArgs, ...$outputArgs]);
        $process = proc_open($cmd, [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes);

        if (!is_resource($process)) {
            error_log('[CDN stream] proc_open failed for FFmpeg pipe.');
            return;
        }

        stream_set_blocking($pipes[1], true);
        stream_set_blocking($pipes[2], false);
        $stderr = '';
        $bytesWritten = 0;

        while (!feof($pipes[1]) && !connection_aborted()) {
            $chunk = fread($pipes[1], 1024 * 256);
            if ($chunk === false || $chunk === '') {
                break;
            }

            echo $chunk;
            $bytesWritten += strlen($chunk);
            $stderr .= fread($pipes[2], 8192);
            if (strlen($stderr) > 65536) {
                $stderr = substr($stderr, -65536);
            }
            flush();
        }

        fclose($pipes[1]);
        $stderr .= stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        if (connection_aborted()) {
            proc_terminate($process);
        }

        $exitCode = proc_close($process);
        $stderr = trim($stderr);
        if ($bytesWritten === 0) {
            error_log(sprintf(
                '[CDN stream] FFmpeg pipe produced no bytes exit_code=%s source_host=%s stderr=%s',
                (string) $exitCode,
                (string) (parse_url($sourceUrl, PHP_URL_HOST) ?: ''),
                $stderr !== '' ? $stderr : '(empty)'
            ));

            throw new \RuntimeException('FFmpeg nao retornou dados de midia.');
        }

        if ($exitCode !== 0 || $stderr !== '') {
            error_log(sprintf(
                '[CDN stream] FFmpeg pipe finished with exit_code=%s stderr=%s',
                (string) $exitCode,
                $stderr !== '' ? $stderr : '(empty)'
            ));
        }
    }

    private function runFfmpegToFile(string $ffmpeg, string $sourceUrl, array $outputArgs, string $outputFile): void
    {
        $baseArgs = [
            '-hide_banner',
            '-loglevel', 'error',
            '-y',
            '-reconnect', '1',
            '-reconnect_streamed', '1',
            '-reconnect_delay_max', '4',
            '-fflags', '+genpts+discardcorrupt',
            '-user_agent', self::SOURCE_USER_AGENT,
            '-headers', $this->sourceHeaders($sourceUrl),
            '-i', $sourceUrl,
        ];

        $cmd = $this->buildCommand($ffmpeg, [...$baseArgs, ...$outputArgs, $outputFile]);
        $nullDevice = PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null';
        $process = proc_open($cmd, [
            1 => ['file', $nullDevice, 'w'],
            2 => ['pipe', 'w'],
        ], $pipes);

        if (!is_resource($process)) {
            error_log('[CDN stream] proc_open failed for FFmpeg.');
            throw new \RuntimeException('Nao foi possivel iniciar o FFmpeg.');
        }

        stream_set_blocking($pipes[2], false);
        $stderr = '';
        $exitCode = null;

        do {
            $stderr .= fread($pipes[2], 8192);
            if (strlen($stderr) > 65536) {
                $stderr = substr($stderr, -65536);
            }

            $status = proc_get_status($process);
            if (!($status['running'] ?? false)) {
                $exitCode = $status['exitcode'] ?? null;
                break;
            }

            usleep(100000);
        } while (true);

        $stderr .= stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        proc_close($process);

        $stderr = trim($stderr);
        if ($exitCode !== 0) {
            error_log(sprintf(
                '[CDN stream] FFmpeg failed with exit_code=%s stderr=%s',
                (string) $exitCode,
                $stderr !== '' ? $stderr : '(empty)'
            ));

            throw new \RuntimeException('FFmpeg falhou ao preparar a midia.');
        }
    }

    private function serveFile(string $file, string $contentType, string $mode): void
    {
        if (!is_file($file)) {
            throw new \RuntimeException('Arquivo de cache nao encontrado.');
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
            if ($m[1] === '' && $m[2] !== '') {
                $suffix = min((int) $m[2], $size);
                $start = $size - $suffix;
            } else {
                $start = (int) $m[1];
            }

            if ($m[2] !== '') {
                $end = min((int) $m[2], $end);
            }

            if ($start > $end || $start >= $size) {
                CdnHeaders::rangeNotSatisfiable($size);
                return;
            }

            $status = 206;
        }

        $length = $end - $start + 1;
        http_response_code($status);
        CdnHeaders::file($contentType, $mode);
        header('Content-Length: ' . $length);
        if ($status === 206) {
            header("Content-Range: bytes {$start}-{$end}/{$size}");
        }

        if ($_SERVER['REQUEST_METHOD'] === 'HEAD') {
            return;
        }

        $handle = fopen($file, 'rb');
        if (!$handle) {
            throw new \RuntimeException('Nao foi possivel abrir arquivo de cache.');
        }

        fseek($handle, $start);
        $remaining = $length;
        while ($remaining > 0 && !feof($handle) && !connection_aborted()) {
            $chunk = fread($handle, min(1024 * 256, $remaining));
            if ($chunk === false || $chunk === '') {
                break;
            }

            echo $chunk;
            $remaining -= strlen($chunk);
            flush();
        }

        fclose($handle);
    }

    private function flushHeaders(): void
    {
        while (ob_get_level()) {
            ob_end_flush();
        }

        flush();
    }

    private function cacheDir(): string
    {
        $dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'cdn-cache';
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException('Nao foi possivel criar cache da CDN.');
        }

        return $dir;
    }

    private function sourceHeaders(string $sourceUrl, ?string $sourceOrigin = null): string
    {
        return implode("\r\n", [
            'Accept: video/mp4,video/*;q=0.9,*/*;q=0.8',
            'Connection: keep-alive',
            '',
        ]);
    }

    private function buildCommand(string $binary, array $args): string
    {
        $parts = [escapeshellarg($binary)];
        foreach ($args as $arg) {
            $parts[] = escapeshellarg((string) $arg);
        }
        return implode(' ', $parts);
    }
}
