<?php
class ProfileController {
    private $profileService;

    public function __construct(ProfileService $profileService) {
        $this->profileService = $profileService;
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
            $model = new ProfileModel($GLOBALS['pdo']);
            $model->updateWatchingStatus((int)$_SESSION['profile_id'], true, session_id());
            echo json_encode(['success' => true]);
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
}