<?php
class ResponseUtil {
    public static function json($data, $status = 200) {
        $isCacheableResponse = class_exists('ResponseCache', false) && ResponseCache::isActive();
        $headers = ['Content-Type: application/json; charset=utf-8'];

        if ($isCacheableResponse) {
            $headers[] = 'Cache-Control: private, no-cache';
            $headers[] = 'Vary: Cookie';
        } else {
            $headers[] = 'Cache-Control: no-store, no-cache, must-revalidate';
            $headers[] = 'Pragma: no-cache';
        }

        foreach ($headers as $header) {
            header($header);
        }

        http_response_code($status);
        $body = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($isCacheableResponse) {
            header('ETag: "' . sha1($body) . '"');
            ResponseCache::storeActive($body, $status, $headers);
        }

        echo $body;
        exit;
    }
}
