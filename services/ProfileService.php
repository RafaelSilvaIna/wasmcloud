<?php
class ProfileService
{
    private $profileModel;
    private $authModel;

    private const DEFAULT_PROFILE_LIMIT = 2;
    private const PREMIUM_PROFILE_LIMIT = 8;

    public function __construct(ProfileModel $profileModel, AuthModel $authModel)
    {
        $this->profileModel = $profileModel;
        $this->authModel = $authModel;
    }

    private function getCurrentUserId(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    private function getCurrentAuthProvider(): string
    {
        return $_SESSION['auth_provider'] ?? 'cineveo';
    }

    /**
     * Retorna true se o usuário tem assinatura premium ativa —
     * inclui planos pagos E planos de cortesia (admin_courtesy).
     */
    private function isPremiumActive(int $userId): bool
    {
        return $this->authModel->hasActivePremiumSubscription($userId);
    }

    private function getProfileLimitForCurrentUser(): array
    {
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            return ['success' => false, 'message' => 'Sessao expirada.'];
        }

        if ($this->getCurrentAuthProvider() === 'pipocine') {
            return [
                'success' => true,
                'isPremium' => false,
                'limit' => self::DEFAULT_PROFILE_LIMIT
            ];
        }

        $isPremium = $this->isPremiumActive($userId);

        return [
            'success' => true,
            'isPremium' => $isPremium,
            'limit' => $isPremium ? self::PREMIUM_PROFILE_LIMIT : self::DEFAULT_PROFILE_LIMIT
        ];
    }

    public function getProfilesForUser(): array
    {
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            return [];
        }

