<?php

declare(strict_types=1);

final class SecurityChallengeHook
{
    public static function injectClientBridge(): void
    {
        static $started = false;
        if ($started) {
            return;
        }
        $started = true;

        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        if (str_starts_with($path, '/api/') || str_starts_with($path, '/cdn/')) {
            return;
        }

        ob_start(static function (string $html): string {
            if (stripos($html, '</body>') === false) {
                return $html;
            }

            $script = <<<'HTML'
<script>
(() => {
    if (window.__pipoSecurityBridgeInstalled) return;
    window.__pipoSecurityBridgeInstalled = true;

    const redirectToChallenge = (url = '/security/challenge') => {
        if (window.location.pathname !== '/security/challenge') {
            window.location.href = url;
        }
    };

    const originalFetch = window.fetch;
    window.fetch = async (...args) => {
        const response = await originalFetch(...args);
        const contentType = response.headers.get('content-type') || '';

        if (contentType.includes('application/json')) {
            try {
                const payload = await response.clone().json();
                if (payload && payload.security_challenge) {
                    redirectToChallenge(payload.challenge_url || '/security/challenge');
                }
            } catch (_) {}
        }

        return response;
    };

    const originalOpen = XMLHttpRequest.prototype.open;
    XMLHttpRequest.prototype.open = function (...args) {
        this.addEventListener('load', function () {
            const contentType = this.getResponseHeader('content-type') || '';
            if (!contentType.includes('application/json')) return;
            try {
                const payload = JSON.parse(this.responseText || '{}');
                if (payload && payload.security_challenge) {
                    redirectToChallenge(payload.challenge_url || '/security/challenge');
                }
            } catch (_) {}
        });
        return originalOpen.apply(this, args);
    };
})();
</script>
HTML;

            return str_ireplace('</body>', $script . "\n</body>", $html);
        });
    }
}
