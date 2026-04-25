<?php
class AuthModel {
    private $dbCineveo;
    private $dbPipocine;

    public function __construct(PDO $pdoCineveo, ?PDO $pdoPipocine = null) {
        $this->dbCineveo = $pdoCineveo;
        $this->dbPipocine = $pdoPipocine;
    }

    public function getUserData(int $id) {
        $stmt = $this->dbCineveo->prepare("SELECT id, username, name, full_name, profile_pic_url, email, plan_type, role FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}