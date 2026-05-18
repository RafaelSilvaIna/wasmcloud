<?php
declare(strict_types=1);

namespace Services\Ads;

use Helpers\Ads\AdsValidator;
use Models\Ads\AdsAccountModel;

final class AdsAuthService
{
    public function __construct(private readonly AdsAccountModel $model) {}

    public function register(array $data): array
    {
        if (!empty($_SESSION['ads_account_id'])) {
            return [
                'success' => true,
                'already_authenticated' => true,
                'message' => 'Você já possui uma sessão comercial ativa.',
                'redirect' => '/ads/dashboard',
            ];
        }

        if (!empty($_SESSION['user_id'])) {
            $linkedAccount = $this->model->findByPipocineUserId((int) $_SESSION['user_id']);
            if ($linkedAccount) {
                $_SESSION['ads_account_id'] = (int) $linkedAccount['id'];
                return [
                    'success' => true,
                    'already_authenticated' => true,
                    'message' => 'Sua conta comercial já está vinculada.',
                    'redirect' => '/ads/dashboard',
                ];
            }
        }

        $brand = AdsValidator::brand((string)($data['brand_name'] ?? ''));
        $cnpj = AdsValidator::cnpj($data['cnpj'] ?? null);
        $email = AdsValidator::email((string)($data['email'] ?? ''));
        $password = (string)($data['password'] ?? '');

        if (!$brand) return ['success'=>false,'message'=>'Informe o nome da empresa ou marca.'];
        if ($cnpj === '') return ['success'=>false,'message'=>'CNPJ inválido.'];
        if (!$email) return ['success'=>false,'message'=>'Informe um email profissional válido.'];
        if (!AdsValidator::password($password)) return ['success'=>false,'message'=>'A senha deve ter 8 caracteres, letra maiúscula, minúscula e número.'];
        if ($this->model->findByEmail($email)) return ['success'=>false,'message'=>'Este email já possui uma conta Ads.'];

        $id = $this->model->create($brand, $cnpj, $email, password_hash($password, PASSWORD_ARGON2ID), null);
        $_SESSION['ads_account_id'] = $id;
        return ['success'=>true,'linked'=>false,'redirect'=>!empty($_SESSION['user_id']) ? '/ads/link' : '/ads/dashboard'];
    }

    public function login(string $email, string $password): array
    {
        if (!empty($_SESSION['ads_account_id'])) {
            return ['success'=>true,'already_authenticated'=>true,'redirect'=>'/ads/dashboard'];
        }

        $email = AdsValidator::email($email);
        if (!$email) return ['success'=>false,'message'=>'Credenciais inválidas.'];
        $account = $this->model->findByEmail($email);
        if (!$account || !password_verify($password, $account['password_hash'])) {
            return ['success'=>false,'message'=>'Credenciais inválidas.'];
        }
        $_SESSION['ads_account_id'] = (int)$account['id'];
        $this->model->touchLogin((int)$account['id']);
        return ['success'=>true,'redirect'=>'/ads/dashboard'];
    }

    public function linkCurrentToPipocine(): array
    {
        if (empty($_SESSION['ads_account_id']) || empty($_SESSION['user_id'])) {
            return ['success'=>false,'message'=>'Vínculo indisponível.'];
        }
        $this->model->linkPipocineUser((int)$_SESSION['ads_account_id'], (int)$_SESSION['user_id']);
        return ['success'=>true,'redirect'=>'/ads/dashboard'];
    }

    public function completeOnboarding(array $data): array
    {
        if (empty($_SESSION['ads_account_id'])) {
            return ['success'=>false,'message'=>'Sessão comercial expirada.'];
        }

        $account = $this->model->findById((int) $_SESSION['ads_account_id']);
        if (!$account) {
            return ['success'=>false,'message'=>'Conta comercial não encontrada.'];
        }
        if (!empty($account['onboarding_completed_at'])) {
            return ['success'=>false,'message'=>'Onboarding já concluído.','redirect'=>'/ads/dashboard'];
        }

        $logoUrl = AdsValidator::logoUrl((string)($data['logo_url'] ?? ''));
        $websiteUrl = AdsValidator::website($data['website_url'] ?? null);
        $contactName = AdsValidator::contactName((string)($data['contact_name'] ?? ''));
        $phone = AdsValidator::phone((string)($data['phone'] ?? ''));
        $industry = AdsValidator::industry((string)($data['industry'] ?? ''));
        $companySize = AdsValidator::companySize((string)($data['company_size'] ?? ''));
        $description = AdsValidator::description($data['business_description'] ?? null);

        if (!$logoUrl) return ['success'=>false,'message'=>'Envie uma logo válida para sua marca.'];
        if ($websiteUrl === '') return ['success'=>false,'message'=>'Informe um site válido ou deixe o campo vazio.'];
        if (!$contactName) return ['success'=>false,'message'=>'Informe o nome do responsável.'];
        if (!$phone) return ['success'=>false,'message'=>'Informe um telefone válido com DDD.'];
        if (!$industry) return ['success'=>false,'message'=>'Selecione o segmento da empresa.'];
        if (!$companySize) return ['success'=>false,'message'=>'Selecione o porte da empresa.'];
        if ($description === '') return ['success'=>false,'message'=>'A descrição deve ter no máximo 280 caracteres.'];

        $this->model->completeOnboarding((int) $account['id'], [
            'logo_url' => $logoUrl,
            'website_url' => $websiteUrl,
            'contact_name' => $contactName,
            'phone_e164' => $phone,
            'industry' => $industry,
            'company_size' => $companySize,
            'business_description' => $description,
        ]);

        return ['success'=>true,'redirect'=>'/ads/dashboard'];
    }
}
