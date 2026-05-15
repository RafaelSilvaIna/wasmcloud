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
        $stmt = $this->db->prepare("
            SELECT id, profile_name, username, profile_image, is_kids, (pin_hash IS NOT NULL) as has_pin, is_watching, last_active_at
            FROM profiles
            WHERE user_id = ?
            ORDER BY id ASC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id) {
        $stmt = $this->db->prepare("SELECT * FROM profiles WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findByUsername(string $username): ?array {
        $stmt = $this->db->prepare("SELECT id FROM profiles WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function updateWatchingStatus(int $profileId, bool $status, ?string $sessionId = null): bool {
        $stmt = $this->db->prepare("UPDATE profiles SET is_watching = ?, last_active_at = NOW(), current_session_id = ? WHERE id = ?");
        return $stmt->execute([$status ? 1 : 0, $sessionId, $profileId]);
    }

    public function checkActiveSession(int $profileId) {
        $stmt = $this->db->prepare("SELECT is_watching, last_active_at, current_session_id FROM profiles WHERE id = ?");
        $stmt->execute([$profileId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Atualiza nome, avatar e username do perfil
    public function updateProfile(int $id, int $userId, string $name, string $image, ?string $username = null): bool {
        if ($username !== null) {
            $stmt = $this->db->prepare("UPDATE profiles SET profile_name = ?, profile_image = ?, profile_username = ? WHERE id = ? AND user_id = ?");
            return $stmt->execute([$name, $image, $username, $id, $userId]);
        }
        $stmt = $this->db->prepare("UPDATE profiles SET profile_name = ?, profile_image = ? WHERE id = ? AND user_id = ?");
        return $stmt->execute([$name, $image, $id, $userId]);
    }

    // Exclui um perfil do usuário
    public function deleteProfile(int $id, int $userId): bool {
        $stmt = $this->db->prepare("DELETE FROM profiles WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    }

    // Verifica se username já existe (excluindo um profileId específico)
    public function findByUsernameExcluding(string $username, int $excludeId): ?array {
        $stmt = $this->db->prepare("SELECT id FROM profiles WHERE profile_username = ? AND id != ? LIMIT 1");
        $stmt->execute([$username, $excludeId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