        return $this->profileModel->listByUserId($userId);
    }

    public function isUsernameAvailable(string $username): array
    {
        $username = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $username));
        if (strlen($username) < 3) {
            return ['available' => false];
        }

        $exists = $this->profileModel->findByUsername($username);
        return ['available' => $exists === null];
    }

    public function selectProfile(int $profileId, ?string $pin): array
    {
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            return ['success' => false, 'message' => 'Nao autenticado.'];
        }

        $profile = $this->profileModel->findById($profileId);
        if (!$profile || (int) $profile['user_id'] !== $userId) {
            return ['success' => false, 'message' => 'Perfil nao encontrado.'];
        }

        if (!empty($profile['pin_hash'])) {
            if (empty($pin) || !password_verify($pin, $profile['pin_hash'])) {
                return ['success' => false, 'message' => 'PIN incorreto.'];
            }
        }

        $activeSession = $this->authModel->hasActiveSession($profileId);
        $currentSessionId = session_id();

        if ($activeSession) {
            $existingSession = $this->authModel->getActiveProfileSession($profileId);
            if ($existingSession && $existingSession['session_id'] !== $currentSessionId) {
                return [
                    'success' => false,
                    'message' => 'Este perfil ja esta sendo usado em outro dispositivo.',
                    'code' => 'PROFILE_IN_USE'
                ];
            }
        }

        $_SESSION['profile_id'] = $profile['id'];
        $_SESSION['profile_name'] = $profile['profile_name'];
        $_SESSION['profile_image'] = $profile['profile_image'];
        $_SESSION['profile_is_kids'] = (bool) (int) $profile['is_kids'];

        $expiresAt = date('Y-m-d H:i:s', time() + 2592000);
        $this->authModel->createActiveSession($profileId, $userId, $currentSessionId, $expiresAt);
        $this->profileModel->updateWatchingStatus($profileId, true, $currentSessionId);

        return ['success' => true];
    }

    public function addNewProfile(array $data): array
    {
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            return ['success' => false, 'message' => 'Sessao expirada.'];
        }

        $profileLimit = $this->getProfileLimitForCurrentUser();
        if (empty($profileLimit['success'])) {
            return $profileLimit;
        }

        $currentProfilesCount = $this->profileModel->countByUserId($userId);
        $isPremium = (bool) $profileLimit['isPremium'];
        $limit = (int) $profileLimit['limit'];

        if ($currentProfilesCount >= $limit) {
            $msg = $isPremium
                ? "Limite de perfis atingido para usuarios Premium ($limit)."
                : "O plano Casual permite apenas $limit perfis. Torne-se Premium para criar ate 8!";
            return ['success' => false, 'message' => $msg];
        }

        $name = strip_tags(trim($data['name'] ?? ''));
        $username = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $data['username'] ?? ''));
        $image = trim($data['image'] ?? '');

        if ($name === '') {
            return ['success' => false, 'message' => 'O nome do perfil nao pode estar vazio.'];
        }

        if (strlen($username) < 3) {
            return ['success' => false, 'message' => 'O nome de usuario precisa ter pelo menos 3 caracteres.'];
        }

        if ($image === '') {
            return ['success' => false, 'message' => 'Escolha um avatar para o perfil.'];
        }

        $pinHash = !empty($data['pin']) ? password_hash($data['pin'], PASSWORD_ARGON2ID) : null;

        $payload = [
            'user_id' => $userId,
            'profile_name' => $name,
            'username' => $username,
            'pin_hash' => $pinHash,
            'profile_image' => $image,
            'is_kids' => (isset($data['type']) && $data['type'] === 'kids') ? 1 : 0
        ];

        if ($this->profileModel->create($payload)) {
            return ['success' => true];
        }

        return ['success' => false, 'message' => 'Erro ao criar perfil no banco de dados.'];
    }

    public function startWatching(int $profileId): array
    {
        $session = $this->profileModel->checkActiveSession($profileId);
        $now = new DateTime();

        if ($session['is_watching']) {
            $lastActive = new DateTime($session['last_active_at']);
            $diff = $now->getTimestamp() - $lastActive->getTimestamp();

            if ($diff < 60 && $session['current_session_id'] !== session_id()) {
                return ['success' => false, 'message' => 'Este perfil ja esta sendo usado em outro dispositivo.'];
            }
        }

        $this->profileModel->updateWatchingStatus($profileId, true, session_id());
        return ['success' => true];
    }

    public function stopWatching(int $profileId): void
    {
        $this->profileModel->updateWatchingStatus($profileId, false, null);
        $this->authModel->deactivateProfileSessions($profileId);
    }

    public function updateProfile(array $data): array
    {
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            return ['success' => false, 'message' => 'Nao autenticado.'];
        }

        $id       = (int) ($data['id'] ?? 0);
        $name     = strip_tags(trim($data['name'] ?? ''));
        $image    = $data['image'] ?? '';
        $username = isset($data['username']) ? strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $data['username'])) : null;

        if ($name === '') {
            return ['success' => false, 'message' => 'O nome do perfil nao pode estar vazio.'];
        }

        // Validação de username (se fornecido)
        if ($username !== null && $username !== '') {
            if (strlen($username) < 3) {
                return ['success' => false, 'message' => 'Username muito curto.'];
            }
            $conflict = $this->profileModel->findByUsernameExcluding($username, $id);
            if ($conflict) {
                return ['success' => false, 'message' => 'Este username ja esta em uso.'];
            }
        }

        if ($this->profileModel->updateProfile($id, $userId, $name, $image, $username ?: null)) {
            if (isset($_SESSION['profile_id']) && $_SESSION['profile_id'] == $id) {
                $_SESSION['profile_name']  = $name;
                $_SESSION['profile_image'] = $image;
            }
            return ['success' => true];
        }

        return ['success' => false, 'message' => 'Erro ao atualizar o perfil.'];
    }

    public function deleteProfile(int $profileId): array
    {
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            return ['success' => false, 'message' => 'Nao autenticado.'];
        }

        $profile = $this->profileModel->findById($profileId);
        if (!$profile || (int) $profile['user_id'] !== $userId) {
            return ['success' => false, 'message' => 'Perfil nao encontrado.'];
        }

        // Não pode excluir o perfil atualmente em uso
        if (isset($_SESSION['profile_id']) && (int) $_SESSION['profile_id'] === $profileId) {
            unset($_SESSION['profile_id'], $_SESSION['profile_name'], $_SESSION['profile_image']);
        }

        if ($this->profileModel->deleteProfile($profileId, $userId)) {
            return ['success' => true];
        }

        return ['success' => false, 'message' => 'Erro ao excluir perfil.'];
    }

    /**
     * Valida o limite de perfis e retorna a URL de redirecionamento para criação.
     * Não depende de tabela de tokens — usa sessão PHP para proteção.
     */
    public function issueCreationToken(): array
    {
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            return ['success' => false, 'message' => 'Nao autenticado.'];
        }

        $profileLimit = $this->getProfileLimitForCurrentUser();
        if (empty($profileLimit['success'])) {
            return $profileLimit;
        }

        $currentCount = $this->profileModel->countByUserId($userId);
        $limit        = (int) $profileLimit['limit'];
        $isPremium    = (bool) $profileLimit['isPremium'];

        if ($currentCount >= $limit) {
            $msg = $isPremium
                ? "Limite de $limit perfis atingido."
                : "O plano gratuito permite apenas $limit perfis. Assine o Premium para criar ate 8!";
            return ['success' => false, 'message' => $msg, 'limit_reached' => true];
        }

        // Armazena flag na sessao para proteger a pagina de criacao
        $_SESSION['can_create_profile'] = true;

        return ['success' => true, 'redirect' => '/create/profile'];
    }

    /**
     * Emite um token de edição de perfil.
     */
    public function issueEditToken(int $profileId): array
    {
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            return ['success' => false, 'message' => 'Nao autenticado.'];
        }

        $profile = $this->profileModel->findById($profileId);
        if (!$profile || (int) $profile['user_id'] !== $userId) {
            return ['success' => false, 'message' => 'Perfil nao encontrado.'];
        }

        return ['success' => true, 'redirect' => "/create/profile/edit={$profileId}"];
    }

    /**
     * Adiciona novo perfil com suporte a token de segurança.
     * Consome o token após criação bem-sucedida.
     */
    /**
     * Alias de addNewProfile mantido por compatibilidade com chamadas existentes.
     */
    public function addNewProfileWithToken(array $data): array
    {
        return $this->addNewProfile($data);
    }
}
