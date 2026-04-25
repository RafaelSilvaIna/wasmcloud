<?php
class ProfileService {
    private $profileModel;

    public function __construct(ProfileModel $profileModel) {
        $this->profileModel = $profileModel;
    }

    public function isUsernameAvailable(string $username): bool {
        if (strlen($username) < 3) return false;
        return !$this->profileModel->checkUsernameExists($username);
    }

    public function addNewProfile(array $data): array {
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

    public function selectProfile(int $profileId, ?string $pin = null): array {
        $profile = $this->profileModel->findById($profileId);
        
        if (!$profile || $profile['user_id'] != $_SESSION['user_id']) {
            return ['success' => false, 'message' => 'Perfil inválido.'];
        }

        if ($profile['pin_hash']) {
            if (!$pin || !password_verify($pin, $profile['pin_hash'])) {
                return ['success' => false, 'requires_pin' => true, 'message' => 'PIN incorreto.'];
            }
        }

        $_SESSION['profile_id'] = $profile['id'];
        $_SESSION['profile_name'] = $profile['profile_name'];
        $_SESSION['profile_image'] = $profile['profile_image'];
        $_SESSION['is_kids'] = (bool)$profile['is_kids'];

        return ['success' => true];
    }
}