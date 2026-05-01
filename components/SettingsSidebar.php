<?php
/**
 * COMPONENTE: SettingsSidebar
 *
 * Sidebar responsivo do painel de configurações PipoCine/CineVEO.
 * Inclui logo, links de navegação por seção e botão de sair.
 *
 * Uso: require_once __DIR__ . '/../components/SettingsSidebar.php';
 *      SettingsSidebar::render($activeSection);
 *
 * O parâmetro $activeSection corresponde ao valor data-section do link ativo.
 * Se omitido, usa o query param ?tab= da URL corrente.
 */
class SettingsSidebar
{
    /**
     * Renderiza o HTML completo do sidebar + overlay mobile.
     *
     * @param string|null $activeSection  Seção ativa (ex: 'minha-conta')
     */
    public static function render(?string $activeSection = null): void
    {
        if ($activeSection === null) {
            $activeSection = htmlspecialchars(
                $_GET['tab'] ?? 'minha-conta',
                ENT_QUOTES,
                'UTF-8'
            );
        }

        $nav = self::getNavItems();
        ?>
        <!-- SIDEBAR OVERLAY (mobile) -->
        <div class="sidebar-overlay" id="sidebar-overlay" aria-hidden="true"></div>

        <!-- SIDEBAR -->
        <aside class="settings-sidebar" id="settings-sidebar" role="navigation" aria-label="Menu de configurações">

            <!-- Logo PipoCine -->
            <a href="/home" class="sidebar-logo" title="Voltar à Home">
                <img
                    src="/assets/img/logo-pipocine.png"
                    alt="PipoCine"
                    class="sidebar-logo-img"
                    width="120"
                    height="auto"
                    loading="eager"
                    draggable="false"
                >
            </a>

            <!-- Navegação -->
            <nav class="sidebar-nav" aria-label="Seções das configurações">

                <?php foreach ($nav as $group): ?>
                    <?php if ($group['label']): ?>
                        <span class="sidebar-section-label"><?= htmlspecialchars($group['label']) ?></span>
                    <?php endif ?>

                    <?php foreach ($group['items'] as $item): ?>
                        <button
                            type="button"
                            class="sidebar-link<?= $activeSection === $item['section'] ? ' active' : '' ?>"
                            data-section="<?= htmlspecialchars($item['section']) ?>"
                            aria-current="<?= $activeSection === $item['section'] ? 'page' : 'false' ?>"
                        >
                            <?= $item['icon'] ?>
                            <span class="sidebar-link-label"><?= htmlspecialchars($item['label']) ?></span>
                            <?php if (!empty($item['badge'])): ?>
                                <span class="sidebar-badge"><?= htmlspecialchars($item['badge']) ?></span>
                            <?php endif ?>
                        </button>
                    <?php endforeach ?>
                <?php endforeach ?>

            </nav>

            <!-- Rodapé: Sair -->
            <div class="sidebar-footer">
                <a href="/logout" class="sidebar-link" title="Sair da conta">
                    <?= self::iconSvg('log-out') ?>
                    <span class="sidebar-link-label">Sair</span>
                </a>
            </div>

        </aside>
        <?php
    }

    // ──────────────────────────────────────────────────────────────────────
    // Estrutura de navegação
    // ──────────────────────────────────────────────────────────────────────

    private static function getNavItems(): array
    {
        return [
            [
                'label' => 'Conta',
                'items' => [
                    [
                        'section' => 'minha-conta',
                        'label'   => 'Minha Conta CineVEO',
                        'icon'    => self::iconSvg('user-circle'),
                        'badge'   => null,
                    ],
                ],
            ],
            [
                'label' => 'PipoCine',
                'items' => [
                    [
                        'section' => 'perfis',
                        'label'   => 'Gerenciar Perfis',
                        'icon'    => self::iconSvg('users'),
                        'badge'   => null,
                    ],
                    [
                        'section' => 'plano',
                        'label'   => 'Meu Plano',
                        'icon'    => self::iconSvg('star'),
                        'badge'   => null,
                    ],
                ],
            ],
            [
                'label' => 'Sistema',
                'items' => [
                    [
                        'section' => 'seguranca',
                        'label'   => 'Segurança',
                        'icon'    => self::iconSvg('shield'),
                        'badge'   => null,
                    ],
                    [
                        'section' => 'notificacoes',
                        'label'   => 'Notificações',
                        'icon'    => self::iconSvg('bell'),
                        'badge'   => null,
                    ],
                ],
            ],
        ];
    }

    // ──────────────────────────────────────────────────────────────────────
    // Ícones SVG inline (subset Lucide)
    // ──────────────────────────────────────────────────────────────────────

    private static function iconPopcorn(): string
    {
        // Ícone de pipoca customizado para a logo
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     aria-hidden="true">
                  <path d="M18 8a2 2 0 0 0 0-4 2 2 0 0 0-4 0 2 2 0 0 0-4 0 2 2 0 0 0-4 0 2 2 0 0 0 0 4"/>
                  <path d="M10 22 9 8h6l-1 14"/>
                  <path d="M11 14h2"/>
                </svg>';
    }

    /**
     * Retorna um ícone SVG Lucide inline a partir do nome do ícone.
     * Usamos paths pré-definidos para os ícones necessários.
     */
    private static function iconSvg(string $name): string
    {
        $paths = [
            'user-circle'  => '<circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>',
            'users'        => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
            'star'         => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
            'shield'       => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
            'bell'         => '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>',
            'log-out'      => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>',
            'menu'         => '<line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="18" x2="20" y2="18"/>',
            'settings'     => '<path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/>',
        ];

        $path = $paths[$name] ?? '';

        return '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
             . $path
             . '</svg>';
    }
}
