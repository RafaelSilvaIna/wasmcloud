<?php

declare(strict_types=1);

namespace Services\V4;

use Models\V4\FamilyBoxModel;

class FamilyBoxService
{
    private FamilyBoxModel $model;

    public function __construct(FamilyBoxModel $model)
    {
        $this->model = $model;
    }

    public function summary(int $userId): array
    {
        $this->ensureRenewalNotices($userId);
        $familyBenefit = $this->model->activeFamilyBenefitForMember($userId);

        return [
            'success' => true,
            'unread' => $this->model->unreadCount($userId),
            'family_member' => (bool) $familyBenefit,
            'family_badge' => $familyBenefit ? 'Membro da familia' : null,
        ];
    }

    public function inbox(int $userId): array
    {
        $this->ensureRenewalNotices($userId);

        return [
            'success' => true,
            'unread' => $this->model->unreadCount($userId),
            'items' => array_map([$this, 'normalizeBoxItem'], $this->model->inbox($userId)),
        ];
    }

    public function item(int $userId, int $itemId): array
    {
        $item = $this->model->boxItemForUser($itemId, $userId);
        if (!$item) {
            return ['success' => false, 'message' => 'Mensagem nao encontrada.'];
        }

        return [
            'success' => true,
            'item' => $this->normalizeBoxItem($item),
            'unread' => $this->model->unreadCount($userId),
        ];
    }

    public function processRenewalNotices(int $limit = 1000): array
    {
        $created = 0;
        $checked = 0;
        $errors = 0;

        foreach ($this->model->renewalSubscriptionsDueForNotices($limit) as $subscription) {
            $checked++;

            try {
                if ($this->createRenewalNoticeFromSubscription($subscription)) {
                    $created++;
                }
            } catch (\Throwable) {
                $errors++;
            }
        }

        return [
            'success' => $errors === 0,
            'checked' => $checked,
            'created' => $created,
            'skipped' => max(0, $checked - $created - $errors),
            'errors' => $errors,
        ];
    }

    public function familyDashboard(int $ownerUserId): array
    {
        $subscription = $this->model->activeSubscription($ownerUserId);
        $limit = max(0, (int) ($subscription['family_member_limit'] ?? 0));
        $members = array_map([$this, 'normalizeMember'], $this->model->activeMembers($ownerUserId));

        return [
            'success' => true,
            'enabled' => (bool) $subscription && $limit > 0,
            'limit' => $limit,
            'used' => count($members),
            'members' => $members,
        ];
    }

    public function invite(int $ownerUserId, string $email): array
    {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Digite um e-mail valido.'];
        }

        $subscription = $this->model->activeSubscription($ownerUserId);
        $limit = max(0, (int) ($subscription['family_member_limit'] ?? 0));
        if (!$subscription || $limit < 1) {
            return ['success' => false, 'message' => 'Seu plano nao permite membros familiares.'];
        }

        if ($this->model->activeMemberCount($ownerUserId) >= $limit) {
            return ['success' => false, 'message' => 'Voce atingiu o limite de membros familiares do seu plano.'];
        }

        $target = $this->model->userByEmail($email);
        if (!$target || ($target['status'] ?? '') !== 'active') {
            return ['success' => false, 'message' => 'Nao encontramos uma conta Pipocine ativa com este e-mail.'];
        }

        if ((int) $target['id'] === $ownerUserId) {
            return ['success' => false, 'message' => 'Voce nao pode convidar a propria conta.'];
        }

        if ($this->model->activeMembershipBetween($ownerUserId, (int) $target['id'])) {
            return ['success' => false, 'message' => 'Este usuario ja faz parte da sua familia.'];
        }

        if ($this->model->activeMembershipForMember((int) $target['id'])) {
            return ['success' => false, 'message' => 'Este usuario ja esta vinculado a outra familia.'];
        }

        if ($this->model->pendingInvite($ownerUserId, (int) $target['id'])) {
            return ['success' => false, 'message' => 'Ja existe um convite pendente para este e-mail.'];
        }

        $owner = $this->model->userById($ownerUserId);
        $ownerName = trim((string) ($owner['full_name'] ?? 'Um usuario Pipocine')) ?: 'Um usuario Pipocine';
        $inviteId = $this->model->createInvite($ownerUserId, (int) $target['id'], $ownerName);

