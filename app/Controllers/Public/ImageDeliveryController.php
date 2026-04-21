<?php
namespace App\Controllers\Public;

use App\Repositories\BrandingRepository;
use App\Services\UploadService;

class ImageDeliveryController {
    public function serveImage($type) {
        $repo = new BrandingRepository();
        $branding = $repo->getBranding();
        $filename = $branding[$type . '_filename'] ?? null;

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
        header("Cache-Control: public, max-age=86400");
        readfile($path);
        exit;
    }

    public function serveLogo() { $this->serveImage('logo'); }
    public function serveFavicon() { $this->serveImage('favicon'); }
    public function serveIcon() { $this->serveImage('icon'); }
    
    public function getBrandingInfo() {
        $repo = new BrandingRepository();
        $branding = $repo->getBranding();
        
        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'success',
            'data' => [
                'institution_name' => $branding['institution_name'],
                'abbreviation' => $branding['abbreviation'],
                'slogan' => $branding['slogan'],
                'logo_url' => '/api/img/logo',
                'favicon_url' => '/api/img/favicon',
                'icon_url' => '/api/img/icon'
            ]
        ]);
        exit;
    }
}