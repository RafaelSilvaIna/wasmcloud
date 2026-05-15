<?php
class ProfileController {
    private $profileService;

    public function __construct(ProfileService $profileService) {
        $this->profileService = $profileService;
    }

    public function list(): void {
        header('Content-Type: application/json');
        echo json_encode($this->profileService->getProfilesForUser());
    }

    public function checkUsername(): void {
        header('Content-Type: application/json');
        $username = $_GET['username'] ?? '';
        echo json_encode($this->profileService->isUsernameAvailable($username));
    }

    public function getAvatars(): void {
        header('Content-Type: application/json');
        $category = $_GET['category'] ?? 'adventurer';
        require_once __DIR__ . '/../helpers/AvatarHelper.php';
        echo json_encode(['avatars' => AvatarHelper::generate($category)]);
    }

    public function select(): void {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        echo json_encode($this->profileService->selectProfile((int)($data['id'] ?? 0), $data['pin'] ?? null));
    }

    public function current(): void {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['authenticated' => false, 'profile' => null]);
            exit;
        }
        if (!isset($_SESSION['profile_id'])) {
            echo json_encode(['authenticated' => true, 'profile' => null]);
            exit;
        }

        $profile = $this->profileService->getCurrentSelectedProfile();
        if (!$profile) {
            echo json_encode(['authenticated' => true, 'profile' => null]);
            exit;
        }

        echo json_encode([
            'authenticated' => true,
            'profile' => $profile
        ]);
    }

    public function create(): void {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        echo json_encode($this->profileService->addNewProfile($data));
    }

    public function startSession(): void {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        $profileId = $data['profile_id'] ?? ($_SESSION['profile_id'] ?? 0);
        echo json_encode($this->profileService->startWatching((int)$profileId));
    }

    public function heartbeat(): void {
        header('Content-Type: application/json');
        if (isset($_SESSION['profile_id'])) {
            echo json_encode($this->profileService->startWatching((int) $_SESSION['profile_id']));
        } else {
            echo json_encode(['success' => false]);
        }
    }

    public function stopSession(): void {
        header('Content-Type: application/json');
        if (isset($_SESSION['profile_id'])) {
            $this->profileService->stopWatching((int)$_SESSION['profile_id']);
        }
        echo json_encode(['success' => true]);
    }

    public function update(): void {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        echo json_encode($this->profileService->updateProfile($data));
    }

    public function delete(): void {
        header('Content-Type: application/json');
        $data      = json_decode(file_get_contents('php://input'), true);
        $profileId = (int) ($data['id'] ?? 0);
        echo json_encode($this->profileService->deleteProfile($profileId));
    }

    public function issueCreationToken(): void {
        header('Content-Type: application/json');
        echo json_encode($this->profileService->issueCreationToken());
    }

    public function issueEditToken(): void {
        header('Content-Type: application/json');
        $profileId = (int) ($_GET['profile_id'] ?? 0);
        echo json_encode($this->profileService->issueEditToken($profileId));
    }

    public function createWithToken(): void {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        echo json_encode($this->profileService->addNewProfileWithToken($data));
    }
}
