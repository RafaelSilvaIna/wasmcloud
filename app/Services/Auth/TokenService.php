<?php
namespace App\Services\Auth;

class TokenService {
    public static function generateTemporaryToken($userId, $role) {
        $expirationTime = 900; 
        
        if ($role === 'coordinator') {
            $expirationTime = 7200; 
        } elseif ($role === 'student' || $role === 'family') {
            $expirationTime = 172800; 
        }

        $payload = [
            'iss' => 'customizable_school_system',
            'iat' => time(),
            'exp' => time() + $expirationTime,
            'sub' => $userId,
            'role' => $role
        ];
        
        $config = require __DIR__ . '/../../../config/security.php';
        $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $payloadEncoded = base64_encode(json_encode($payload));
        
        $signature = hash_hmac('sha256', "$header.$payloadEncoded", $config['encryption_key'], true);
        $signatureEncoded = base64_encode($signature);
        
        return "$header.$payloadEncoded.$signatureEncoded";
    }

    public static function validateToken($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return false;

        list($header, $payload, $signature) = $parts;
        
        $config = require __DIR__ . '/../../../config/security.php';
        $validSignature = base64_encode(hash_hmac('sha256', "$header.$payload", $config['encryption_key'], true));
        
        if (!hash_equals($validSignature, $signature)) return false;

        $payloadData = json_decode(base64_decode($payload), true);
        if ($payloadData['exp'] < time()) return false;

        return $payloadData;
    }
}