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
        $status = 'active';

        if (empty($configs)) {
            $this->repository->initializeDefaults();
            $configs = $this->repository->getAll();
            $status = 'initialized';
            
            AuditLogger::log(
                $_SERVER['MASTER_ID'], 
                $_SERVER['MASTER_ROLE'], 
                'Configuracoes globais inicializadas automaticamente com padroes'
            );
        }

        return ['status' => $status, 'configs' => $configs];
    }

    public function updateGlobalConfigs($inputData) {
        $configs = $this->getOrInitializeConfigs()['configs'];
        $db = \App\Core\Database::getInstance();
        $db->beginTransaction();

        try {
            foreach ($inputData as $key => $value) {
                if (array_key_exists($key, $configs)) {
                    $dbValue = $value === true ? 'true' : 'false';
                    $this->repository->update($key, $dbValue);
                }
            }
            $db->commit();
            AuditLogger::log(
                $_SERVER['MASTER_ID'], 
                $_SERVER['MASTER_ROLE'], 
                'Configuracoes globais de permissoes atualizadas'
            );
            return true;
        } catch (\PDOException $e) {
            $db->rollBack();
            AuditLogger::log(
                $_SERVER['MASTER_ID'], 
                $_SERVER['MASTER_ROLE'], 
                'Falha ao atualizar configuracoes globais', 
                'failure'
            );
            return false;
        }
    }
}