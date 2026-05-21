<?php

declare(strict_types=1);

namespace Services\V4;

use Helpers\V4\EvoPayClient;
use Models\V4\PlatformUserModel;
use Models\V4\SubscriptionModel;

class SubscriptionService
{
    private const RENEWAL_WINDOW_DAYS = 14;

    private SubscriptionModel $subscriptions;
    private PlatformUserModel $users;
    private EvoPayClient $evopay;

    public function __construct(SubscriptionModel $subscriptions, PlatformUserModel $users, EvoPayClient $evopay)
    {
        $this->subscriptions = $subscriptions;
        $this->users = $users;
        $this->evopay = $evopay;
        $this->subscriptions->ensureSchema();
        $this->subscriptions->expireOldSubscriptions();
    }

    public function state(int $userId): array
    {
        $active = $this->subscriptions->activeSubscription($userId);
        $familyBenefit = $this->subscriptions->activeFamilyBenefit($userId);

        return [
            'success' => true,
            'active' => (bool) $active || (bool) $familyBenefit,
            'real_active' => (bool) $active,
            'family_active' => (bool) $familyBenefit,
            'subscription' => $this->normalizeSubscription($active),
            'family_benefit' => $this->normalizeFamilyBenefit($familyBenefit),
            'history' => $this->subscriptions->paymentHistory($userId),
        ];
    }

    public function createCheckout(int $userId, array $input, string $baseUrl): array
    {
        $user = $this->users->findById($userId);
        if (!$user) {
            return ['success' => false, 'code' => 'USER_NOT_FOUND', 'message' => 'Usuario nao encontrado. Entre novamente.'];
        }

        $active = $this->subscriptions->activeSubscription($userId);
        if ($active && (($active['source'] ?? 'paid') === 'paid') && (($active['plan_code'] ?? '') !== 'casual')) {
            return ['success' => false, 'code' => 'SUBSCRIPTION_ACTIVE', 'message' => 'Sua assinatura ja esta ativa.'];
        }

        $planCode = (string) ($input['plan'] ?? 'gold');
        $plan = $this->subscriptions->plan($planCode);
        if (!$plan || $planCode !== 'gold') {
            return ['success' => false, 'code' => 'INVALID_PLAN', 'message' => 'Plano indisponivel para assinatura.'];
        }

        $accepted = !empty($input['accepted_terms']);
        if (!$accepted) {
            return ['success' => false, 'code' => 'TERMS_REQUIRED', 'message' => 'Confirme as politicas de privacidade e assinatura para continuar.'];
        }

        $pending = $this->subscriptions->pendingPayment($userId, $planCode);
        if ($pending && !empty($pending['qr_code'])) {
            return $this->checkoutResponse($pending, true);
        }

        $expiresAt = date('Y-m-d H:i:s', time() + 3600);
        $paymentId = $this->subscriptions->createPayment($userId, $planCode, (float) $plan['price'], $expiresAt, [
            'name' => trim((string) ($input['name'] ?? $user['full_name'] ?? '')),
            'email' => trim((string) ($input['email'] ?? $user['email'] ?? '')),
            'phone' => trim((string) ($input['phone'] ?? $user['phone'] ?? '')),
            'accepted_terms' => true,
        ]);

        $callbackUrl = rtrim($baseUrl, '/') . '/webhooks/evopay.php';
        $pix = $this->evopay->createPix((float) $plan['price'], $callbackUrl);

        $this->subscriptions->attachPix(
            $paymentId,
            (string) $pix['id'],
            (string) ($pix['qrCodeText'] ?? ''),
            (string) ($pix['qrCodeUrl'] ?? ''),
            $pix
        );
        $this->subscriptions->event($userId, $paymentId, null, 'payment_created', $pix);

        $payment = $this->subscriptions->paymentByIdForUser($paymentId, $userId);
        return $this->checkoutResponse($payment, false);
    }

