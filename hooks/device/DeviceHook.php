<?php

declare(strict_types=1);

namespace Hooks\Device;

use PDO;
use Models\Device\DeviceModel;
use Helpers\Device\DeviceFingerprint;
use Services\Device\DeviceService;

/**
 * DeviceHook
 *
 * Intercepta todas as requisições de páginas front-end (não-API) após
 * autenticação e, para usuários logados e com perfil selecionado,
 * aplica o controle de dispositivos simultâneos.
 *
 * Rotas isentas (não sofrem verificação):
 *   - /select-profile
 *   - /pipocine/suporte  (qualquer URI contendo /suporte)
 *   - /settings
 *   - /plan  (e sub-rotas)
 *   - Rotas de API  (/api/*)
 *   - Assets estáticos (/assets/*)
 *   - Webhooks (/webhooks/*)
 *   - Páginas de erro (/error/*)
 *   - Login (/login, /verify)
 *
 * Comportamento para páginas protegidas:
 *   1. Verifica se o dispositivo atual já está registrado e ativo.
 *   2. Se sim → renova heartbeat e libera o acesso.
 *   3. Se não → conta os dispositivos ativos:
 *      a. Se há vaga → registra e libera.
 *      b. Se não há vaga → armazena flag `device_limit_exceeded` na
 *         sessão e deixa a página carregar. O DeviceLimitModal.php
 *         (incluído no componente) exibe o bloqueio visual em JS.
 *
 * Renderização global (app-wide) via Output Buffering:
 *   O hook registra um callback via ob_start() que intercepta toda a
 *   saída HTML da página e injeta automaticamente o DeviceLimitModal
 *   (modal + script de heartbeat) imediatamente antes do </body>.
 *   Isso elimina a necessidade de adicionar manualmente o componente
 *   em cada página — qualquer página roteada pelo routes/index.php
 *   recebe o componente automaticamente.
 *
 * O modal de bloqueio verifica periodicamente via `/api/devices/status`
 * se houve liberação de vaga e, nesse caso, remove automaticamente o
 * aviso sem necessidade de reload.
 */
final class DeviceHook
{
    /**
     * HTML pré-renderizado do DeviceLimitModal.
     * Preenchido em enforce() ANTES de ob_start() ser registrado,
     * evitando qualquer ob_start() aninhado dentro do callback.
     */
    private static string $componentHtml = '';
    // URIs completamente isentas de verificação
    private const EXEMPT_EXACT = [
        '/select-profile',
        '/settings',
        '/plan',
        '/plan/',
        '/plan/checkout',
        '/plan/pix',
        '/plan/payment',
        '/plan/me',
        '/login',
        '/verify',
        '/',
        '/main',
        '/manage-profiles',
        '/create/profile',
        '/d2xs8d3sdfsegequ6249f',
    ];

    // Prefixos isentos
    private const EXEMPT_PREFIXES = [
        '/api/',
        '/assets/',
        '/webhooks/',
        '/error/',
        '/login/',
        '/suporte',
        '/pipocine/suporte',
        '/plan/payment/active=',
        '/create/profile/edit=',
    ];

    public static function enforce(PDO $pdo): void
    {
        // Só age em usuários autenticados
        if (empty($_SESSION['user_id'])) {
            return;
        }

        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';

        // ── Release automático ao voltar para /select-profile ─────────────
        // Quando o usuário navega de volta ao seletor de perfis (ex: clicou
        // em "Mudar Perfil"), o slot do dispositivo deve ser liberado
        // imediatamente, sem esperar o TTL expirar. Isso impede o falso
        // "Perfil em Uso" quando outro dispositivo tenta o mesmo perfil logo
        // em seguida.
        if ($uri === '/select-profile' && !empty($_SESSION['user_id'])) {
            self::releaseIfActive($pdo, (int) $_SESSION['user_id']);
        }

        // Rotas de API e isentas não recebem verificação nem output buffer
        if (self::isExempt($uri)) {
            return;
        }

        $userId = (int) $_SESSION['user_id'];

        // Determina se o usuário tem plano Gold ou cortesia
        $isGold = self::resolveIsGold($pdo, $userId);

        // Persiste status na sessão para evitar nova consulta no heartbeat
        $_SESSION['device_plan_gold'] = $isGold;

        // Carrega dependências
        require_once __DIR__ . '/../../models/device/DeviceModel.php';
        require_once __DIR__ . '/../../helpers/device/DeviceFingerprint.php';
        require_once __DIR__ . '/../../services/device/DeviceService.php';

        $service = new DeviceService(new DeviceModel($pdo));
        $result  = $service->heartbeat($userId, $isGold);

        if ($result['allowed']) {
            // Acesso liberado — remove qualquer bloqueio anterior
            unset($_SESSION['device_limit_exceeded']);
        } else {
            // Limite excedido — a página carrega mas o modal é exibido
            $_SESSION['device_limit_exceeded'] = [
                'active' => $result['active'],
                'limit'  => $result['limit'],
                'is_gold'=> $isGold,
            ];
        }

        // ── Injeção global via Output Buffering ───────────────────────────
        // Pré-renderiza o componente AGORA (fora de qualquer callback),
        // armazena na propriedade estática e só então registra o ob_start.
        // Isso evita ob_start() aninhado dentro do callback, que é proibido
        // pelo PHP e causaria Fatal Error.
        ob_start();
        require __DIR__ . '/../../components/device/DeviceLimitModal.php';
        self::$componentHtml = (string) ob_get_clean();

        ob_start([self::class, 'injectDeviceComponent']);
    }

