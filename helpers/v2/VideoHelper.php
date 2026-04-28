<?php
// rafaelsilvaina/pipocine/helpers/v2/VideoHelper.php

class VideoHelper {
    public static function formatProxyUrl(string $url, string $host) {
        if (empty($url)) return null;
        if (strpos($url, 'hubby') !== false) {
            // Exemplo de lógica de substituição para CDN/Proxy interna
            return "https://cdn.cineveo.site/stream?url=" . urlencode($url);
        }
        return $url;
    }
}