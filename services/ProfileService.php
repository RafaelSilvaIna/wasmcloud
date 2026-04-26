<?php
class ProfileService {
    private $profileModel;
    private $authModel;

    public function __construct(ProfileModel $profileModel, AuthModel $authModel) {
        $this->profileModel = $profileModel;
        $this->authModel = $authModel;
    }

    public function getProfilesForUser(): array {
        if (!isset($_SESSION['user_id'])) return [];
        return $this->profileModel->listByUserId((int)$_SESSION['user_id']);
    }

    public function isUsernameAvailable(string $username): array {
        $username = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $username));
        if (strlen($username) < 3) return ['available' => false];
        $exists = $this->profileModel->findByUsername($username);
        return ['available' => $exists === null];
    }

    public function selectProfile(int $profileId, ?string $pin): array {
        if (!isset($_SESSION['user_id'])) {
            return ['success' => false, 'message' => 'Não autenticado.'];
        }
        $profile = $this->profileModel->findById($profileId);
        if (!$profile || (int)$profile['user_id'] !== (int)$_SESSION['user_id']) {
            return ['success' => false, 'message' => 'Perfil não encontrado.'];
        }
        if (!empty($profile['pin_hash'])) {
            if (empty($pin) || !password_verify($pin, $profile['pin_hash'])) {
                return ['success' => false, 'message' => 'PIN incorreto.'];
            }
        }
        $_SESSION['profile_id']    = $profile['id'];
        $_SESSION['profile_name']  = $profile['profile_name'];
        $_SESSION['profile_image'] = $profile['profile_image'];
        return ['success' => true];
    }

    public function addNewProfile(array $data): array {
        $userCineveo = $this->authModel->getUserData((int)$_SESSION['user_id']);
        $currentProfilesCount = $this->profileModel->countByUserId((int)$_SESSION['user_id']);
        
        $limit = ($userCineveo['plan_type'] === 'premium') ? 8 : 2;

        if ($currentProfilesCount >= $limit) {
            return ['success' => false, 'message' => "Limite de perfis atingido para seu plano ($limit)."];
        }

        $pinHash = !empty($data['pin']) ? password_hash($data['pin'], PASSWORD_ARGON2ID) : null;
        
        $payload = [
            'user_id'       => $_SESSION['user_id'],
            'profile_name'  => strip_tags($data['name']),
            'username'      => strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $data['username'])),
            'pin_hash'      => $pinHash,
            'profile_image' => $data['image'],
            'is_kids'       => $data['type'] === 'kids' ? 1 : 0
        ];

        if ($this->profileModel->create($payload)) {
            return ['success' => true];
        }
        return ['success' => false, 'message' => 'Erro ao criar perfil.'];
    }

    public function startWatching(int $profileId): array {
        $session = $this->profileModel->checkActiveSession($profileId);
        $now = new DateTime();
        
        if ($session['is_watching']) {
            $lastActive = new DateTime($session['last_active_at']);
            $diff = $now->getTimestamp() - $lastActive->getTimestamp();

            if ($diff < 60 && $session['current_session_id'] !== session_id()) {
                return ['success' => false, 'message' => 'Este perfil já está sendo usado em outro dispositivo.'];
            }
        }

        $this->profileModel->updateWatchingStatus($profileId, true, session_id());
        return ['success' => true];
    }

    public function stopWatching(int $profileId): void {
        $this->profileModel->updateWatchingStatus($profileId, false, null);
    }

    // NOVA FUNÇÃO: Processa a edição de perfil
    public function updateProfile(array $data): array {
        if (!isset($_SESSION['user_id'])) {
            return ['success' => false, 'message' => 'Não autenticado.'];
        }
        
        $id = (int)($data['id'] ?? 0);
        $name = strip_tags(trim($data['name'] ?? ''));
        $image = $data['image'] ?? '';

        if (empty($name)) {
            return ['success' => false, 'message' => 'O nome do perfil não pode estar vazio.'];
        }

        if ($this->profileModel->updateProfile($id, $_SESSION['user_id'], $name, $image)) {
            // Se o perfil que foi atualizado for o que está atualmente selecionado na sessão, atualiza os dados visuais na hora
            if (isset($_SESSION['profile_id']) && $_SESSION['profile_id'] == $id) {
                $_SESSION['profile_name'] = $name;
                $_SESSION['profile_image'] = $image;
            }
            return ['success' => true];
        }
        
        return ['success' => false, 'message' => 'Erro ao atualizar o perfil.'];
    }
}