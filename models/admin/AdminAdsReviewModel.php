<?php
declare(strict_types=1);

namespace Models\Admin;

use PDO;

final class AdminAdsReviewModel
{
    public function __construct(private readonly PDO $db) {}

    public function counts(): array
    {
        $stmt = $this->db->query(
            "SELECT status, COUNT(*) AS total
               FROM ads_campaigns
              WHERE status IN ('pending_review','in_review','changes_requested','approved','active','paused','rejected')
              GROUP BY status"
        );
        $counts = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $counts[(string) $row['status']] = (int) $row['total'];
        }
        return $counts;
    }

    public function list(string $filter = 'queue', string $query = ''): array
    {
        $where = [];
        $params = [];
        if ($filter === 'queue') {
            $where[] = "c.status IN ('pending_review','in_review')";
        } elseif ($filter !== 'all') {
            $where[] = 'c.status = ?';
            $params[] = $filter;
        }
        if ($query !== '') {
            $where[] = '(c.name LIKE ? OR a.brand_name LIKE ? OR a.email LIKE ?)';
            $like = '%' . $query . '%';
            array_push($params, $like, $like, $like);
        }
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $this->db->prepare(
            "SELECT
                c.*,
                a.brand_name,
                a.email AS account_email,
                a.logo_url AS account_logo_url,
                admin.display_name AS lock_admin_name,
                reviewer.display_name AS reviewer_name
             FROM ads_campaigns c
             JOIN ads_accounts a ON a.id = c.ads_account_id
             LEFT JOIN admin_users admin ON admin.id = c.review_lock_admin_id
             LEFT JOIN admin_users reviewer ON reviewer.id = c.reviewed_by_admin_id
             {$whereSql}
             ORDER BY
                CASE c.status
                    WHEN 'pending_review' THEN 1
                    WHEN 'in_review' THEN 2
                    WHEN 'approved' THEN 3
                    WHEN 'changes_requested' THEN 4
                    WHEN 'active' THEN 5
                    ELSE 6
                END,
                c.submitted_at ASC,
                c.id ASC
             LIMIT 180"
        );
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $campaignId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT
                c.*,
                a.brand_name,
                a.email AS account_email,
                a.logo_url AS account_logo_url,
                a.contact_name,
                a.phone_e164,
                admin.display_name AS lock_admin_name,
                reviewer.display_name AS reviewer_name
             FROM ads_campaigns c
             JOIN ads_accounts a ON a.id = c.ads_account_id
             LEFT JOIN admin_users admin ON admin.id = c.review_lock_admin_id
             LEFT JOIN admin_users reviewer ON reviewer.id = c.reviewed_by_admin_id
             WHERE c.id = ?
             LIMIT 1"
        );
        $stmt->execute([$campaignId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findForUpdate(int $campaignId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM ads_campaigns WHERE id = ? LIMIT 1 FOR UPDATE');
        $stmt->execute([$campaignId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function events(int $campaignId): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                e.*,
                a.display_name AS admin_name
             FROM ads_campaign_status_events e
             LEFT JOIN admin_users a ON a.id = e.actor_admin_id
             WHERE e.campaign_id = ?
             ORDER BY e.id ASC"
        );
        $stmt->execute([$campaignId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatus(int $campaignId, array $fields): void
    {
        if (!$fields) {
            return;
        }
        $assignments = [];
        $params = [];
        foreach ($fields as $column => $value) {
            $assignments[] = "{$column} = ?";
            $params[] = $value;
        }
        $params[] = $campaignId;
        $stmt = $this->db->prepare(
            'UPDATE ads_campaigns SET ' . implode(', ', $assignments) . ' WHERE id = ?'
        );
        $stmt->execute($params);
    }

    public function latestEventId(): int
    {
        return (int) $this->db->query('SELECT COALESCE(MAX(id), 0) FROM ads_campaign_status_events')->fetchColumn();
    }
}
