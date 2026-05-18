<?php
declare(strict_types=1);

namespace Services\Admin;

use Models\Admin\AdminAdsReviewModel;
use Models\Ads\AdsCampaignModel;
use PDO;

final class AdminAdsReviewService
{
    private const LOCK_MINUTES = 10;

    public function __construct(
        private readonly PDO $db,
        private readonly AdminAdsReviewModel $reviews,
        private readonly AdsCampaignModel $campaigns
    ) {}

    public function board(string $filter = 'queue', string $query = ''): array
    {
        return [
            'success' => true,
            'counts' => $this->reviews->counts(),
            'campaigns' => $this->reviews->list($this->sanitizeFilter($filter), trim($query)),
            'revision' => $this->reviews->latestEventId(),
            'server_time' => date('Y-m-d H:i:s'),
        ];
    }

    public function detail(int $campaignId): array
    {
        $campaign = $this->reviews->find($campaignId);
        if (!$campaign) {
            return ['success' => false, 'error' => 'Anúncio não encontrado.'];
        }
        return [
            'success' => true,
            'campaign' => $campaign,
            'events' => $this->reviews->events($campaignId),
            'server_time' => date('Y-m-d H:i:s'),
        ];
    }

    public function startReview(int $campaignId, int $adminId): array
    {
        return $this->transition($campaignId, $adminId, function (array $campaign) use ($adminId): array {
            if (!in_array($campaign['status'], ['pending_review', 'in_review'], true)) {
                return ['error' => 'Este anúncio não está disponível para revisão.'];
            }
            if ($campaign['status'] === 'in_review'
                && $this->hasActiveForeignLock($campaign, $adminId)) {
                return ['error' => 'Outro administrador j? assumiu esta revis?o.'];
            }
            return [
                'to' => 'in_review',
                'public' => 'Um administrador iniciou a análise do anúncio.',
                'internal' => 'Revisão assumida pelo administrador.',
                'fields' => [
                    'review_lock_admin_id' => '__ADMIN__',
                    'review_lock_expires_at' => date('Y-m-d H:i:s', time() + self::LOCK_MINUTES * 60),
                    'review_started_at' => $campaign['review_started_at'] ?: date('Y-m-d H:i:s'),
                ],
            ];
        });
    }

    public function approve(int $campaignId, int $adminId, string $publicNote, string $internalNote): array
    {
        return $this->lockedDecision($campaignId, $adminId, 'approved', $publicNote ?: 'Anúncio aprovado na revisão.', $internalNote, [
            'approved_at' => date('Y-m-d H:i:s'),
            'reviewed_at' => date('Y-m-d H:i:s'),
            'reviewed_by_admin_id' => $adminId,
            'review_lock_admin_id' => null,
            'review_lock_expires_at' => null,
        ]);
    }

    public function requestChanges(int $campaignId, int $adminId, string $publicNote, string $internalNote): array
    {
        if (trim($publicNote) === '') {
            return ['success' => false, 'error' => 'Informe ao anunciante quais ajustes são necessários.'];
        }
        return $this->lockedDecision($campaignId, $adminId, 'changes_requested', trim($publicNote), $internalNote, [
            'changes_requested_at' => date('Y-m-d H:i:s'),
            'reviewed_at' => date('Y-m-d H:i:s'),
            'reviewed_by_admin_id' => $adminId,
            'review_lock_admin_id' => null,
            'review_lock_expires_at' => null,
        ]);
    }

    public function reject(int $campaignId, int $adminId, string $publicNote, string $internalNote): array
    {
        if (trim($publicNote) === '') {
            return ['success' => false, 'error' => 'Informe o motivo público da rejeição.'];
        }
        return $this->lockedDecision($campaignId, $adminId, 'rejected', trim($publicNote), $internalNote, [
            'rejected_at' => date('Y-m-d H:i:s'),
            'reviewed_at' => date('Y-m-d H:i:s'),
            'reviewed_by_admin_id' => $adminId,
            'review_lock_admin_id' => null,
            'review_lock_expires_at' => null,
        ]);
    }

