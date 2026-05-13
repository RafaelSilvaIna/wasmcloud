<?php

declare(strict_types=1);

namespace Helpers\Suporte;

/**
 * AES-256-CBC encryption for support messages.
 * Key = PBKDF2(SUPPORT_CIPHER_KEY, chat_id, 100_000, SHA-256) → 32 bytes
 * IV  = random 16 bytes per message, stored as 32-char hex in support_messages.iv
 */
final class SupportCipher
{
    private const CIPHER    = 'aes-256-cbc';
    private const HASH_ALGO = 'sha256';
    private const ITERATIONS = 100_000;
    private const KEY_LEN    = 32;

    private string $masterKey;

    public function __construct()
    {
        $key = defined('SUPPORT_CIPHER_KEY') ? SUPPORT_CIPHER_KEY : '';
        if ($key === '') {
            // Fallback: derive from DB credentials — never empty in production
            $key = hash(self::HASH_ALGO, (DB_PIPO['pass'] ?? '') . (DB_PIPO['name'] ?? '') . 'support_salt_v1');
        }
        $this->masterKey = $key;
    }

    /** Derive a per-chat key using PBKDF2 with the chat_id as additional salt. */
    private function deriveKey(int $chatId): string
    {
        return hash_pbkdf2(
            self::HASH_ALGO,
            $this->masterKey,
            'pipocine_support_' . $chatId,
            self::ITERATIONS,
            self::KEY_LEN,
            true
        );
    }

    /**
     * Encrypt plaintext for a given chat.
     * Returns ['ciphertext' => base64, 'iv' => 32-char hex]
     */
    public function encrypt(string $plaintext, int $chatId): array
    {
        $key = $this->deriveKey($chatId);
        $ivRaw = random_bytes(16);
        $encrypted = openssl_encrypt($plaintext, self::CIPHER, $key, OPENSSL_RAW_DATA, $ivRaw);

        if ($encrypted === false) {
            throw new \RuntimeException('Encryption failed.');
        }

        return [
            'ciphertext' => base64_encode($encrypted),
            'iv'         => bin2hex($ivRaw),
        ];
    }

    /** Decrypt a stored ciphertext back to plaintext. */
    public function decrypt(string $ciphertext, string $ivHex, int $chatId): string
    {
        $key    = $this->deriveKey($chatId);
        $ivRaw  = hex2bin($ivHex);
        $result = openssl_decrypt(base64_decode($ciphertext), self::CIPHER, $key, OPENSSL_RAW_DATA, $ivRaw);

        if ($result === false) {
            return '';
        }

        return $result;
    }
}
