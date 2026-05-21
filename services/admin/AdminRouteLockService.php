<?php
declare(strict_types=1);

namespace Services\Admin;

use Models\Admin\AdminRouteLockModel;

final class AdminRouteLockService
{
    private array $allowedTypes = ['exact', 'prefix', 'regex'];

    public function __construct(
        private AdminRouteLockModel $locks,
        private string $rootDir
    ) {
        $this->locks->ensureSchema();
    }

    public function routes(?string $query = null): array
    {
        $knownLocks = [];
        foreach ($this->locks->allLocks() as $lock) {
            $knownLocks[$this->identity($lock['route_path'], $lock['match_type'])] = $lock;
        }

        $routes = $this->discoverRoutes();
        foreach ($routes as &$route) {
            $lock = $knownLocks[$this->identity($route['route_path'], $route['match_type'])] ?? null;
            $route['is_locked'] = $lock ? (bool) $lock['is_locked'] : false;
            $route['lock_id'] = $lock['id'] ?? null;
            $route['maintenance_title'] = $lock['maintenance_title'] ?? 'Pagina em manutencao';
            $route['maintenance_message'] = $lock['maintenance_message'] ?? null;
            $route['locked_at'] = $lock['locked_at'] ?? null;
            $route['updated_at'] = $lock['updated_at'] ?? null;
        }
        unset($route);

        foreach ($knownLocks as $key => $lock) {
            if (isset($routes[$key])) {
                continue;
            }
            $routes[$key] = [
                'route_path' => $lock['route_path'],
                'match_type' => $lock['match_type'],
                'page_file' => $lock['page_file'],
                'route_label' => $lock['route_label'] ?: 'Manual',
                'source' => 'manual',
                'is_locked' => (bool) $lock['is_locked'],
                'lock_id' => $lock['id'],
                'maintenance_title' => $lock['maintenance_title'],
                'maintenance_message' => $lock['maintenance_message'],
                'locked_at' => $lock['locked_at'],
                'updated_at' => $lock['updated_at'],
            ];
        }

        $items = array_values($routes);
        if ($query !== null && trim($query) !== '') {
            $needle = strtolower(trim($query));
            $items = array_values(array_filter($items, static function (array $route) use ($needle): bool {
                return str_contains(strtolower((string) $route['route_path']), $needle)
                    || str_contains(strtolower((string) ($route['page_file'] ?? '')), $needle)
                    || str_contains(strtolower((string) ($route['route_label'] ?? '')), $needle);
            }));
        }

        usort($items, static function (array $a, array $b): int {
            return [$b['is_locked'], $a['route_path']] <=> [$a['is_locked'], $b['route_path']];
        });

        return [
            'success' => true,
            'routes' => $items,
            'total' => count($items),
        ];
    }

    public function lock(array $payload, int $adminId): array
    {
        $route = $this->sanitizePayload($payload);
        $lock = $this->locks->setLock($route, true, $adminId);

        return [
            'success' => true,
            'route' => $lock,
            'message' => 'Rota fechada para manutencao.',
        ];
    }

    public function unlock(array $payload, int $adminId): array
    {
        $route = $this->sanitizePayload($payload);
        $lock = $this->locks->setLock($route, false, $adminId);

        return [
            'success' => true,
            'route' => $lock,
            'message' => 'Rota aberta.',
        ];
    }

    public function delete(array $payload): array
    {
        $route = $this->sanitizePayload($payload, false);
        $this->locks->deleteLock($route['route_path'], $route['match_type']);

        return [
            'success' => true,
            'message' => 'Regra removida.',
        ];
    }

    public function logs(string $range = '1d', int $limit = 80): array
    {
        return [
            'success' => true,
            'logs' => $this->locks->recentLogs($limit),
            'stats' => $this->locks->routeStats($this->since($range), 25),
        ];
    }

