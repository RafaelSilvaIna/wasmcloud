<?php
/**
 * API: /api/v2/episode-url
 * Retorna a URL de vídeo de um episódio ou filme diretamente do banco cineveo.
 *
 * Parâmetros GET:
 *   id     (int)    — id_tmdb do conteúdo
 *   type   (string) — "serie" ou "filme"
 *   s      (int)    — temporada (apenas séries)
 *   e      (int)    — episódio  (apenas séries)
 *   audio  (string) — "dub" (padrão) ou "leg"
 */

declare(strict_types=1);

// Sem buffer, resposta rápida
while (ob_get_level()) ob_end_clean();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

// Carrega conexões do banco — usamos $pdoCineveo (banco cineveo)
require_once __DIR__ . '/../../database/db.php';
require_once __DIR__ . '/../../services/cdn/CdnPlaybackService.php';

// ─── Validação de entrada ──────────────────────────────────────────────────────
$id    = (int)   ($_GET['id']    ?? 0);
$type  = strtolower(trim($_GET['type']  ?? 'filme'));
$s     = (int)   ($_GET['s']     ?? 1);
$e     = (int)   ($_GET['e']     ?? 1);
$audio = strtolower(trim($_GET['audio'] ?? 'dub'));

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID inválido.']);
    exit;
}

if (!$pdoCineveo) {
    http_response_code(503);
    echo json_encode(['success' => false, 'error' => 'Banco de dados indisponível.']);
    exit;
}

$isSerie = in_array($type, ['serie', 'series', 'tv'], true);

// ─── Sanitiza URL bruta do banco ──────────────────────────────────────────────
function sanitizeVideoUrl(string $url): string
{
    $url = trim($url);
    $url = preg_replace('/\s+exist$/i', '', $url);
    $url = preg_replace('/\.mp4\.mp4$/i', '.mp4', $url);
    return trim($url);
}

// ─── Detecta tipo de mídia pela extensão/path ─────────────────────────────────
function detectMediaType(string $url): string
{
    $path = strtolower(parse_url($url, PHP_URL_PATH) ?? $url);
    if (str_contains($path, '.m3u8')) return 'm3u8';
    if (str_contains($path, '.mp4'))  return 'mp4';
    if (str_contains($path, '.mkv'))  return 'mkv';
    if (str_contains($path, '.webm')) return 'webm';
    return 'auto';
}

// ─── Resolve redirect de CDN (ex: hubby.cx → URL final do servidor) ──────────
// Usa GET com Range:bytes=0-0 para seguir o redirect sem baixar o corpo.
// CDNs bloqueiam HEAD (403/405) mas aceitam GET parcial — assim pegamos
// a CURLINFO_EFFECTIVE_URL com custo mínimo (~0 bytes transferidos).
// Retorna a URL final resolvida, ou a URL original em caso de falha de rede.
function resolveRedirect(string $url): string
{
    $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');

    // Apenas resolve se for host de redirect conhecido
    $redirectHosts = ['hubby.cx', 'hub.cx'];
    $needsResolve  = false;
    foreach ($redirectHosts as $rh) {
        if ($host === $rh || str_ends_with($host, '.' . $rh)) {
            $needsResolve = true;
            break;
        }
    }

    if (!$needsResolve) {
        return $url;
    }

    $originHost = (parse_url($url, PHP_URL_SCHEME) ?? 'https') . '://' . $host;

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_CONNECTTIMEOUT => 6,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
        CURLOPT_HTTPHEADER     => [
            'Range: bytes=0-0',          // Solicita apenas 1 byte — só queremos a URL final
            'Referer: ' . $originHost . '/',
            'Origin: ' . $originHost,
            'Accept: video/mp4,video/*;q=0.9,*/*;q=0.8',
        ],
    ]);
    curl_exec($ch);
    $finalUrl  = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $curlError = curl_errno($ch);
    curl_close($ch);

    // Se falhou na rede, devolve a URL original (o player tentará direto)
    if ($curlError || empty($finalUrl)) {
        return $url;
    }

    return $finalUrl;
}

// ─── Busca da URL de vídeo ────────────────────────────────────────────────────
$videoUrl = '';
$audioUsed = $audio;

