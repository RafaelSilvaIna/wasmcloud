<?php
namespace App\Services;

use App\Repositories\SystemConfigRepository;

class SystemConfigService {
    private $repository;

    public function __construct() {
        $this->repository = new SystemConfigRepository();
    }

    public function getOrInitializeConfigs() {
        $configs = $this->repository->getAll();
        
        if (empty($configs)) {
            $this->repository->initializeDefaults();
            $configs = $this->repository->getAll();
            return ['status' => 'pending_setup', 'configs' => $configs];
        }

        $setupCompleted = $configs['system_setup_completed'] ?? false;
        return [
            'status' => $setupCompleted ? 'active' : 'pending_setup',
            'configs' => $configs
        ];
    }

    public function updateGlobalConfigs($inputData) {
        $db = \App\Core\Database::getInstance();
        $db->beginTransaction();

        try {
            foreach ($inputData as $key => $value) {
                $dbValue = $value === true ? 'true' : 'false';
                $this->repository->set($key, $dbValue);
            }
            
            $this->repository->set('system_setup_completed', 'true');
            
            $db->commit();
            AuditLogger::log($_SERVER['MASTER_ID'], $_SERVER['MASTER_ROLE'], 'Políticas globais de acesso atualizadas no Kernel');
            return true;
        } catch (\PDOException $e) {
            $db->rollBack();
            return false;
        }
    }
}