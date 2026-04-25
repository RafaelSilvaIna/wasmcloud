<?php
class AuthService {
    private $authModel;

    public function __construct(AuthModel $authModel) {
        $this->authModel = $authModel;
    }

    public function checkRealTimeAuth(): array {
        if (!isset($_SESSION['user_id'])) {
            return ['isAuthenticated' => false, 'user' => null];
        }

        $user = $this->authModel->getUserData((int)$_SESSION['user_id']);

        if (!$user) {
            $_SESSION = [];
            session_destroy();
            return ['isAuthenticated' => false, 'user' => null];
        }

        return [
            'isAuthenticated' => true,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'fullName' => $user['full_name'] ?: $user['name'],
                'avatar' => $user['profile_pic_url'],
                'email' => $user['email'],
                'plan' => $user['plan_type'],
                'role' => $user['role']
            ]
        ];
    }
}