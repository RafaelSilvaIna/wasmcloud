<?php
declare(strict_types=1);

namespace Helpers\Ads;

final class AdsDraftPresenter
{
    public static function editUrl(array $campaign): string
    {
        $token = rawurlencode((string) ($campaign['draft_token'] ?? ''));
        if (trim((string) ($campaign['creative_url'] ?? '')) === '') {
            return '/ads/anuncios/criar/upload?draft=' . $token;
        }
        return '/ads/anuncios/criar/detalhes?draft=' . $token;
    }

    public static function continueUrl(array $campaign): string
    {
        $token = rawurlencode((string) ($campaign['draft_token'] ?? ''));
        if (trim((string) ($campaign['creative_url'] ?? '')) === '') {
            return '/ads/anuncios/criar/upload?draft=' . $token;
        }
        if (trim((string) ($campaign['description'] ?? '')) === '') {
            return '/ads/anuncios/criar/detalhes?draft=' . $token;
        }
        return '/ads/anuncios/criar/revisao?draft=' . $token;
    }

    public static function progress(array $campaign): int
    {
        if (trim((string) ($campaign['creative_url'] ?? '')) === '') {
            return 25;
        }
        if (trim((string) ($campaign['description'] ?? '')) === '') {
            return 58;
        }
        return 84;
    }

    public static function stageLabel(array $campaign): string
    {
        if (trim((string) ($campaign['creative_url'] ?? '')) === '') {
            return 'Aguardando mídia';
        }
        if (trim((string) ($campaign['description'] ?? '')) === '') {
            return 'Faltam detalhes';
        }
        return 'Pronto para revisão';
    }
}
