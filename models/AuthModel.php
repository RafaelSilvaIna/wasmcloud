<?php
class AuthModel {
    private $dbCineveo;

    /**
     * Construtor da classe: recebe a conexão com o banco de dados.
     */
    public function __construct(PDO $pdoCineveo) {
        $this->dbCineveo = $pdoCineveo;
    }

    /**
     * Procura um utilizador na base de dados pelo seu e-mail.
     * Retorna todos os dados do utilizador.
     */
    public function getUserByEmail(string $email) {
        $stmt = $this->dbCineveo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Recolhe dados específicos de um utilizador através do seu ID para carregar o perfil.
     */
    public function getUserData(int $id) {
        // CORREÇÃO: Utilizando 'plan_expiration', que é o nome correto da coluna
        // na tabela users, conforme evidenciado pelos componentes Premium.
        $stmt = $this->dbCineveo->prepare("SELECT id, username, name, full_name, profile_pic_url, email, plan_type, plan_expiration, role FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Cria uma nova sessão para o utilizador após o login na tabela user_sessions.
     */
    public function createSession(int $userId, string $tokenHash, string $expiresAt) {
        $stmt = $this->dbCineveo->prepare("INSERT INTO user_sessions (token_hash, user_id, expires_at) VALUES (?, ?, ?)");
        return $stmt->execute([$tokenHash, $userId, $expiresAt]);
    }
}