    public function createRenewalCheckout(int $userId, array $input, string $baseUrl): array
    {
        $user = $this->users->findById($userId);
        if (!$user) {
            return ['success' => false, 'code' => 'USER_NOT_FOUND', 'message' => 'Usuario nao encontrado. Entre novamente.'];
        }

        $active = $this->subscriptions->activeSubscription($userId);
        if (!$active || (($active['source'] ?? 'paid') !== 'paid') || (($active['plan_code'] ?? '') !== 'gold')) {
            return ['success' => false, 'code' => 'RENEWAL_UNAVAILABLE', 'message' => 'Renovacao disponivel apenas para assinaturas Gold pagas e ativas.'];
        }

        $daysLeft = $this->daysUntil((string) ($active['expires_at'] ?? ''));
        if ($daysLeft === null || $daysLeft > self::RENEWAL_WINDOW_DAYS) {
            return [
                'success' => false,
                'code' => 'RENEWAL_TOO_EARLY',
                'message' => 'A renovacao por Pix fica disponivel nos ultimos ' . self::RENEWAL_WINDOW_DAYS . ' dias do plano.'
            ];
        }

        if (empty($input['accepted_terms'])) {
            return ['success' => false, 'code' => 'TERMS_REQUIRED', 'message' => 'Confirme a renovacao para gerar o Pix.'];
        }

        $plan = $this->subscriptions->plan('gold');
        if (!$plan) {
            return ['success' => false, 'code' => 'INVALID_PLAN', 'message' => 'Plano indisponivel para renovacao.'];
        }

        $pending = $this->subscriptions->pendingPayment($userId, 'gold');
        if ($pending && !empty($pending['qr_code'])) {
            return $this->checkoutResponse($pending, true);
        }

        $expiresAt = date('Y-m-d H:i:s', time() + 3600);
        $paymentId = $this->subscriptions->createPayment($userId, 'gold', (float) $plan['price'], $expiresAt, [
            'name' => trim((string) ($input['name'] ?? $user['full_name'] ?? '')),
            'email' => trim((string) ($input['email'] ?? $user['email'] ?? '')),
            'phone' => trim((string) ($input['phone'] ?? $user['phone'] ?? '')),
            'accepted_terms' => true,
            'purpose' => 'renewal',
            'renew_subscription_id' => (int) $active['id'],
            'current_expires_at' => (string) $active['expires_at'],
        ]);

        $callbackUrl = rtrim($baseUrl, '/') . '/webhooks/evopay.php';
        $pix = $this->evopay->createPix((float) $plan['price'], $callbackUrl);

        $this->subscriptions->attachPix(
            $paymentId,
            (string) $pix['id'],
            (string) ($pix['qrCodeText'] ?? ''),
            (string) ($pix['qrCodeUrl'] ?? ''),
            $pix
        );
        $this->subscriptions->event($userId, $paymentId, null, 'renewal_payment_created', $pix);

        $payment = $this->subscriptions->paymentByIdForUser($paymentId, $userId);
        return $this->checkoutResponse($payment, false);
    }

    public function paymentStatus(int $userId, int $paymentId, string $sessionHash): array
    {
        $payment = $this->subscriptions->paymentByIdForUser($paymentId, $userId);
        if (!$payment) {
            return ['success' => false, 'status' => 'not_found', 'message' => 'Pagamento nao encontrado.'];
        }

        if ($payment['status'] === 'pending' && !empty($payment['provider_txid'])) {
            try {
                $remote = $this->evopay->transaction((string) $payment['provider_txid']);
                if ($this->isPaidPayload($remote)) {
                    $this->subscriptions->markPaymentPaid((int) $payment['id'], $remote);
                    $this->subscriptions->event($userId, (int) $payment['id'], null, 'payment_paid_remote', $remote);
                    $payment = $this->subscriptions->paymentByIdForUser($paymentId, $userId);
                }
            } catch (\Throwable $e) {
                error_log('[Subscription] status poll failed: ' . $e->getMessage());
            }
        }

        if ($payment['status'] !== 'paid') {
            return [
                'success' => true,
                'status' => $payment['status'],
                'paid' => false,
                'message' => 'Aguardando pagamento Pix.'
            ];
        }

        $token = $this->issueActivationToken($userId, (int) $payment['id'], $sessionHash);

        return [
            'success' => true,
            'status' => 'paid',
            'paid' => true,
            'activation_url' => '/plan/payment?active=' . rawurlencode($token)
        ];
    }

