<?php
namespace App\Middlewares;

class RateLimitMiddleware {
    public static function check() {
        $ip = $_SERVER['REMOTE_ADDR'];
        $logFile = __DIR__ . '/../../storage/logs/rate_limit.json';
        $data = file_exists($logFile) ? json_decode(file_get_contents($logFile), true) : [];
        $time = time();

        if (isset($data[$ip])) {
            if ($data[$ip]['blocked_until'] > $time) {
                self::abort(429, 'IP temporariamente bloqueado.');
            }

            if ($time - $data[$ip]['start_time'] > 60) {
                $data[$ip] = ['count' => 1, 'start_time' => $time, 'blocked_until' => 0];
            } else {
                $data[$ip]['count']++;
                if ($data[$ip]['count'] > 100) {
                    $data[$ip]['blocked_until'] = $time + 180;
                    file_put_contents($logFile, json_encode($data));
                    self::abort(429, 'Limite de requisições excedido. Bloqueio de 3 minutos.');
                }
            }
        } else {
            $data[$ip] = ['count' => 1, 'start_time' => $time, 'blocked_until' => 0];
        }

        file_put_contents($logFile, json_encode($data));
    }

    private static function abort($code, $message) {
        http_response_code($code);
        echo json_encode(['error' => $message]);
        exit;
    }
}