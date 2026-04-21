<?php
namespace App\Controllers\Admin;

use App\Repositories\BrandingRepository;
use App\Services\UploadService;
use App\Services\SecurityVault;
use App\Services\AuditLogger;

class BrandingController {
    public function updateBranding() {
        $repo = new BrandingRepository();
        $uploadService = new UploadService();
        $currentBranding = $repo->getBranding();

        $name = isset($_POST['institution_name']) ? SecurityVault::sanitize($_POST['institution_name']) : $currentBranding['institution_name'];
        $abbrev = isset($_POST['abbreviation']) ? SecurityVault::sanitize($_POST['abbreviation']) : $currentBranding['abbreviation'];
        $slogan = isset($_POST['slogan']) ? SecurityVault::sanitize($_POST['slogan']) : $currentBranding['slogan'];

        $repo->updateTexts($name, $abbrev, $slogan);

        $imageTypes = ['logo', 'favicon', 'icon'];
        foreach ($imageTypes as $type) {
            if (isset($_FILES[$type]) && $_FILES[$type]['error'] === UPLOAD_ERR_OK) {
                $oldFilename = $currentBranding[$type . '_filename'];
                $newFilename = $uploadService->handleImageUpload($_FILES[$type], $oldFilename);
                
                if ($newFilename) {
                    $repo->updateImageFilename($type, $newFilename);
                }
            }
        }

        AuditLogger::log(
            $_SERVER['MASTER_ID'], 
            $_SERVER['MASTER_ROLE'], 
            'Atualizou as configuracoes de branding (textos e/ou imagens)'
        );

        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'success', 'message' => 'Branding atualizado com sucesso.']);
        exit;
    }
}