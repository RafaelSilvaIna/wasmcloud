<?php
declare(strict_types=1);

namespace Models\Ads;

use PDO;

final class AdsCampaignModel
{
    public function __construct(private readonly PDO $pdo) {}

    public function listByAccount(int $accountId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT *
               FROM ads_campaigns
              WHERE ads_account_id = ?
              ORDER BY created_at DESC, id DESC'
        );
        $stmt->execute([$accountId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByIdForAccount(int $accountId, int $campaignId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT *
               FROM ads_campaigns
              WHERE id = ? AND ads_account_id = ?
              LIMIT 1'
        );
        $stmt->execute([$campaignId, $accountId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function statusCounts(int $accountId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT status, COUNT(*) AS total
               FROM ads_campaigns
              WHERE ads_account_id = ?
              GROUP BY status'
        );
        $stmt->execute([$accountId]);

        $counts = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $counts[(string) $row['status']] = (int) $row['total'];
        }
        return $counts;
    }

    public function createDraft(int $accountId, string $type): array
    {
        $draftToken = bin2hex(random_bytes(32));
        $stmt = $this->pdo->prepare(
            "INSERT INTO ads_campaigns
                (ads_account_id, draft_token, name, creative_type, creative_url, status)
             VALUES (?, ?, 'Novo anúncio', ?, '', 'draft')"
        );
        $stmt->execute([$accountId, $draftToken, $type]);
        return $this->findByDraftToken($accountId, $draftToken) ?? [];
    }

    public function findByDraftToken(int $accountId, string $draftToken): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT *
               FROM ads_campaigns
              WHERE ads_account_id = ? AND draft_token = ?
              LIMIT 1'
        );
        $stmt->execute([$accountId, $draftToken]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findByDraftTokenForUpdate(int $accountId, string $draftToken): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT *
               FROM ads_campaigns
              WHERE ads_account_id = ? AND draft_token = ?
              LIMIT 1
              FOR UPDATE'
        );
        $stmt->execute([$accountId, $draftToken]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findByCdnToken(string $cdnToken): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT *
               FROM ads_campaigns
              WHERE cdn_token = ?
              LIMIT 1'
        );
        $stmt->execute([$cdnToken]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function deleteDraft(int $accountId, string $draftToken): bool
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM ads_campaigns
              WHERE ads_account_id = ?
                AND draft_token = ?
                AND status = ?
              LIMIT 1'
        );
        $stmt->execute([$accountId, $draftToken, 'draft']);
        return $stmt->rowCount() > 0;
    }

    public function updateMedia(int $campaignId, array $media): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE ads_campaigns
                SET creative_url = ?,
                    creative_duration_seconds = ?,
                    creative_mime_type = ?,
                    media_provider = ?,
                    media_provider_file_id = ?,
                    original_filename = ?,
                    file_size_bytes = ?,
                    cdn_token = COALESCE(cdn_token, ?)
              WHERE id = ? AND status = ?'
        );
        $stmt->execute([
            $media['creative_url'],
            $media['creative_duration_seconds'],
            $media['creative_mime_type'],
            $media['media_provider'],
            $media['media_provider_file_id'],
            $media['original_filename'],
            $media['file_size_bytes'],
            $media['cdn_token'],
            $campaignId,
            'draft',
        ]);
    }

    public function updateDetails(int $campaignId, string $name, string $description, ?string $redirectUrl, bool $canSkip): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE ads_campaigns
                SET name = ?,
                    description = ?,
                    redirect_url = ?,
                    can_skip = ?
              WHERE id = ? AND status = ?'
        );
        $stmt->execute([$name, $description, $redirectUrl, $canSkip ? 1 : 0, $campaignId, 'draft']);
    }

    public function markSubmitted(int $campaignId, string $status, int $priceCents, bool $isDemo): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE ads_campaigns
                SET status = ?,
                    price_cents = ?,
                    is_demo = ?,
                    submitted_at = NOW()
              WHERE id = ? AND status = ?'
        );
        $stmt->execute([$status, $priceCents, $isDemo ? 1 : 0, $campaignId, 'draft']);
    }

    public function addStatusEvent(int $campaignId, ?string $fromStatus, string $toStatus, string $note): void
    {
        $this->addDetailedStatusEvent($campaignId, $fromStatus, $toStatus, $note, 'system', null, $note);
    }

    public function addDetailedStatusEvent(
        int $campaignId,
        ?string $fromStatus,
        string $toStatus,
        string $note,
        string $actorType = 'system',
        ?int $actorAdminId = null,
        ?string $publicNote = null,
        ?string $internalNote = null,
        array $metadata = []
    ): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO ads_campaign_status_events
                (campaign_id, from_status, to_status, note, actor_type, actor_admin_id, public_note, internal_note, metadata)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $campaignId,
            $fromStatus,
            $toStatus,
            $note,
            $actorType,
            $actorAdminId,
            $publicNote,
            $internalNote,
            $metadata ? json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
        ]);
    }

    public function publicEventsByAccount(int $accountId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT e.id, e.campaign_id, e.from_status, e.to_status, e.public_note, e.created_at
               FROM ads_campaign_status_events e
               JOIN ads_campaigns c ON c.id = e.campaign_id
              WHERE c.ads_account_id = ?
              ORDER BY e.id ASC'
        );
        $stmt->execute([$accountId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function latestEventIdByAccount(int $accountId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COALESCE(MAX(e.id), 0)
               FROM ads_campaign_status_events e
               JOIN ads_campaigns c ON c.id = e.campaign_id
              WHERE c.ads_account_id = ?'
        );
        $stmt->execute([$accountId]);
        return (int) $stmt->fetchColumn();
    }
}