    public function findActiveLockForPath(string $path): ?array
    {
        $path = $this->normalizePath($path);
        foreach ($this->locks->activeLocks() as $lock) {
            if ($this->matches($path, (string) $lock['route_path'], (string) $lock['match_type'])) {
                return $lock;
            }
        }

        return null;
    }

    private function discoverRoutes(): array
    {
        $routes = [];
        $this->scanPages($routes);
        $this->parseRouterFile($this->rootDir . '/pages/index.php', 'pages/index.php', $routes);
        $this->parseRouterFile($this->rootDir . '/routes/index.php', 'routes/index.php', $routes);
        $this->parseHtaccess($routes);

        return $routes;
    }

    private function scanPages(array &$routes): void
    {
        $pagesDir = $this->rootDir . '/pages';
        if (!is_dir($pagesDir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($pagesDir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($this->rootDir) + 1));
            if ($relative === 'pages/d2xs8d3sdfsegequ6249f.php') {
                continue;
            }

            $route = substr($relative, strlen('pages'));
            $route = preg_replace('/\.php$/', '', $route) ?: '';
            if ($route === '/index') {
                $route = '/';
            } elseif (str_ends_with($route, '/index')) {
                $route = substr($route, 0, -6);
            }

            $this->addRoute($routes, [
                'route_path' => $this->normalizePath($route),
                'match_type' => 'exact',
                'page_file' => $relative,
                'route_label' => 'Arquivo em pages/',
                'source' => 'pages',
            ]);
        }
    }

