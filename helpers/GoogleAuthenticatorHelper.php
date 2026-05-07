<?php
/**
 * Google Authenticator Helper
 * 
 * Implementação TOTP (Time-based One-Time Password) compatível com Google Authenticator
 * RFC 6238
 */

declare(strict_types=1);

class GoogleAuthenticatorHelper
{
    private const CODE_LENGTH = 6;
    private const TIME_STEP = 30; // segundos
    private const SECRET_LENGTH = 16; // caracteres base32
    
    /**
     * Gera uma chave secreta base32 aleatória
     */
    public static function generateSecret(): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; // Base32 charset
        $secret = '';
        
        for ($i = 0; $i < self::SECRET_LENGTH; $i++) {
            $secret .= $chars[random_int(0, 31)];
        }
        
        return $secret;
    }
    
    /**
     * Gera o QR Code URL (usando API externa ou data URI)
     */
    public static function getQRCodeUrl(string $username, string $secret, string $issuer = 'PipoCine'): string
    {
        // Formato otpauth://totp/ISSUER:USERNAME?secret=SECRET&issuer=ISSUER
        $label = urlencode($issuer . ':' . $username);
        $issuerEncoded = urlencode($issuer);
        
        $otpauth = sprintf(
            'otpauth://totp/%s?secret=%s&issuer=%s&algorithm=SHA1&digits=%d&period=%d',
            $label,
            $secret,
            $issuerEncoded,
            self::CODE_LENGTH,
            self::TIME_STEP
        );
        
        return $otpauth;
    }
    
    /**
     * Gera URL do QR Code usando API do Google Charts (ou alternativa)
     */
    public static function getQRCodeImageUrl(string $otpauthUrl, int $size = 200): string
    {
        // Usando API do QRServer (mais confiável que Google Charts deprecado)
        return 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . urlencode($otpauthUrl);
    }
    
    /**
     * Gera código TOTP para um timestamp específico
     */
    public static function generateCode(string $secret, ?int $timestamp = null): string
    {
        $timestamp = $timestamp ?? time();
        $timeStep = floor($timestamp / self::TIME_STEP);
        
        $secret = self::base32Decode($secret);
        $time = pack('N*', 0) . pack('N*', $timeStep);
        
        $hm = hash_hmac('sha1', $time, $secret, true);
        $offset = ord($hm[19]) & 0x0F;
        
        $code = (
            ((ord($hm[$offset]) & 0x7F) << 24) |
            ((ord($hm[$offset + 1]) & 0xFF) << 16) |
            ((ord($hm[$offset + 2]) & 0xFF) << 8) |
            (ord($hm[$offset + 3]) & 0xFF)
        ) % (10 ** self::CODE_LENGTH);
        
        return str_pad((string)$code, self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }
    
    /**
     * Verifica um código TOTP (com tolerância de +/- 1 time step)
     */
    public static function verifyCode(string $secret, string $code, int $window = 1): bool
    {
        $timestamp = time();
        
        for ($i = -$window; $i <= $window; $i++) {
            $checkTime = $timestamp + ($i * self::TIME_STEP);
            if (self::generateCode($secret, $checkTime) === $code) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Gera códigos de backup (8 códigos de 8 dígitos)
     */
    public static function generateBackupCodes(int $count = 8): array
    {
        $codes = [];
        
        for ($i = 0; $i < $count; $i++) {
            $code = '';
            for ($j = 0; $j < 8; $j++) {
                $code .= random_int(0, 9);
            }
            $codes[] = $code;
        }
        
        return $codes;
    }
    
    /**
     * Decodifica base32
     */
    private static function base32Decode(string $input): string
    {
        $map = [
            'A' => 0, 'B' => 1, 'C' => 2, 'D' => 3, 'E' => 4, 'F' => 5, 'G' => 6, 'H' => 7,
            'I' => 8, 'J' => 9, 'K' => 10, 'L' => 11, 'M' => 12, 'N' => 13, 'O' => 14, 'P' => 15,
            'Q' => 16, 'R' => 17, 'S' => 18, 'T' => 19, 'U' => 20, 'V' => 21, 'W' => 22, 'X' => 23,
            'Y' => 24, 'Z' => 25, '2' => 26, '3' => 27, '4' => 28, '5' => 29, '6' => 30, '7' => 31
        ];
        
        $input = strtoupper(str_replace('=', '', $input));
        $output = '';
        $buffer = 0;
        $bufferSize = 0;
        
        for ($i = 0; $i < strlen($input); $i++) {
            $char = $input[$i];
            if (!isset($map[$char])) {
                continue;
            }
            
            $buffer = ($buffer << 5) | $map[$char];
            $bufferSize += 5;
            
            if ($bufferSize >= 8) {
                $bufferSize -= 8;
                $output .= chr(($buffer >> $bufferSize) & 0xFF);
            }
        }
        
        return $output;
    }
    
    /**
     * Gera um token único para dispositivo confiável
     */
    public static function generateDeviceToken(): string
    {
        return bin2hex(random_bytes(32)); // 64 caracteres hex
    }
    
    /**
     * Detecta navegador/OS a partir do User-Agent
     */
    public static function parseUserAgent(string $userAgent): string
    {
        $device = 'Dispositivo Desconhecido';
        
        if (strpos($userAgent, 'Windows') !== false) {
            $device = 'Windows';
        } elseif (strpos($userAgent, 'Macintosh') !== false || strpos($userAgent, 'Mac OS') !== false) {
            $device = 'macOS';
        } elseif (strpos($userAgent, 'Linux') !== false) {
            $device = 'Linux';
        } elseif (strpos($userAgent, 'Android') !== false) {
            $device = 'Android';
        } elseif (strpos($userAgent, 'iPhone') !== false) {
            $device = 'iPhone';
        } elseif (strpos($userAgent, 'iPad') !== false) {
            $device = 'iPad';
        }
        
        if (strpos($userAgent, 'Chrome') !== false) {
            $device .= ' - Chrome';
        } elseif (strpos($userAgent, 'Firefox') !== false) {
            $device .= ' - Firefox';
        } elseif (strpos($userAgent, 'Safari') !== false) {
            $device .= ' - Safari';
        } elseif (strpos($userAgent, 'Edge') !== false) {
            $device .= ' - Edge';
        }
        
        return $device;
    }
}
