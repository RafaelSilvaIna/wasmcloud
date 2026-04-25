<?php
class ProfileController {
    private $profileService;

    public function __construct(ProfileService $profileService) {
        $this->profileService = $profileService;
    }

    public function checkUsername(): void {
        header('Content-Type: application/json');
        $username = $_GET['username'] ?? '';
        echo json_encode(['available' => $this->profileService->isUsernameAvailable($username)]);
    }

    public function getAvatars(): void {
        header('Content-Type: application/json');
        $category = $_GET['category'] ?? 'adventurer';
        echo json_encode(['avatars' => AvatarHelper::generateRandomAvatars($category)]);
    }

    public function list(): void {
        header('Content-Type: application/json');
        $model = new ProfileModel($GLOBALS['pdo']);
        echo json_encode($model->listByUserId($_SESSION['user_id']));
    }

    public function create(): void {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        echo json_encode($this->profileService->addNewProfile($data));
    }

    public function select(): void {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        echo json_encode($this->profileService->selectProfile($data['id'], $data['pin'] ?? null));
    }
}