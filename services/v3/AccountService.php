<?php

require_once __DIR__ . '/../../models/v3/AccountModel.php';

/**
 * SERVICE: AccountService (v3)
 *
 * Orquestra a lógica de negócio relacionada à conta do utilizador.
 * Combina os dados do banco CineVEO com os perfis do PipoCine e
 * aplica formatações necessárias antes de entregar ao controller.
 */
class AccountService
{
    private AccountModel $model;

    public function __construct(AccountModel $model)
    {
        $this->model = $model;
    }

    /**
     * Retorna o resumo completo da conta do utilizador autenticado:
     *  - dados da conta CineVEO (foto, plano, nome, username, email)
     *  - perfis PipoCine vinculados
     *
     * @param  int        $userId   ID do utilizador na sessão ($_SESSION['user_id'])
     * @return array|null           Dados formatados ou null se o utilizador não existir
     */
    public function getAccountSummary(int $userId): ?array
    {
        $account = $this->model->getAccountById($userId);

        if (!$account) {
            return null;
        }

        $profiles = $this->model->getProfilesByUserId($userId);

        // Normaliza plan_type para exibição
        $planLabel  = $this->resolvePlanLabel($account['plan_type'] ?? 'free');
        $planActive = $this->isPlanActive($account['plan_expiration'] ?? null);

        return [
            'account' => [
                'id'              => (int) $account['id'],
                'full_name'       => $account['full_name'] ?? '',
                'username'        => $account['username']  ?? '',
                'email'           => $account['email']     ?? '',
                'profile_pic_url' => $account['profile_pic_url'] ?? '',
                'plan_type'       => $account['plan_type']  ?? 'free',
                'plan_label'      => $planLabel,
                'plan_active'     => $planActive,
                'plan_expiration' => $account['plan_expiration'],
                'role'            => $account['role'] ?? 'user',
            ],
            'profiles' => array_map([$this, 'formatProfile'], $profiles),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    // Privados
    // ─────────────────────────────────────────────────────────────────────

    private function resolvePlanLabel(string $planType): string
    {
        return match (strtolower($planType)) {
            'premium', 'pro'  => 'Premium',
            'family'          => 'Família',
            'student'         => 'Estudante',
            default           => 'Gratuito',
        };
    }

    private function isPlanActive(?string $expiration): bool
    {
        if (!$expiration) {
            return false;
        }
        return strtotime($expiration) > time();
    }

    private function formatProfile(array $profile): array
    {
        return [
            'id'            => (int) $profile['id'],
            'profile_name'  => $profile['profile_name']  ?? '',
            'username'      => $profile['username']       ?? '',
            'profile_image' => $profile['profile_image']  ?? '',
            'is_kids'       => (bool) ($profile['is_kids'] ?? false),
            'is_watching'   => (bool) ($profile['is_watching'] ?? false),
            'last_active_at'=> $profile['last_active_at'] ?? null,
        ];
    }
}