    public function cancel(int $userId, int $paymentId): array
    {
        $ok = $this->subscriptions->cancelPayment($paymentId, $userId, 'Cancelado pelo usuario no checkout.');
        if ($ok) {
            $this->subscriptions->event($userId, $paymentId, null, 'payment_canceled_by_user');
        }

        return [
            'success' => $ok,
            'message' => $ok
                ? 'Pagamento cancelado. Este QR Code nao deve mais ser utilizado.'
                : 'Nao foi possivel cancelar. O pagamento pode ja ter sido confirmado ou expirado.'
        ];
    }

    public function activate(int $userId, string $rawToken, string $sessionHash): array
    {
        if (!preg_match('/^[a-f0-9]{64}$/i', $rawToken)) {
            return ['success' => false, 'message' => 'Token de ativacao invalido.'];
        }

        $token = $this->subscriptions->activationToken(hash('sha256', $rawToken));
        if (!$token || (int) $token['user_id'] !== $userId || !hash_equals((string) $token['session_hash'], $sessionHash)) {
            return ['success' => false, 'message' => 'Token expirado, ja utilizado ou nao pertence a esta sessao.'];
        }

        $payment = $this->subscriptions->paymentByIdForUser((int) $token['payment_id'], $userId);
        if (!$payment || $payment['status'] !== 'paid') {
            return ['success' => false, 'message' => 'Pagamento ainda nao confirmado.'];
        }

        $plan = $this->subscriptions->plan((string) $payment['plan_code']);
        if (!$plan) {
            return ['success' => false, 'message' => 'Plano nao encontrado.'];
        }

        $payload = json_decode((string) ($payment['checkout_payload'] ?? '{}'), true) ?: [];
        $isRenewal = (($payload['purpose'] ?? '') === 'renewal');
        $activeSubscription = $this->subscriptions->activeSubscription($userId);

        if ($activeSubscription && (($activeSubscription['source'] ?? 'paid') === 'paid') && (($activeSubscription['plan_code'] ?? '') !== 'casual') && !$isRenewal) {
            return ['success' => true, 'already_active' => true, 'redirect' => '/plan/me'];
        }

        if ($isRenewal) {
            $subscriptionId = $this->subscriptions->renewActiveSubscription(
                $userId,
                (string) $payment['plan_code'],
                (float) $payment['amount'],
                (int) $payment['id'],
                (int) $plan['duration_days']
            );
        } else {
            $subscriptionId = $this->subscriptions->activateSubscription(
                $userId,
                (string) $payment['plan_code'],
                (float) $payment['amount'],
                (int) $payment['id'],
                (int) $plan['duration_days']
            );
        }

        $this->subscriptions->consumeActivationToken((int) $token['id']);
        $this->subscriptions->event($userId, (int) $payment['id'], $subscriptionId, $isRenewal ? 'subscription_renewed' : 'subscription_activated');

        return ['success' => true, 'redirect' => '/plan/me'];
    }

    public function dashboard(int $userId): array
    {
        return [
            'active' => $this->normalizeSubscription($this->subscriptions->activeSubscription($userId)),
            'family_benefit' => $this->normalizeFamilyBenefit($this->subscriptions->activeFamilyBenefit($userId)),
            'payments' => $this->subscriptions->paymentHistory($userId),
            'subscriptions' => $this->subscriptions->subscriptionHistory($userId),
        ];
    }

