<?php
namespace App\Services;

class SecurityVault {
    public static function sanitize($data) {
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }

    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public static function encrypt($data) {
        if (empty($data)) return null;
        $config = require __DIR__ . '/../../config/security.php';
        $key = hash('sha256', $config['encryption_key'], true);
        $iv = random_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    public static function decrypt($data) {
        if (empty($data)) return null;
        $config = require __DIR__ . '/../../config/security.php';
        $key = hash('sha256', $config['encryption_key'], true);
        $decoded = base64_decode($data);
        $ivLength = openssl_cipher_iv_length('aes-256-cbc');
        $iv = substr($decoded, 0, $ivLength);
        $encrypted = substr($decoded, $ivLength);
        return openssl_decrypt($encrypted, 'aes-256-cbc', $key, 0, $iv);
    }

    public static function generateBlindIndex($data) {
        if (empty($data)) return null;
        $config = require __DIR__ . '/../../config/security.php';
        return hash_hmac('sha256', preg_replace('/[^0-9]/', '', $data), $config['encryption_key']);
    }

    public static function maskCpf($cpf) {
        if (empty($cpf)) return 'Não registado';
        $clean = preg_replace('/[^0-9]/', '', $cpf);
        if (strlen($clean) !== 11) return '***.***.***-**';
        return '***.***.' . substr($clean, 6, 3) . '-' . substr($clean, 9, 2);
    }
}