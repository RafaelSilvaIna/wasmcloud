<?php
namespace App\Services;

use App\Repositories\ThemeRepository;

class ThemeService {
    private $repository;

    public function __construct() {
        $this->repository = new ThemeRepository();
    }

    public function getActiveTheme() {
        return $this->repository->getTheme();
    }

    public function updateSystemTheme($input) {
        $current = $this->repository->getTheme();
        
        $validData = [
            'primary_color'    => $this->validateHex($input['primary_color'] ?? $current['primary_color']),
            'secondary_color'  => $this->validateHex($input['secondary_color'] ?? $current['secondary_color']),
            'background_color' => $this->validateHex($input['background_color'] ?? $current['background_color']),
            'text_color'       => $this->validateHex($input['text_color'] ?? $current['text_color']),
            'accent_color'     => $this->validateHex($input['accent_color'] ?? $current['accent_color'])
        ];

        $success = $this->repository->updateTheme($validData);

        if ($success) {
            AuditLogger::log(
                $_SERVER['MASTER_ID'], 
                $_SERVER['MASTER_ROLE'], 
                'Alterou as cores da identidade visual do sistema'
            );
        }

        return $success;
    }

    private function validateHex($color) {
        if (preg_match('/^#[a-fA-F0-9]{6}$/', $color)) {
            return $color;
        }
        return '#000000';
    }
}