    public function publish(int $campaignId, int $adminId): array
    {
        return $this->transition($campaignId, $adminId, function (array $campaign): array {
            if ($campaign['status'] !== 'approved') {
                return ['error' => 'Apenas anúncios aprovados podem ser publicados.'];
            }
            return [
                'to' => 'active',
                'public' => 'Anúncio aprovado e liberado para exibição.',
                'internal' => 'Campanha publicada no inventário gratuito.',
                'fields' => [
                    'activated_at' => date('Y-m-d H:i:s'),
                    'starts_at' => $campaign['starts_at'] ?: date('Y-m-d H:i:s'),
                ],
            ];
        });
    }

    public function pause(int $campaignId, int $adminId, string $note): array
    {
        return $this->simpleStatusTransition($campaignId, $adminId, ['active'], 'paused', $note ?: 'Anúncio pausado pela administração.');
    }

    public function resume(int $campaignId, int $adminId, string $note): array
    {
        return $this->simpleStatusTransition($campaignId, $adminId, ['paused'], 'active', $note ?: 'Anúncio reativado pela administração.');
    }

    private function lockedDecision(int $campaignId, int $adminId, string $to, string $publicNote, string $internalNote, array $fields): array
    {
        return $this->transition($campaignId, $adminId, function (array $campaign) use ($to, $publicNote, $internalNote, $fields, $adminId): array {
            if ($campaign['status'] !== 'in_review') {
                return ['error' => 'A revisão precisa estar em andamento antes desta decisão.'];
            }
            if (!$this->lockBelongsTo($campaign, $adminId)) {
                return ['error' => 'Outro administrador detém esta revisão no momento.'];
            }
            return [
                'to' => $to,
                'public' => $publicNote,
                'internal' => $internalNote ?: ucfirst(str_replace('_', ' ', $to)),
                'fields' => $fields,
            ];
        });
    }

    private function simpleStatusTransition(int $campaignId, int $adminId, array $from, string $to, string $note): array
    {
        return $this->transition($campaignId, $adminId, function (array $campaign) use ($from, $to, $note): array {
            if (!in_array($campaign['status'], $from, true)) {
                return ['error' => 'Transição indisponível para este anúncio.'];
            }
            return [
                'to' => $to,
                'public' => $note,
                'internal' => $note,
                'fields' => [],
            ];
        });
    }

    private function transition(int $campaignId, int $adminId, callable $resolver): array
    {
        try {
            $this->db->beginTransaction();
            $campaign = $this->reviews->findForUpdate($campaignId);
            if (!$campaign) {
                $this->db->rollBack();
                return ['success' => false, 'error' => 'Anúncio não encontrado.'];
            }

            $decision = $resolver($campaign);
            if (!empty($decision['error'])) {
                $this->db->rollBack();
                return ['success' => false, 'error' => $decision['error']];
            }

            $from = (string) $campaign['status'];
            $to = (string) $decision['to'];
            $fields = ['status' => $to] + ($decision['fields'] ?? []);
            foreach ($fields as $column => $value) {
                if ($value === '__ADMIN__') {
                    $fields[$column] = $adminId;
                }
            }
            $this->reviews->updateStatus($campaignId, $fields);
            $this->campaigns->addDetailedStatusEvent(
                $campaignId,
                $from,
                $to,
                (string) ($decision['internal'] ?? $decision['public'] ?? ''),
                'admin',
                $adminId,
                (string) ($decision['public'] ?? ''),
                (string) ($decision['internal'] ?? '')
            );
            $this->db->commit();
        } catch (\Throwable) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'error' => 'Não foi possível atualizar o anúncio agora.'];
        }

        return $this->detail($campaignId);
    }

    private function lockBelongsTo(array $campaign, int $adminId): bool
    {
        if ((int) ($campaign['review_lock_admin_id'] ?? 0) !== $adminId) {
            return false;
        }
        $expires = strtotime((string) ($campaign['review_lock_expires_at'] ?? ''));
        return $expires === false || $expires >= time();
    }

    private function hasActiveForeignLock(array $campaign, int $adminId): bool
    {
        $ownerId = (int) ($campaign['review_lock_admin_id'] ?? 0);
        if ($ownerId <= 0 || $ownerId === $adminId) {
            return false;
        }
        $expires = strtotime((string) ($campaign['review_lock_expires_at'] ?? ''));
        return $expires === false || $expires >= time();
    }

    private function sanitizeFilter(string $filter): string
    {
        $allowed = ['queue', 'all', 'pending_review', 'in_review', 'approved', 'active', 'paused', 'changes_requested', 'rejected'];
        return in_array($filter, $allowed, true) ? $filter : 'queue';
    }
}
