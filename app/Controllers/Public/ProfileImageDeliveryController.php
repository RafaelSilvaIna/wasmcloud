<?php
namespace App\Controllers\Public;

use App\Repositories\ProfileImageRepository;
use App\Services\UploadService;
use App\Repositories\SystemConfigRepository;

class ProfileImageDeliveryController {
    public function serve() {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        $role = isset($_GET['role']) ? $_GET['role'] : null;
        $userid = isset($_GET['userid']) ? $_GET['userid'] : null;

        if (!$role || !$userid) {
            if (preg_match('#/api/profile-img/([^/]+)/([0-9]+)#', $uri, $matches)) {
                $role = $matches[1];
                $userid = $matches[2];
            }
        }

        if (!$role || !$userid) {
            $this->serveNotFound();
        }

        $configRepo = new SystemConfigRepository();
        if (!$configRepo->arePhotosEnabled()) {
            http_response_code(403);
            exit;
        }

        try {
            $repo = new ProfileImageRepository();
            $filename = $repo->getFilename($role, $userid);
        } catch (\Exception $e) {
            $this->serveNotFound();
        }

        $uploadService = new UploadService();
        $path = $filename ? $uploadService->getFilePath($filename) : null;

        if (!$path || !file_exists($path)) {
            $this->serveNotFound();
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $path);
        finfo_close($finfo);

        header("Content-Type: {$mimeType}");
        header("Cache-Control: public, max-age=86400");
        readfile($path);
        exit;
    }

    private function serveNotFound() {
        $path = __DIR__ . '/../../../public/assets/img/not-found.png';
        if (file_exists($path)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $path);
            finfo_close($finfo);
            header("Content-Type: {$mimeType}");
            readfile($path);
        } else {
            http_response_code(404);
        }
        exit;
    }
}