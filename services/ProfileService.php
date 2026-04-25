<?php
class ProfileService {
    private $profileModel;
    private $authModel;

    public function __construct(ProfileModel $profileModel, AuthModel $authModel) {
        $this->profileModel = $profileModel;
        $this->authModel = $authModel;
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
            'user_id' => $_SESSION['user_id'],
            'profile_name' => strip_tags($data['name']),
            'username' => strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $data['username'])),
            'pin_hash' => $pinHash,
            'profile_image' => $data['image'],
            'is_kids' => $data['type'] === 'kids' ? 1 : 0
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
}