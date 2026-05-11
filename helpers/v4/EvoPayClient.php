<?php

declare(strict_types=1);

namespace Helpers\V4;

use RuntimeException;

class EvoPayClient
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct(array $config)
    {
        $this->apiKey = trim((string) ($config['api_key'] ?? '05165218-a666-4b15-9c82-b04387ae8d57'));
        $this->baseUrl = rtrim((string) ($config['base_url'] ?? 'https://pix.evopay.cash/v1'), '/');

        if ($this->apiKey === '') {
            throw new RuntimeException('Chave da EvoPay nao configurada.');
        }
    }

    public function createPix(float $amount, string $callbackUrl): array
    {
        $data = $this->request('POST', '/pix', [
            'amount' => $amount,
            'callbackUrl' => $callbackUrl,
        ]);

        if (empty($data['id'])) {
            throw new RuntimeException('EvoPay nao retornou o identificador da transacao.');
        }

        return $data;
    }

    public function transaction(string $txid): array
    {
        return $this->request('GET', '/transactions/' . rawurlencode($txid));
    }

    private function request(string $method, string $endpoint, array $body = []): array
    {
        $ch = curl_init($this->baseUrl . $endpoint);
        if ($ch === false) {
            throw new RuntimeException('Nao foi possivel iniciar conexao com EvoPay.');
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => [
                'API-Key: ' . $this->apiKey,
                'Content-Type: application/json',
            ],
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $raw = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            throw new RuntimeException('Erro de conexao com EvoPay: ' . $error);
        }

        $data = json_decode((string) $raw, true);
        if (!is_array($data)) {
            throw new RuntimeException('Resposta invalida da EvoPay.');
        }

        if ($httpCode >= 400 || isset($data['error'])) {
            $message = $data['error'] ?? $data['message'] ?? ('Erro HTTP ' . $httpCode);
            throw new RuntimeException('EvoPay: ' . $message);
        }

        return $data;
    }
}
