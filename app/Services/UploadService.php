<?php
namespace App\Services;

class UploadService {
    private $uploadDir;

    public function __construct() {
        $this->uploadDir = __DIR__ . '/../../storage/uploads/branding/';
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    public function handleImageUpload($fileArray, $oldFilename = null) {
        if ($fileArray['error'] !== UPLOAD_ERR_OK) {
            return false;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $fileArray['tmp_name']);
        finfo_close($finfo);

        $allowedMimes = ['image/jpeg', 'image/png', 'image/x-icon', 'image/svg+xml'];
        if (!in_array($mimeType, $allowedMimes)) {
            return false;
        }

        if ($oldFilename && file_exists($this->uploadDir . $oldFilename)) {
            unlink($this->uploadDir . $oldFilename);
        }

        $extension = pathinfo($fileArray['name'], PATHINFO_EXTENSION);
        $newFilename = bin2hex(random_bytes(16)) . '.' . $extension;
        $destination = $this->uploadDir . $newFilename;

        if (move_uploaded_file($fileArray['tmp_name'], $destination)) {
            return $newFilename;
        }

        return false;
    }

    public function getFilePath($filename) {
        return $this->uploadDir . $filename;
    }
}