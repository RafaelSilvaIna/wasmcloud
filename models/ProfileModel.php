<?php
class ProfileModel {
    private $db;

    public function __construct(PDO $pdo) {
        $this->db = $pdo;
    }

    public function create(array $data): bool {
        $stmt = $this->db->prepare("INSERT INTO profiles (user_id, profile_name, username, pin_hash, profile_image, is_kids) VALUES (?, ?, ?, ?, ?, ?)");
        return $stmt->execute([
            $data['user_id'],
            $data['profile_name'],
            $data['username'],
            $data['pin_hash'],
            $data['profile_image'],
            $data['is_kids']
        ]);
    }

    public function countByUserId(int $userId): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    public function listByUserId(int $userId): array {
        $stmt = $this->db->prepare("SELECT id, profile_name, username, profile_image, is_kids, (pin_hash IS NOT NULL) as has_pin, is_watching, last_active_at FROM profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function findById(int $id) {
        $stmt = $this->db->prepare("SELECT * FROM profiles WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function updateWatchingStatus(int $profileId, bool $status, ?string $sessionId = null): bool {
        $stmt = $this->db->prepare("UPDATE profiles SET is_watching = ?, last_active_at = NOW(), current_session_id = ? WHERE id = ?");
        return $stmt->execute([$status ? 1 : 0, $sessionId, $profileId]);
    }

    public function checkActiveSession(int $profileId) {
        $stmt = $this->db->prepare("SELECT is_watching, last_active_at, current_session_id FROM profiles WHERE id = ?");
        $stmt->execute([$profileId]);
        return $stmt->fetch();
    }
}