try {
    if ($audio === 'dub') {
        // Dublado: tabela `links` do banco cineveo
        if ($isSerie) {
            $stmt = $pdoCineveo->prepare(
                "SELECT url_video FROM links
                 WHERE id_tmdb = ? AND temporada = ? AND episodio = ?
                   AND tipo_conteudo IN ('serie','series','tv')
                   AND url_video IS NOT NULL AND url_video != ''
                 ORDER BY id DESC LIMIT 1"
            );
            $stmt->execute([$id, $s, $e]);
        } else {
            $stmt = $pdoCineveo->prepare(
                "SELECT url_video FROM links
                 WHERE id_tmdb = ?
                   AND tipo_conteudo = 'filme'
                   AND url_video IS NOT NULL AND url_video != ''
                 ORDER BY id DESC LIMIT 1"
            );
            $stmt->execute([$id]);
        }
        $videoUrl = (string) ($stmt->fetchColumn() ?: '');
    }

    // Legendado: tabela `links_legendados` do banco cineveo
    if ($audio === 'leg' || ($audio === 'dub' && $videoUrl === '')) {
        if ($isSerie) {
            $stmt = $pdoCineveo->prepare(
                "SELECT url_video FROM links_legendados
                 WHERE id_tmdb = ? AND temporada = ? AND episodio = ?
                   AND url_video IS NOT NULL AND url_video != ''
                 ORDER BY id DESC LIMIT 1"
            );
            $stmt->execute([$id, $s, $e]);
        } else {
            $stmt = $pdoCineveo->prepare(
                "SELECT url_video FROM links_legendados
                 WHERE id_tmdb = ?
                   AND url_video IS NOT NULL AND url_video != ''
                 ORDER BY id DESC LIMIT 1"
            );
            $stmt->execute([$id]);
        }
        $row = (string) ($stmt->fetchColumn() ?: '');
        if ($row !== '') {
            $videoUrl  = $row;
            $audioUsed = 'leg';
        }
    }
} catch (Throwable $ex) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro interno ao buscar vídeo.']);
    exit;
}

if ($videoUrl === '') {
    echo json_encode([
        'success' => false,
        'error'   => 'Vídeo indisponível',
        'message' => $isSerie
            ? "O episódio S{$s}E{$e} ainda não possui link de vídeo disponível."
            : 'Este filme ainda não possui link de vídeo disponível.',
    ]);
    exit;
}

$videoUrl = sanitizeVideoUrl($videoUrl);

// Resolve redirect de CDN (ex: hubby.cx → URL final real do servidor de mídia)
// Não valida acessibilidade — CDNs bloqueiam HEAD/validações mas servem GET normalmente
$videoUrl = resolveRedirect($videoUrl);

// ─── Próximo episódio (apenas séries) ─────────────────────────────────────────
$nextEpisode = null;
if ($isSerie) {
    try {
        // Próximo episódio na mesma temporada
        $stmtNext = $pdoCineveo->prepare(
            "SELECT l.episodio, l.temporada FROM links l
             WHERE l.id_tmdb = ? AND l.temporada = ? AND l.episodio > ?
               AND l.url_video IS NOT NULL AND l.url_video != ''
             ORDER BY l.episodio ASC LIMIT 1"
        );
        $stmtNext->execute([$id, $s, $e]);
        $next = $stmtNext->fetch(\PDO::FETCH_ASSOC);

        // Fallback: primeiro episódio da próxima temporada
        if (!$next) {
            $stmtNext = $pdoCineveo->prepare(
                "SELECT l.episodio, l.temporada FROM links l
                 WHERE l.id_tmdb = ? AND l.temporada > ?
                   AND l.url_video IS NOT NULL AND l.url_video != ''
                 ORDER BY l.temporada ASC, l.episodio ASC LIMIT 1"
            );
            $stmtNext->execute([$id, $s]);
            $next = $stmtNext->fetch(\PDO::FETCH_ASSOC);
        }

        if ($next) {
            $nextEpisode = [
                'temporada' => (int) $next['temporada'],
                'episodio'  => (int) $next['episodio'],
            ];
        }
    } catch (Throwable $ex) {
        // silêncio — próximo episódio é opcional
    }
}

// ─── Metadados do episódio atual (título + imagem thumbnail) ──────────────────
$meta = [];
if ($isSerie) {
    try {
        $stmtMeta = $pdoCineveo->prepare(
            "SELECT nome, imagem, sinopse FROM episodios
             WHERE id_tmdb = ? AND temporada = ? AND episodio = ? LIMIT 1"
        );
        $stmtMeta->execute([$id, $s, $e]);
        $row = $stmtMeta->fetch(\PDO::FETCH_ASSOC);
        if ($row) {
            $img = $row['imagem'] ?? '';
            if ($img && strpos($img, 'http') !== 0) {
                $img = 'https://image.tmdb.org/t/p/w300' . $img;
            }
            $meta = [
                'nome'    => $row['nome'] ?? "Episódio {$e}",
                'imagem'  => $img,
                'sinopse' => $row['sinopse'] ?? '',
            ];
        }
    } catch (Throwable $ex) {}
}

// ─── Resposta final ────────────────────────────────────────────────────────────
$cdnInternal = [];
try {
    $cdnInternal = (new \Services\Cdn\CdnPlaybackService())->buildUrls(
        $id,
        $isSerie ? 'serie' : 'filme',
        $s,
        $e,
        $audioUsed,
        $videoUrl
    );
} catch (Throwable $ex) {
    $cdnInternal = [
        'enabled' => false,
        'error' => 'Falha ao preparar CDN interna. Usando fonte original.',
    ];
}

$publicVideoUrl = !empty($cdnInternal['enabled']) ? null : $videoUrl;

echo json_encode(array_merge([
    'success'      => true,
    'url'          => $publicVideoUrl,
    'media_type'   => detectMediaType($videoUrl),
    'audio'        => $audioUsed,
    'next_episode' => $nextEpisode,
    'meta'         => $meta,
], [
    'cdn_internal' => $cdnInternal,
]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