    /**
     * Callback do ob_start: injeta o DeviceLimitModal antes de </body>.
     *
     * É chamado automaticamente pelo PHP ao final da execução da página
     * (ou em qualquer ob_flush/ob_end_flush). Só injeta quando a resposta
     * é HTML (contém </body>) e não é um fragmento de API.
     */
    public static function injectDeviceComponent(string $html): string
    {
        // Só injeta em respostas HTML completas que tenham </body>
        $closingBodyPos = strripos($html, '</body>');
        if ($closingBodyPos === false) {
            return $html;
        }

        // Usa o HTML pré-renderizado em enforce() — sem ob_start() aninhado
        return substr($html, 0, $closingBodyPos)
            . self::$componentHtml
            . substr($html, $closingBodyPos);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Verificação de isenção
    // ─────────────────────────────────────────────────────────────────────────

    private static function isExempt(string $uri): bool
    {
        if (in_array($uri, self::EXEMPT_EXACT, true)) {
            return true;
        }

        foreach (self::EXEMPT_PREFIXES as $prefix) {
            if (str_starts_with($uri, $prefix)) {
                return true;
            }
        }

        // Rotas dinâmicas de verificação (ex: /verify=<token>)
        if (preg_match('/^\/verify=/', $uri)) {
            return true;
        }

        return false;
    }

    // ─────────────────────────────────────────────────────────────────────��───
    // Release explícito de slot
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Libera o slot do dispositivo atual, se ele estiver ativo.
     * Chamado ao navegar para /select-profile para garantir que a vaga
     * seja imediatamente disponibilizada a outro dispositivo, sem
     * depender do TTL do heartbeat.
     * É idempotente: não gera erro se o dispositivo não estiver ativo.
     */
    private static function releaseIfActive(PDO $pdo, int $userId): void
    {
        try {
            require_once __DIR__ . '/../../models/device/DeviceModel.php';
            require_once __DIR__ . '/../../helpers/device/DeviceFingerprint.php';
            require_once __DIR__ . '/../../services/device/DeviceService.php';

            // Novo sistema: libera slot em account_devices
            $service = new DeviceService(new DeviceModel($pdo));
            $service->release($userId);

            // Sistema legado: desativa sessão em profile_active_sessions
            // para o perfil atualmente selecionado, se houver.
            // Isso elimina o falso "Perfil em Uso" quando outro dispositivo
            // tenta selecionar o mesmo perfil logo em seguida.
            $profileId = isset($_SESSION['profile_id']) ? (int) $_SESSION['profile_id'] : 0;
            if ($profileId > 0) {
                try {
                    $stmt = $pdo->prepare("
                        UPDATE profile_active_sessions
                           SET is_active = 0
                         WHERE profile_id = ?
                           AND session_id = ?
                    ");
                    $stmt->execute([$profileId, session_id()]);
                } catch (\Throwable) {}
            }

            // Limpa flags de bloqueio residuais na sessão PHP
            unset($_SESSION['device_limit_exceeded']);
        } catch (\Throwable) {
            // Silencioso — nunca deve bloquear a navegação
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Resolução do plano
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Verifica se o usuário possui assinatura ativa (paga ou cortesia).
     * Prioriza banco Pipocine, com fallback para Cineveo.
     */
    private static function resolveIsGold(PDO $pdo, int $userId): bool
    {
        try {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) AS cnt
                FROM user_subscriptions
                WHERE user_id = ?
                  AND status = 'active'
                  AND expires_at > NOW()
                LIMIT 1
            ");
            $stmt->execute([$userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int) ($row['cnt'] ?? 0) > 0;
        } catch (\Throwable) {
            return false;
        }
    }
}