    public function handleWebhook(array $payload): array
    {
        $txid = $this->extractTxid($payload);
        if ($txid === '') {
            return ['success' => false, 'message' => 'Webhook sem txid.'];
        }

        $payment = $this->subscriptions->paymentByTxid($txid);
        if (!$payment) {
            return ['success' => false, 'message' => 'Pagamento nao encontrado.'];
        }

        if ($this->isPaidPayload($payload)) {
            $this->subscriptions->markPaymentPaid((int) $payment['id'], $payload);
            $this->subscriptions->event((int) $payment['user_id'], (int) $payment['id'], null, 'payment_paid_webhook', $payload);
            return ['success' => true, 'status' => 'paid'];
        }

        $this->subscriptions->event((int) $payment['user_id'], (int) $payment['id'], null, 'webhook_ignored', $payload);
        return ['success' => true, 'status' => 'ignored'];
    }

    private function checkoutResponse(?array $payment, bool $existing): array
    {
        if (!$payment) {
            return ['success' => false, 'message' => 'Nao foi possivel recuperar o pagamento.'];
        }

        return [
            'success' => true,
            'existing' => $existing,
            'payment_id' => (int) $payment['id'],
            'txid' => $payment['provider_txid'],
            'amount' => (float) $payment['amount'],
            'qr_code' => $payment['qr_code'],
            'qr_code_image' => $payment['qr_code_image'],
            'expires_at' => $payment['expires_at'],
        ];
    }

    private function daysUntil(string $date): ?int
    {
        $timestamp = strtotime($date);
        if (!$timestamp) {
            return null;
        }

        return max(0, (int) ceil(($timestamp - time()) / 86400));
    }

    private function issueActivationToken(int $userId, int $paymentId, string $sessionHash): string
    {
        $token = bin2hex(random_bytes(32));
        $this->subscriptions->createActivationToken(
            $userId,
            $paymentId,
            hash('sha256', $token),
            $sessionHash,
            date('Y-m-d H:i:s', time() + 3600)
        );

        return $token;
    }

    private function normalizeSubscription(?array $subscription): ?array
    {
        if (!$subscription) {
            return null;
        }

        $subscription['benefits'] = json_decode((string) ($subscription['benefits_json'] ?? '[]'), true) ?: [];
        return $subscription;
    }

    private function normalizeFamilyBenefit(?array $benefit): ?array
    {
        if (!$benefit) {
            return null;
        }

        return [
            'membership_id' => (int) $benefit['membership_id'],
            'owner_user_id' => (int) $benefit['owner_user_id'],
            'owner_name' => (string) ($benefit['owner_name'] ?? 'Titular Pipocine'),
            'owner_email' => (string) ($benefit['owner_email'] ?? ''),
            'plan_code' => (string) ($benefit['plan_code'] ?? 'gold'),
            'plan_name' => 'Beneficio familiar',
            'source' => 'family_benefit',
            'badge' => 'Membro da familia',
            'accepted_at' => (string) ($benefit['accepted_at'] ?? ''),
            'expires_at' => '',
            'device_limit' => 1,
            'profile_limit' => 2,
            'benefits' => [
                'Sem anuncios',
                'Personalizacao de perfil',
                'Player completo',
                'Qualidade 2K',
            ],
        ];
    }

    private function isPaidPayload(array $payload): bool
    {
        $status = strtolower((string) ($payload['status'] ?? $payload['paymentStatus'] ?? $payload['state'] ?? ''));
        return in_array($status, ['paid', 'confirmed', 'approved', 'completed', 'success', 'received'], true)
            || !empty($payload['paidAt'])
            || !empty($payload['paid_at']);
    }

    private function extractTxid(array $payload): string
    {
        return (string) (
            $payload['id']
            ?? $payload['txid']
            ?? $payload['transactionId']
            ?? $payload['transaction_id']
            ?? ''
        );
    }
}
