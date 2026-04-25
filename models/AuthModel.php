<?php
class AuthModel {
    private $db;

    public function __construct(PDO $pdoCineveo) {
        $this->db = $pdoCineveo;
    }

    public function getUserById(int $id) {
        $stmt = $this->db->prepare("SELECT id, username, full_name, profile_pic_url, email FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}