<?php
declare(strict_types=1);

namespace Helpers\Ads;

final class AdsFeature
{
    public const PUBLIC_ENABLED = false;

    public static function isPublicEnabled(): bool
    {
        return self::PUBLIC_ENABLED;
    }

    public static function denyPublicAccess(): never
    {
        http_response_code(404);
        echo "
        <div style='background:#0a0a0a;color:#fff;height:100vh;display:flex;
                    align-items:center;justify-content:center;font-family:sans-serif;'>
            <div style='text-align:center;'>
                <h1 style='font-size:4rem;margin:0;font-weight:700;'>404</h1>
                <p style='color:#888;margin:12px 0 24px;'>Oops! essa funcionalidade foi descontinuada.</p>
                <a href='/home' style='color:#e50914;text-decoration:none;font-weight:600;
                                       font-size:14px;border:1px solid #e50914;
                                       padding:10px 24px;border-radius:6px;'>
                    Voltar para a Home
                </a>
            </div>
        </div>";
        exit;
    }

    public static function denyPublicApiAccess(): never
    {
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'Endpoint nÃ£o encontrado.',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
