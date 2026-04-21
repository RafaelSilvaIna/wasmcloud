<?php
namespace App\Controllers\Public;

use App\Repositories\ProfileImageRepository;
use App\Services\UploadService;
use App\Services\SystemConfigRepository;

class ProfileImageDeliveryController {
    public function serve($role, $userid) {
        $configRepo = new SystemConfigRepository();
        if (!$configRepo->arePhotosEnabled()) {
            http_response_code(403);
            exit;
        }

        $repo = new ProfileImageRepository();
        $filename = $repo->getFilename($role, $userid);

        $uploadService = new UploadService();
        $path = $filename ? $uploadService->getFilePath($filename) : null;

        if (!$path || !file_exists($path)) {
            $path = __DIR__ . '/../../../public/assets/img/not-found.png';
        }

        if (!file_exists($path)) {
            http_response_code(404);
            exit;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $path);
        finfo_close($finfo);

        header("Content-Type: {$mimeType}");
        readfile($path);
        exit;
    }
}