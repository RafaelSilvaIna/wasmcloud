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
        return [
            'success' => true,
            'unread' => $this->model->unreadCount($userId),
        ];
    }

    public function inbox(int $userId): array
    {
        return [
            'success' => true,
            'unread' => $this->model->unreadCount($userId),
            'items' => array_map([$this, 'normalizeBoxItem'], $this->model->inbox($userId)),
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

        $this->model->acceptInvite($item);
        return ['success' => true, 'message' => 'Convite aceito. Voce agora faz parte da familia Pipocine.'];
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
            'actor' => [
                'name' => (string) ($item['actor_name'] ?? 'Pipocine'),
                'email' => (string) ($item['actor_email'] ?? ''),
                'avatar' => (string) ($item['actor_avatar'] ?? ''),
            ],
        ];
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