        return [
            'success' => true,
            'message' => 'Solicitacao enviada para a Box do usuario.',
            'invite_id' => $inviteId,
        ];
    }

    public function accept(int $userId, int $itemId): array
    {
        $item = $this->model->boxItemForUser($itemId, $userId);
        if (!$item || ($item['type'] ?? '') !== 'family_invite') {
            return ['success' => false, 'message' => 'Solicitacao nao encontrada.'];
        }

        if (($item['action_status'] ?? '') !== 'pending') {
            return ['success' => false, 'message' => 'Esta solicitacao ja foi respondida.'];
        }

        $ownerUserId = (int) ($item['actor_user_id'] ?? 0);
        $subscription = $this->model->activeSubscription($ownerUserId);
        $limit = max(0, (int) ($subscription['family_member_limit'] ?? 0));

        if (!$subscription || $limit < 1) {
            return ['success' => false, 'message' => 'O plano do titular nao esta ativo para membros familiares.'];
        }

        if ($this->model->activeMemberCount($ownerUserId) >= $limit) {
            return ['success' => false, 'message' => 'O titular ja atingiu o limite de membros familiares.'];
        }

        $existingFamily = $this->model->activeMembershipForMember($userId);
        if ($existingFamily && (int) $existingFamily['owner_user_id'] !== $ownerUserId) {
            return ['success' => false, 'message' => 'Sua conta ja esta vinculada a outra familia.'];
        }

        if (!$this->model->acceptInvite($item, $limit)) {
            return ['success' => false, 'message' => 'Nao foi possivel confirmar o convite com seguranca. Atualize a Box e tente novamente.'];
        }

        return [
            'success' => true,
            'message' => 'Convite aceito. Voce agora faz parte da familia Pipocine.',
            'family_member' => true,
            'family_badge' => 'Membro da familia',
        ];
    }

    public function decline(int $userId, int $itemId): array
    {
        $item = $this->model->boxItemForUser($itemId, $userId);
        if (!$item || ($item['type'] ?? '') !== 'family_invite') {
            return ['success' => false, 'message' => 'Solicitacao nao encontrada.'];
        }

        if (($item['action_status'] ?? '') !== 'pending') {
            return ['success' => false, 'message' => 'Esta solicitacao ja foi respondida.'];
        }

        $this->model->declineInvite($item);
        return ['success' => true, 'message' => 'Convite recusado.'];
    }

    public function removeMember(int $ownerUserId, int $memberUserId): array
    {
        if ($memberUserId < 1) {
            return ['success' => false, 'message' => 'Membro invalido.'];
        }

        $removed = $this->model->removeMember($ownerUserId, $memberUserId);
        if ($removed) {
            $this->model->createNoticeIfMissing(
                $memberUserId,
                $ownerUserId,
                'family_removed',
                'family_removed:' . $ownerUserId . ':' . $memberUserId . ':' . date('Y-m-d'),
                'Beneficio familiar encerrado',
                'Voce foi removido do beneficio familiar Pipocine. Assine o Plano Gold para continuar com seus beneficios e desbloquear muito mais.',
                [
                    'action_url' => '/plan',
                    'action_label' => 'Assinar Plano Gold',
                    'tone' => 'warning',
                ]
            );
        }

        return [
            'success' => $removed,
            'message' => $removed ? 'Membro removido da familia.' : 'Nao foi possivel remover este membro.',
        ];
    }

    public function markRead(int $userId, int $itemId): array
    {
        $this->model->markRead($itemId, $userId);
        return ['success' => true];
    }

    private function normalizeBoxItem(array $item): array
    {
        return [
            'id' => (int) $item['id'],
            'type' => (string) $item['type'],
            'title' => (string) $item['title'],
            'body' => (string) $item['body'],
            'status' => (string) $item['status'],
            'action_status' => (string) $item['action_status'],
            'created_at' => (string) $item['created_at'],
            'payload' => $this->safePayload($item['payload'] ?? null),
            'actor' => [
                'name' => (string) ($item['actor_name'] ?? 'Pipocine'),
                'email' => (string) ($item['actor_email'] ?? ''),
                'avatar' => (string) ($item['actor_avatar'] ?? ''),
            ],
        ];
    }

    private function ensureRenewalNotices(int $userId): void
    {
        foreach ($this->model->renewalSubscriptionsDueForNotice($userId) as $subscription) {
            $this->createRenewalNoticeFromSubscription($subscription);
        }
    }

    private function createRenewalNoticeFromSubscription(array $subscription): bool
    {
        $userId = (int) ($subscription['user_id'] ?? 0);
        $daysLeft = (int) ($subscription['days_left'] ?? 0);
        $source = (string) ($subscription['source'] ?? 'paid');
        $subscriptionId = (int) ($subscription['id'] ?? 0);

        if ($userId < 1 || $subscriptionId < 1 || !in_array($daysLeft, [14, 5, 1], true)) {
            return false;
        }

        if ($source === 'admin_courtesy') {
            return $this->model->createNoticeIfMissing(
                $userId,
                null,
                'courtesy_expiring',
                'courtesy_expiring:' . $subscriptionId . ':' . $daysLeft,
                'Sua cortesia Pipocine esta perto de acabar',
                'Sua cortesia esta perto de acabar. Assine o Plano Gold do Pipocine para continuar com beneficios premium, mais controle e uma experiencia completa.',
                [
                    'action_url' => '/plan',
                    'action_label' => 'Assinar Plano Gold',
                    'days_left' => $daysLeft,
                    'subscription_id' => $subscriptionId,
                ]
            ) !== null;
        }

        return $this->model->createNoticeIfMissing(
            $userId,
            null,
            'subscription_renewal',
            'subscription_renewal:' . $subscriptionId . ':' . $daysLeft,
            'Seu Plano Gold esta perto de acabar',
            'Seu plano esta perto de acabar. Faca uma renovacao do seu plano para continuar com seus beneficios Pipocine.',
            [
                'action_url' => '/plan/me',
                'action_label' => 'Renovar agora',
                'days_left' => $daysLeft,
                'subscription_id' => $subscriptionId,
            ]
        ) !== null;
    }

    private function safePayload($payload): array
    {
        if (!is_string($payload) || trim($payload) === '') {
            return [];
        }

        $decoded = json_decode($payload, true);
        if (!is_array($decoded)) {
            return [];
        }

        $allowed = [];
        foreach (['action_url', 'action_label', 'tone', 'days_left'] as $key) {
            if (array_key_exists($key, $decoded)) {
                $allowed[$key] = is_scalar($decoded[$key]) ? (string) $decoded[$key] : '';
            }
        }

        return $allowed;
    }

    private function normalizeMember(array $member): array
    {
        return [
            'id' => (int) $member['member_user_id'],
            'name' => (string) ($member['full_name'] ?? 'Membro'),
            'email' => (string) ($member['email'] ?? ''),
            'avatar' => (string) ($member['avatar_url'] ?? ''),
            'accepted_at' => (string) ($member['accepted_at'] ?? ''),
        ];
    }
}
