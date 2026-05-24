<?php
declare(strict_types=1);

namespace Services\Admin;

use Models\Admin\AdminBoxModel;

final class AdminBoxService
{
    private const TYPES = [
        'message' => 'admin_message',
        'link' => 'admin_link',
        'survey' => 'admin_survey',
        'notice' => 'admin_notice',
    ];

    public function __construct(private AdminBoxModel $box)
    {
        $this->box->ensureSchema();
    }

    public function dashboard(): array
    {
        return [
            'success' => true,
            'summary' => $this->box->summary(),
            'campaigns' => array_map([$this, 'normalizeCampaign'], $this->box->campaigns()),
        ];
    }

    public function searchUsers(string $query): array
    {
        $query = trim($query);
        if (strlen($query) < 2) {
            return ['success' => true, 'users' => []];
        }

        return [
            'success' => true,
            'users' => array_map([$this, 'normalizeUser'], $this->box->findUsers($query)),
        ];
    }

    public function send(int $adminId, array $input): array
    {
        if ($adminId <= 0) {
            return ['success' => false, 'error' => 'Administrador invalido.'];
        }

        $audience = (string) ($input['audience'] ?? 'user');
        $kind = (string) ($input['kind'] ?? 'message');
        $type = self::TYPES[$kind] ?? self::TYPES['message'];
        $title = $this->cleanText((string) ($input['title'] ?? ''), 160);
        $body = $this->cleanText((string) ($input['body'] ?? ''), 600);
        $targetEmail = strtolower(trim((string) ($input['target_email'] ?? '')));
        $actionUrl = trim((string) ($input['action_url'] ?? ''));
        $actionLabel = $this->cleanText((string) ($input['action_label'] ?? ''), 80);
        $tone = $this->safeTone((string) ($input['tone'] ?? 'info'));

        if ($title === '' || $body === '') {
            return ['success' => false, 'error' => 'Informe titulo e mensagem.'];
        }

        if ($kind === 'survey' || $kind === 'link') {
            if ($actionUrl === '') {
                return ['success' => false, 'error' => 'Este tipo de envio precisa de um link.'];
            }
        }

        if ($actionUrl !== '' && !$this->isSafeUrl($actionUrl)) {
            return ['success' => false, 'error' => 'Use um link interno ou uma URL http/https valida.'];
        }

        if ($actionUrl !== '' && $actionLabel === '') {
            $actionLabel = $kind === 'survey' ? 'Responder pesquisa' : 'Abrir link';
        }

        $payload = [
            'tone' => $tone,
            'admin_box' => '1',
        ];

        if ($actionUrl !== '') {
            $payload['action_url'] = $actionUrl;
            $payload['action_label'] = $actionLabel;
        }

        if ($audience === 'all') {
            $count = $this->box->createForAll($adminId, $type, $title, $body, $payload);
            return [
                'success' => true,
                'message' => 'Envio publicado na Box dos usuarios ativos.',
                'recipients' => $count,
            ];
        }

        if (!filter_var($targetEmail, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Digite um e-mail valido para envio individual.'];
        }

        $user = $this->box->userByEmail($targetEmail);
        if (!$user || ($user['status'] ?? '') !== 'active') {
            return ['success' => false, 'error' => 'Usuario ativo nao encontrado com este e-mail.'];
        }

        $payload['target_email'] = (string) $user['email'];
        $itemId = $this->box->createForUser($adminId, (int) $user['id'], $type, $title, $body, $payload);

        return [
            'success' => true,
            'message' => 'Mensagem enviada para a Box do usuario.',
            'recipients' => 1,
            'item_id' => $itemId,
            'target' => $this->normalizeUser($user),
        ];
    }

    private function normalizeCampaign(array $campaign): array
    {
        return [
            'id' => (int) $campaign['id'],
            'audience' => (string) $campaign['audience'],
            'target_email' => (string) ($campaign['target_email'] ?? ''),
            'box_type' => (string) $campaign['box_type'],
            'title' => (string) $campaign['title'],
            'body' => (string) $campaign['body'],
            'action_url' => (string) ($campaign['action_url'] ?? ''),
            'action_label' => (string) ($campaign['action_label'] ?? ''),
            'tone' => (string) ($campaign['tone'] ?? 'info'),
            'recipients_count' => (int) $campaign['recipients_count'],
            'admin_name' => (string) ($campaign['admin_name'] ?? 'Administrador'),
            'created_at' => (string) $campaign['created_at'],
        ];
    }

    private function normalizeUser(array $user): array
    {
        return [
            'id' => (int) $user['id'],
            'name' => (string) ($user['full_name'] ?? 'Usuario Pipocine'),
            'email' => (string) ($user['email'] ?? ''),
            'phone' => (string) ($user['phone'] ?? ''),
            'status' => (string) ($user['status'] ?? ''),
        ];
    }

    private function cleanText(string $value, int $max): string
    {
        $value = trim(preg_replace('/\s+/', ' ', $value) ?? '');
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $max);
        }

        return substr($value, 0, $max);
    }

    private function safeTone(string $tone): string
    {
        return in_array($tone, ['info', 'success', 'warning', 'danger'], true) ? $tone : 'info';
    }

    private function isSafeUrl(string $url): bool
    {
        if (str_starts_with($url, '/') && !str_starts_with($url, '//')) {
            return true;
        }

        return (bool) filter_var($url, FILTER_VALIDATE_URL)
            && in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true);
    }
}
