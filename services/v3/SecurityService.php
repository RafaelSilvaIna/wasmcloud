<?php
/**
 * SERVICE: SecurityService (v3)
 *
 * Orquestra a lógica de negócio dos métodos de autenticação alternativos.
 * Coordena o SecurityModel (pipcine) com o banco CineVEO (para resolver user_id).
 *
 * Responsabilidades:
 *   - Código de login: criar, ler status, remover, validar no login
 *   - QR Code: gerar sessão, consultar status (polling), confirmar autenticação
 *   - Após auth bem-sucedida via QR ou código: inicia a sessão PHP do usuário
 */

declare(strict_types=1);

class SecurityService
{
    private SecurityModel $secModel;
    private ?PDO          $pdoCineveo;

    public function __construct(SecurityModel $secModel, ?PDO $pdoCineveo = null)
    {
        $this->secModel   = $secModel;
        $this->pdoCineveo = $pdoCineveo;
    }

    // ──────────────────────────────────────────────────────────────────────
    // CÓDIGO DE LOGIN — painel de configurações
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Retorna o status do código do usuário autenticado.
     * Nunca expõe o hash.
     */
    public function getCodeStatus(int $userId): array
    {
        $row = $this->secModel->getLoginCode($userId);

        if ($row === null) {
            return [
                'has_code'        => false,
                'last_changed_at' => null,
                'locked_until'    => null,
            ];
        }

        return [
            'has_code'        => true,
            'last_changed_at' => $row['last_changed_at'],
            'locked_until'    => $row['locked_until'],
        ];
    }

    /**
     * Cria ou atualiza o código de 4 dígitos do usuário.
     */
    public function saveCode(int $userId, string $codePlain): array
    {
        if (!preg_match('/^\d{4}$/', $codePlain)) {
            return ['success' => false, 'message' => 'O código deve ter exatamente 4 dígitos numéricos.'];
        }

        $ok = $this->secModel->upsertLoginCode($userId, $codePlain);

        return $ok
            ? ['success' => true,  'message' => 'Código de acesso salvo com sucesso.']
            : ['success' => false, 'message' => 'Erro ao salvar o código. Tente novamente.'];
    }

    /**
     * Remove o código de login do usuário.
     */
    public function removeCode(int $userId): array
    {
        $exists = $this->secModel->getLoginCode($userId);
        if ($exists === null) {
            return ['success' => false, 'message' => 'Nenhum código cadastrado.'];
        }

        $ok = $this->secModel->deleteLoginCode($userId);
        return $ok
            ? ['success' => true,  'message' => 'Código removido com sucesso.']
            : ['success' => false, 'message' => 'Erro ao remover o código.'];
    }

    // ──────────────────────────────────────────────────────────────────────
    // CÓDIGO DE LOGIN — fluxo de autenticação na página de login
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Autentica o usuário via código de 4 dígitos.
     * Em caso de sucesso, inicia a sessão PHP.
     */
    public function authenticateByCode(string $codePlain, string $ip, string $userAgent): array
    {
        $result = $this->secModel->verifyLoginCode($codePlain, $ip, $userAgent);

        if (!$result['ok']) {
            return [
                'success'      => false,
                'message'      => $result['error'],
                'locked_until' => $result['locked_until'] ?? null,
            ];
        }

        $userId = $result['user_id'];

        // Carrega dados do usuário no banco CineVEO
        $user = $this->loadCineveoUser($userId);

        if ($user === null) {
            return ['success' => false, 'message' => 'Conta associada não encontrada.'];
        }

        // Inicia sessão
        $this->startUserSession($user);

        return ['success' => true, 'message' => 'Autenticado com sucesso.'];
    }

    // ──────────────────────────────────────────────────────────────────────
    // QR CODE — geração
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Gera uma nova sessão QR Code e retorna os dados para o frontend renderizar o QR.
     */
    public function generateQrSession(string $ip, string $userAgent): array
    {
        // Limpa sessões expiradas antes de criar nova
        $this->secModel->expireOldQrSessions();

        $session = $this->secModel->createQrSession($ip, $userAgent);

        // URL que será codificada no QR Code
        // O servidor PipoCine deve ter SSL (HTTPS) em produção
        $confirmUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http')
                    . '://' . ($_SERVER['HTTP_HOST'] ?? 'pipocine.site')
                    . '/auth/qr/confirm?token=' . urlencode($session['token']);

        return [
            'success'     => true,
            'session_id'  => $session['session_id'],
            'token'       => $session['token'],
            'confirm_url' => $confirmUrl,
            'expires_at'  => $session['expires_at'],
            'ttl'         => SecurityModel::QR_TTL,
        ];
    }

    // ──────────────────────────────────────────────────────────────────────
    // QR CODE — polling de status (chamado pelo frontend a cada 2s)
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Verifica o status da sessão QR.
     * Se 'used', inicia a sessão PHP e sinaliza o frontend para redirecionar.
     */
    public function pollQrSession(string $token): array
    {
        $session = $this->secModel->getQrSessionByToken($token);

        if ($session === null) {
            return ['status' => 'not_found', 'message' => 'Sessão não encontrada.'];
        }

        $now       = new DateTimeImmutable();
        $expiresAt = new DateTimeImmutable($session['expires_at']);

        if ($session['status'] === 'expired' || $now > $expiresAt) {
            return ['status' => 'expired', 'message' => 'QR Code expirado. Gere um novo.'];
        }

        if ($session['status'] === 'used' && $session['user_id'] !== null) {
            // Inicia sessão no servidor
            $user = $this->loadCineveoUser((int)$session['user_id']);

            if ($user) {
                $this->startUserSession($user);
                return ['status' => 'authenticated', 'redirect' => '/home'];
            }
        }

        $secondsLeft = max(0, $expiresAt->getTimestamp() - $now->getTimestamp());

        return [
            'status'       => 'pending',
            'seconds_left' => $secondsLeft,
        ];
    }

    // ──────────────────────────────────────────────────────────────────────
    // QR CODE — confirmação (endpoint chamado pelo dispositivo que escaneou)
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Confirma a sessão QR no contexto de um usuário JÁ autenticado no dispositivo móvel.
     * O dispositivo que escaneia deve ter uma sessão PHP válida.
     */
    public function confirmQrSession(string $token, int $userId, string $ip): array
    {
        $confirmed = $this->secModel->confirmQrSession($token, $userId, $ip);

        if (!$confirmed) {
            return [
                'success' => false,
                'message' => 'QR Code inválido, expirado ou já utilizado.',
            ];
        }

        return [
            'success' => true,
            'message' => 'QR Code confirmado. Faça a verificação no dispositivo original.',
        ];
    }

    // ──────────────────────────────────────────────────────────────────────
    // UTILITÁRIOS INTERNOS
    // ──────────────────────────────────────────────────────────────────────

    private function loadCineveoUser(int $userId): ?array
    {
        if ($this->pdoCineveo === null) return null;

        try {
            $stmt = $this->pdoCineveo->prepare('
                SELECT id, username, full_name, name, profile_pic_url
                FROM users
                WHERE id = ?
                LIMIT 1
            ');
            $stmt->execute([$userId]);
            return $stmt->fetch() ?: null;
        } catch (Throwable) {
            return null;
        }
    }

    private function startUserSession(array $user): void
    {
        $_SESSION['user_id']         = $user['id'];
        $_SESSION['username']        = $user['username'];
        $_SESSION['full_name']       = $user['full_name'] ?: ($user['name'] ?? '');
        $_SESSION['profile_pic_url'] = $user['profile_pic_url'] ?? null;
    }
}
