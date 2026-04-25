<?php
class AuthModel {
    private $dbCineveo;

    public function __construct(PDO $pdoCineveo) {
        $this->dbCineveo = $pdoCineveo;
    }

    public function getUserByEmail(string $email) {
        $stmt = $this->dbCineveo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public function getUserData(int $id) {
        $stmt = $this->dbCineveo->prepare("SELECT id, username, name, full_name, profile_pic_url, email, plan_type, role FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function createSession(int $userId, string $tokenHash, string $expiresAt) {
        $stmt = $this->dbCineveo->prepare("INSERT INTO user_sessions (token_hash, user_id, expires_at) VALUES (?, ?, ?)");
        return $stmt->execute([$tokenHash, $userId, $expiresAt]);
    }
}