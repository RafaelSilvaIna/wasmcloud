<?php

class ResponseCache {
    private const CACHEABLE_TTLS = [
        '/api/v2/trending' => 300,
        '/api/v2/conteudo' => 600,
    ];

    private const STALE_TTL = 1800;
    private const WAIT_FOR_FILL_MICROSECONDS = 800000;
    private const WAIT_STEP_MICROSECONDS = 50000;

    private static ?self $active = null;

    private string $key;
    private string $file;
    private string $lockFile;
    private int $ttl;
    private $lockHandle = null;

    public static function bootstrapForRequest(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
            return;
        }

        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
        $ttl = self::ttlForPath($path);
        if ($ttl === null) {
            return;
        }

        $variant = !empty($_SESSION['profile_is_kids']) ? 'kids' : 'standard';
        $scope = hash('sha256', implode('|', [
            (string) ($_SESSION['user_id'] ?? 'guest'),
            (string) ($_SESSION['profile_id'] ?? 'none'),
            session_status() === PHP_SESSION_ACTIVE ? session_id() : 'no-session',
        ]));
        $query = $_GET;
        ksort($query);

        $keySource = implode('|', [
            'v2-response-cache',
            $path,
            http_build_query($query),
            'profile=' . $variant,
            'scope=' . $scope,
        ]);

        $cache = new self(hash('sha256', $keySource), $ttl);
        self::$active = $cache;
        $cache->serveOrPrepare($variant);
    }

    public static function storeActive(string $body, int $status, array $headers = []): void {
        if (self::$active === null) {
            return;
        }

        self::$active->store($body, $status, $headers);
    }

    public static function isActive(): bool {
        return self::$active !== null;
    }

    public static function markMissHeader(): void {
        if (self::$active !== null && !headers_sent()) {
            header('X-Origin-Cache: MISS');
        }
    }

    private static function ttlForPath(string $path): ?int {
        foreach (self::CACHEABLE_TTLS as $prefix => $ttl) {
            if (strpos($path, $prefix) === 0) {
                return $ttl;
            }
        }

        return null;
    }

    private function __construct(string $key, int $ttl) {
        $this->key = $key;
        $this->ttl = $ttl;

        $dir = dirname(__DIR__, 2) . '/data/cache/api-response';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $this->file = $dir . '/' . $key . '.json';
        $this->lockFile = $dir . '/' . $key . '.lock';
    }

    private function serveOrPrepare(string $variant): void {
        $entry = $this->readEntry();
        if ($this->isFresh($entry)) {
            $this->emit($entry, 'HIT', $variant);
        }

        if (!$this->acquireLock()) {
            $freshAfterWait = $this->waitForFreshEntry();
            if ($this->isFresh($freshAfterWait)) {
                $this->emit($freshAfterWait, 'HIT-WAIT', $variant);
            }

            if ($this->isStale($entry)) {
                $this->emit($entry, 'STALE', $variant);
            }
        }

        self::markMissHeader();
        if (!headers_sent()) {
            header('X-Cache-Variant: profile=' . $variant);
        }
    }

    private function store(string $body, int $status, array $headers): void {
        if ($status !== 200 || $body === '' || $this->lockHandle === null) {
            $this->releaseLock();
            return;
        }

        $now = time();
        $entry = [
            'status' => $status,
            'headers' => $headers,
            'body' => $body,
            'created_at' => $now,
            'expires_at' => $now + $this->ttl,
            'stale_until' => $now + $this->ttl + self::STALE_TTL,
            'etag' => '"' . sha1($body) . '"',
        ];

        $tmp = $this->file . '.' . getmypid() . '.tmp';
        file_put_contents($tmp, json_encode($entry, JSON_UNESCAPED_SLASHES), LOCK_EX);
        rename($tmp, $this->file);
        $this->releaseLock();
    }

    private function emit(array $entry, string $cacheStatus, string $variant): void {
        $status = (int)($entry['status'] ?? 200);
        $body = (string)($entry['body'] ?? '');
        $etag = (string)($entry['etag'] ?? '');

        if ($etag !== '' && trim($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
            http_response_code(304);
            $body = '';
        } else {
            http_response_code($status);
        }

        foreach (($entry['headers'] ?? []) as $header) {
            header($header);
        }

        $age = max(0, time() - (int)($entry['created_at'] ?? time()));
        header('Age: ' . $age);
        header('ETag: ' . $etag);
        header('X-Origin-Cache: ' . $cacheStatus);
        header('X-Cache-Variant: profile=' . $variant);

        echo $body;
        exit;
    }

    private function waitForFreshEntry(): ?array {
        $deadline = microtime(true) + (self::WAIT_FOR_FILL_MICROSECONDS / 1000000);

        do {
            usleep(self::WAIT_STEP_MICROSECONDS);
            $entry = $this->readEntry();
            if ($this->isFresh($entry)) {
                return $entry;
            }
        } while (microtime(true) < $deadline);

        return null;
    }

    private function readEntry(): ?array {
        if (!is_file($this->file)) {
            return null;
        }

        $raw = file_get_contents($this->file);
        $entry = json_decode($raw ?: '', true);
        return is_array($entry) ? $entry : null;
    }

    private function isFresh(?array $entry): bool {
        return is_array($entry) && (int)($entry['expires_at'] ?? 0) >= time();
    }

    private function isStale(?array $entry): bool {
        return is_array($entry) && (int)($entry['stale_until'] ?? 0) >= time();
    }

    private function acquireLock(): bool {
        $this->lockHandle = fopen($this->lockFile, 'c');
        if (!$this->lockHandle) {
            return false;
        }

        if (flock($this->lockHandle, LOCK_EX | LOCK_NB)) {
            return true;
        }

        fclose($this->lockHandle);
        $this->lockHandle = null;
        return false;
    }

    private function releaseLock(): void {
        if ($this->lockHandle === null) {
            return;
        }

        flock($this->lockHandle, LOCK_UN);
        fclose($this->lockHandle);
        $this->lockHandle = null;
    }
}
