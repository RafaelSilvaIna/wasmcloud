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

    public function listByUserId(int $userId): array {
        $stmt = $this->db->prepare("SELECT id, profile_name, username, profile_image, is_kids, (pin_hash IS NOT NULL) as has_pin FROM profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function checkUsernameExists(string $username): bool {
        $stmt = $this->db->prepare("SELECT id FROM profiles WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        return (bool)$stmt->fetch();
    }

    public function findById(int $id) {
        $stmt = $this->db->prepare("SELECT * FROM profiles WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}