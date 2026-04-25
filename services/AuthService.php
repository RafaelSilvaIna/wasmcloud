<?php
class AuthService {
    private $authModel;

    public function __construct(AuthModel $authModel) {
        $this->authModel = $authModel;
    }

    public function checkRealTimeAuth(): array {
        if (!isset($_SESSION['userId'])) {
            return [
                'isAuthenticated' => false,
                'user' => null
            ];
        }

        $user = $this->authModel->getUserById((int)$_SESSION['userId']);

        if (!$user) {
            session_destroy();
            return [
                'isAuthenticated' => false,
                'user' => null
            ];
        }

        return [
            'isAuthenticated' => true,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'fullName' => $user['full_name'],
                'avatar' => $user['profile_pic_url'],
                'email' => $user['email']
            ]
        ];
    }
}