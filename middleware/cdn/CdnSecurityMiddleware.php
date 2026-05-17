<?php

declare(strict_types=1);

namespace Middleware\Cdn;

use Helpers\Cdn\CdnHeaders;
use Services\Cdn\CdnTokenService;

require_once __DIR__ . '/../../helpers/cdn/CdnHeaders.php';
require_once __DIR__ . '/../../services/cdn/CdnTokenService.php';

final class CdnSecurityMiddleware
{
    public function __construct(private ?CdnTokenService $tokens = null)
    {
        $this->tokens ??= new CdnTokenService();
    }

    public function authorize(string $token, string $kind, ?string $profile = null): ?array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'HEAD') {
            CdnHeaders::noStore();
            http_response_code(405);
            return null;
        }

        if (empty($_SESSION['user_id']) || empty($_SESSION['profile_id'])) {
            CdnHeaders::noStore();
            http_response_code(401);
            return null;
        }

        if (!$this->sameOriginReferrer()) {
            CdnHeaders::noStore();
            http_response_code(403);
            return null;
        }

        $claims = $this->tokens->validate($token, $kind, $profile);
        if (!$claims) {
            CdnHeaders::noStore();
            http_response_code(403);
            return null;
        }

        return $claims;
    }

    private function sameOriginReferrer(): bool
    {
        $ref = (string) ($_SERVER['HTTP_REFERER'] ?? '');
        if ($ref === '') {
            return true;
        }

        $refHost = strtolower((string) parse_url($ref, PHP_URL_HOST));
        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        return $refHost === $host;
    }
}