    private function parseRouterFile(string $file, string $source, array &$routes): void
    {
        if (!is_file($file)) {
            return;
        }

        $content = (string) file_get_contents($file);

        if (preg_match_all('/\$requestUri\s*={2,3}\s*[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
            foreach ($matches[1] as $path) {
                $this->addRoute($routes, [
                    'route_path' => $this->normalizePath($path),
                    'match_type' => 'exact',
                    'page_file' => $source,
                    'route_label' => 'Rota do roteador',
                    'source' => $source,
                ]);
            }
        }

        if (preg_match_all('/str_starts_with\(\s*\$requestUri\s*,\s*[\'"]([^\'"]+)[\'"]\s*\)/', $content, $matches)) {
            foreach ($matches[1] as $path) {
                $this->addRoute($routes, [
                    'route_path' => $this->normalizePath($path),
                    'match_type' => 'prefix',
                    'page_file' => $source,
                    'route_label' => 'Prefixo do roteador',
                    'source' => $source,
                ]);
            }
        }

        if (preg_match_all('/strpos\(\s*\$requestUri\s*,\s*[\'"]([^\'"]+)[\'"]\s*\)\s*={2,3}\s*0/', $content, $matches)) {
            foreach ($matches[1] as $path) {
                $this->addRoute($routes, [
                    'route_path' => $this->normalizePath($path),
                    'match_type' => 'prefix',
                    'page_file' => $source,
                    'route_label' => 'Prefixo do roteador',
                    'source' => $source,
                ]);
            }
        }

        if (preg_match_all('/preg_match\(\s*([\'"])(.+?)\1\s*,\s*\$requestUri/s', $content, $matches)) {
            foreach ($matches[2] as $pattern) {
                $this->addRoute($routes, [
                    'route_path' => $pattern,
                    'match_type' => 'regex',
                    'page_file' => $source,
                    'route_label' => 'Padrao dinamico',
                    'source' => $source,
                ]);
            }
        }
    }

    private function parseHtaccess(array &$routes): void
    {
        $file = $this->rootDir . '/.htaccess';
        if (!is_file($file)) {
            return;
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (!str_starts_with($line, 'RewriteRule ') || !str_contains($line, ' pages/')) {
                continue;
            }

            if (!preg_match('/^RewriteRule\s+(\S+)\s+(pages\/\S+)/', $line, $m)) {
                continue;
            }

            $pageFile = preg_replace('/\?.*$/', '', $m[2]);
            if (
                str_contains($pageFile, 'd2xs8d3sdfsegequ6249f.php')
                || $pageFile === 'pages/$1'
                || str_starts_with($m[1], '^pages/')
                || $m[1] === '^(.*)$'
            ) {
                continue;
            }

            $sourcePattern = trim($m[1], '^$');
            $pattern = '~^/' . str_replace('~', '\\~', $sourcePattern) . '$~';
            $this->addRoute($routes, [
                'route_path' => $pattern,
                'match_type' => 'regex',
                'page_file' => $pageFile,
                'route_label' => 'RewriteRule',
                'source' => '.htaccess',
            ]);
        }
    }

    private function addRoute(array &$routes, array $route): void
    {
        if ($this->isInternalRoute((string) $route['route_path'])) {
            return;
        }

        $key = $this->identity((string) $route['route_path'], (string) $route['match_type']);
        if (!isset($routes[$key])) {
            $routes[$key] = $route;
        }
    }

    private function sanitizePayload(array $payload, bool $includeMessage = true): array
    {
        $matchType = strtolower(trim((string) ($payload['match_type'] ?? 'exact')));
        if (!in_array($matchType, $this->allowedTypes, true)) {
            throw new \InvalidArgumentException('Tipo de rota invalido.');
        }

        $path = trim((string) ($payload['route_path'] ?? ''));
        if ($path === '') {
            throw new \InvalidArgumentException('Informe a rota.');
        }

        if ($matchType !== 'regex') {
            $path = $this->normalizePath($path);
            if ($this->isInternalRoute($path)) {
                throw new \InvalidArgumentException('Esta rota nao pode ser fechada por este modulo.');
            }
        } elseif (@preg_match($path, '') === false) {
            throw new \InvalidArgumentException('Regex invalido.');
        }

        return [
            'route_path' => $path,
            'match_type' => $matchType,
            'page_file' => substr((string) ($payload['page_file'] ?? ''), 0, 255) ?: null,
            'route_label' => substr((string) ($payload['route_label'] ?? 'Manual'), 0, 160),
            'maintenance_title' => $includeMessage
                ? substr(trim((string) ($payload['maintenance_title'] ?? 'Pagina em manutencao')), 0, 120)
                : 'Pagina em manutencao',
            'maintenance_message' => $includeMessage
                ? substr(trim((string) ($payload['maintenance_message'] ?? 'Estamos ajustando esta area. Volte em instantes.')), 0, 500)
                : null,
        ];
    }

    private function normalizePath(string $path): string
    {
        $path = parse_url($path, PHP_URL_PATH) ?: '/';
        $path = '/' . ltrim($path, '/');
        $path = preg_replace('#/+#', '/', $path) ?: '/';
        if ($path === '') {
            return '/';
        }

        return rtrim($path, '/') ?: '/';
    }

    private function matches(string $path, string $route, string $type): bool
    {
        if ($type === 'exact') {
            return $path === $this->normalizePath($route);
        }

        if ($type === 'prefix') {
            $route = $this->normalizePath($route);
            return $path === $route || str_starts_with($path, rtrim($route, '/') . '/');
        }

        return @preg_match($route, $path) === 1;
    }

    private function isInternalRoute(string $path): bool
    {
        if ($path === '') {
            return true;
        }

        if ($path[0] !== '/') {
            return false;
        }

        foreach (['/api/', '/assets/', '/cdn/', '/webhooks/', '/security/', '/d2xs8d3sdfsegequ6249f', '/routes/'] as $prefix) {
            if (str_starts_with($path, rtrim($prefix, '/'))) {
                return true;
            }
        }

        return false;
    }

    private function since(string $range): string
    {
        $map = [
            '1h' => '-1 hour',
            '1d' => '-1 day',
            '1w' => '-1 week',
            '1m' => '-1 month',
        ];

        return date('Y-m-d H:i:s', strtotime($map[$range] ?? $map['1d']));
    }

    private function identity(string $routePath, string $matchType): string
    {
        return $matchType . '::' . $routePath;
    }
}
