<?php
declare(strict_types=1);

namespace Services\Ads;

use Helpers\Ads\AdsCampaignValidator;
use Helpers\Ads\AdsStatusPresenter;
use Models\Ads\AdsAccountModel;
use Models\Ads\AdsCampaignModel;
use PDO;

final class AdsCampaignService
{
    private const IMGBB_KEY = '538999ea6353b2b12c58af1f65f3cd8c';
    private const MAX_IMAGE_SIZE = 10 * 1024 * 1024;
    private const IMAGE_MIME = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private const VIDEO_MP4_WAIT_SECONDS = 90;
    private const VIDEO_MP4_POLL_INTERVAL_MICROSECONDS = 2_000_000;

    public function __construct(
        private readonly PDO $pdo,
        private readonly AdsCampaignModel $campaigns,
        private readonly AdsAccountModel $accounts,
        private readonly VidsStClient $vids
    ) {}

    public function list(int $accountId): array
    {
        return $this->campaigns->listByAccount($accountId);
    }

    public function statusBoard(int $accountId): array
    {
        $campaigns = $this->campaigns->listByAccount($accountId);
        $events = $this->campaigns->publicEventsByAccount($accountId);
        $eventsByCampaign = [];
        foreach ($events as $event) {
            $eventsByCampaign[(int) $event['campaign_id']][] = $event;
        }

        $counts = [];
        foreach ($campaigns as &$campaign) {
            $status = (string) ($campaign['status'] ?? 'draft');
            $counts[$status] = ($counts[$status] ?? 0) + 1;
            $campaign['label'] = AdsStatusPresenter::label($status);
            $campaign['tone'] = AdsStatusPresenter::tone($status);
            $campaign['journey'] = AdsStatusPresenter::journey($status);
            $campaign['events'] = $eventsByCampaign[(int) $campaign['id']] ?? [];
        }
        unset($campaign);

        return [
            'success' => true,
            'campaigns' => $campaigns,
            'counts' => $counts,
            'revision' => $this->campaigns->latestEventIdByAccount($accountId),
            'server_time' => date('Y-m-d H:i:s'),
        ];
    }

    public function createDraft(int $accountId, string $creativeType): array
    {
        $creativeType = AdsCampaignValidator::creativeType($creativeType);
        if (!$creativeType) {
            return ['success' => false, 'message' => 'Selecione um formato de anúncio válido.'];
        }

        $draft = $this->campaigns->createDraft($accountId, $creativeType);
        return [
            'success' => true,
            'draft_token' => $draft['draft_token'] ?? null,
            'redirect' => '/ads/anuncios/criar/upload?draft=' . urlencode((string) ($draft['draft_token'] ?? '')),
        ];
    }

    public function deleteDraft(int $accountId, string $draftToken): array
    {
        $campaign = $this->resolveDraft($accountId, $draftToken);
        if (!$campaign) {
            return ['success' => false, 'message' => 'Rascunho nÃ£o encontrado.'];
        }

        if (($campaign['media_provider'] ?? '') === 'vids_st'
            && trim((string) ($campaign['media_provider_file_id'] ?? '')) !== '') {
            try {
                $this->vids->deleteFile((string) $campaign['media_provider_file_id']);
            } catch (\Throwable) {
                // A exclusÃ£o remota Ã© best-effort; o rascunho local nÃ£o deve ficar preso por isso.
            }
        }

        $deleted = $this->campaigns->deleteDraft($accountId, $draftToken);
        return [
            'success' => $deleted,
            'message' => $deleted ? 'Rascunho excluÃ­do.' : 'NÃ£o foi possÃ­vel excluir o rascunho.',
        ];
    }

    public function uploadImage(int $accountId, string $draftToken, array $file): array
    {
        $campaign = $this->resolveDraft($accountId, $draftToken, 'image');
        if (!$campaign) {
            return ['success' => false, 'message' => 'Rascunho de imagem não encontrado.'];
        }
        if (trim((string) ($campaign['creative_url'] ?? '')) !== '') {
            return ['success' => false, 'message' => 'A mídia deste anúncio já foi enviada.'];
        }
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Selecione uma imagem válida.'];
        }
        if ((int) ($file['size'] ?? 0) > self::MAX_IMAGE_SIZE) {
            return ['success' => false, 'message' => 'A imagem deve ter no máximo 10 MB.'];
        }

        $mime = $this->detectMime((string) ($file['tmp_name'] ?? ''));
        if (!in_array($mime, self::IMAGE_MIME, true)) {
            return ['success' => false, 'message' => 'Use JPG, PNG, GIF ou WEBP.'];
        }

        try {
            $url = $this->uploadToImgBb((string) $file['tmp_name']);
            $this->campaigns->updateMedia((int) $campaign['id'], [
                'creative_url' => $url,
                'creative_duration_seconds' => null,
                'creative_mime_type' => $mime,
                'media_provider' => 'imgbb',
                'media_provider_file_id' => null,
                'original_filename' => basename((string) ($file['name'] ?? 'imagem')),
                'file_size_bytes' => (int) ($file['size'] ?? 0),
                'cdn_token' => bin2hex(random_bytes(32)),
            ]);
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        $fresh = $this->campaigns->findByDraftToken($accountId, $draftToken);
        return [
            'success' => true,
            'cdn_url' => '/cdn/ads=' . ($fresh['cdn_token'] ?? ''),
            'redirect' => '/ads/anuncios/criar/detalhes?draft=' . urlencode($draftToken),
        ];
    }

    public function prepareVideoUpload(int $accountId, string $draftToken): array
    {
        $campaign = $this->resolveDraft($accountId, $draftToken, 'video');
        if (!$campaign) {
            return ['success' => false, 'message' => 'Rascunho de vídeo não encontrado.'];
        }
        if (trim((string) ($campaign['creative_url'] ?? '')) !== '') {
            return ['success' => false, 'message' => 'A mídia deste anúncio já foi enviada.'];
        }

        try {
            $tokenResponse = $this->vids->uploadToken();
            $serverResponse = $this->vids->uploadServer();
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        $token = $this->findFirstValueByKeys($tokenResponse, ['token', 'upload_token']);
        if (!$token) {
            return ['success' => false, 'message' => 'O serviço de vídeo não retornou um token de upload.'];
        }

        $chunkSize = (int) ($this->findFirstValueByKeys($serverResponse, ['chunk_size', 'chunk_limit', 'max_chunk_size']) ?? 5 * 1024 * 1024);
        if ($chunkSize <= 0) {
            $chunkSize = 5 * 1024 * 1024;
        }

        $uploadUrl = $this->findFirstValueByKeys($serverResponse, ['upload_url', 'url', 'server']);
        if (!is_string($uploadUrl) || !filter_var($uploadUrl, FILTER_VALIDATE_URL)) {
            $uploadUrl = 'https://vids.st/ajax/upload_chunk.php';
        }

        return [
            'success' => true,
            'token' => $token,
            'upload_url' => $uploadUrl,
            'chunk_size_bytes' => min(max($chunkSize, 1024 * 1024), 10 * 1024 * 1024),
        ];
    }

    public function completeVideoUpload(int $accountId, string $draftToken, array $data): array
    {
        $campaign = $this->resolveDraft($accountId, $draftToken, 'video');
        if (!$campaign) {
            return ['success' => false, 'message' => 'Rascunho de v?deo n?o encontrado.'];
        }
        if (trim((string) ($campaign['creative_url'] ?? '')) !== '') {
            return ['success' => false, 'message' => 'A m?dia deste an?ncio j? foi enviada.'];
        }

        $token = trim((string) ($data['token'] ?? ''));
        if ($token === '') {
            return ['success' => false, 'message' => 'Token de upload inv?lido.'];
        }

        try {
            @set_time_limit(self::VIDEO_MP4_WAIT_SECONDS + 20);
            $complete = $this->vids->completeUpload($token);
            $processed = $this->waitForProcessedVideo($token, $complete);
            $metadata = $processed['metadata'];
            $fileId = $processed['file_id'];
            $mp4Url = $processed['mp4_url'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        if (!$mp4Url) {
            return [
                'success' => false,
                'processing' => true,
                'message' => 'O v?deo foi enviado, mas o provedor ainda n?o liberou o MP4 ap?s a janela de processamento. Tente concluir novamente em instantes.',
            ];
        }

        $duration = $this->extractDurationSeconds($metadata);
        if ($duration === null) {
            $hint = (float) ($data['duration_seconds'] ?? 0);
            $duration = $hint > 0 ? (int) ceil($hint) : null;
        }

        $this->campaigns->updateMedia((int) $campaign['id'], [
            'creative_url' => $mp4Url,
            'creative_duration_seconds' => $duration,
            'creative_mime_type' => 'video/mp4',
            'media_provider' => 'vids_st',
            'media_provider_file_id' => $fileId !== '' ? $fileId : null,
            'original_filename' => basename((string) ($data['original_filename'] ?? 'video.mp4')),
            'file_size_bytes' => max(0, (int) ($data['file_size_bytes'] ?? 0)),
            'cdn_token' => bin2hex(random_bytes(32)),
        ]);

        $fresh = $this->campaigns->findByDraftToken($accountId, $draftToken);
        return [
            'success' => true,
            'duration_seconds' => $fresh['creative_duration_seconds'] ?? null,
            'cdn_url' => '/cdn/ads=' . ($fresh['cdn_token'] ?? ''),
            'redirect' => '/ads/anuncios/criar/detalhes?draft=' . urlencode($draftToken),
        ];
    }

    public function saveDetails(int $accountId, string $draftToken, array $data): array
    {
        $campaign = $this->resolveDraft($accountId, $draftToken);
        if (!$campaign || trim((string) ($campaign['creative_url'] ?? '')) === '') {
            return ['success' => false, 'message' => 'Envie a mídia antes de continuar.'];
        }

        $description = AdsCampaignValidator::description((string) ($data['description'] ?? ''));
        $redirectUrl = AdsCampaignValidator::redirectUrl($data['redirect_url'] ?? null);
        $canSkip = AdsCampaignValidator::canSkip($data['can_skip'] ?? true);

        if (!$description) {
            return ['success' => false, 'message' => 'A descrição deve ter entre 8 e 500 caracteres.'];
        }
        if ($redirectUrl === '') {
            return ['success' => false, 'message' => 'Informe um link válido com http ou https.'];
        }
        if (($campaign['creative_type'] ?? '') === 'video'
            && !$canSkip
            && (int) ($campaign['creative_duration_seconds'] ?? 0) > 20) {
            return ['success' => false, 'message' => 'Vídeos acima de 20 segundos não podem ser obrigatórios.'];
        }

        $this->campaigns->updateDetails(
            (int) $campaign['id'],
            AdsCampaignValidator::campaignNameFromDescription($description),
            $description,
            $redirectUrl,
            $canSkip
        );

        return [
            'success' => true,
            'redirect' => '/ads/anuncios/criar/revisao?draft=' . urlencode($draftToken),
        ];
    }

    public function submit(int $accountId, string $draftToken): array
    {
        try {
            $this->pdo->beginTransaction();
            $account = $this->accounts->findByIdForUpdate($accountId);
            $campaign = $this->campaigns->findByDraftTokenForUpdate($accountId, $draftToken);

            if (!$account || !$campaign) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'Rascunho não encontrado.'];
            }
            if (($campaign['status'] ?? '') !== 'draft') {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'Este anúncio já foi enviado.'];
            }
            if (trim((string) ($campaign['creative_url'] ?? '')) === '' || trim((string) ($campaign['description'] ?? '')) === '') {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'Conclua todas as etapas antes de enviar.'];
            }

            $isDemo = empty($account['first_ad_demo_claimed_at']);
            $status = $isDemo ? 'pending_review' : 'awaiting_payment';
            $priceCents = $isDemo ? 0 : 1000;

            $this->campaigns->markSubmitted((int) $campaign['id'], $status, $priceCents, $isDemo);
            if ($isDemo) {
                $this->accounts->claimFirstAdDemo((int) $account['id']);
            }
            $this->campaigns->addDetailedStatusEvent(
                (int) $campaign['id'],
                'draft',
                $status,
                $isDemo
                    ? 'Primeira campanha enviada com demonstração gratuita.'
                    : 'Campanha enviada e aguardando pagamento.',
                'advertiser',
                null,
                $isDemo
                    ? 'Campanha enviada para a fila de revisão.'
                    : 'Campanha enviada e aguardando pagamento.'
            );

            $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['success' => false, 'message' => 'Não foi possível enviar o anúncio agora.'];
        }

        return [
            'success' => true,
            'is_demo' => $isDemo,
            'status' => $status,
            'redirect' => '/ads/anuncios/status?submitted=1',
        ];
    }

    private function resolveDraft(int $accountId, string $draftToken, ?string $expectedType = null): ?array
    {
        $draftToken = AdsCampaignValidator::draftToken($draftToken);
        if (!$draftToken) {
            return null;
        }
        $campaign = $this->campaigns->findByDraftToken($accountId, $draftToken);
        if (!$campaign || ($campaign['status'] ?? '') !== 'draft') {
            return null;
        }
        if ($expectedType !== null && ($campaign['creative_type'] ?? '') !== $expectedType) {
            return null;
        }
        return $campaign;
    }

    private function detectMime(string $path): ?string
    {
        if (!is_file($path)) {
            return null;
        }
        if (function_exists('finfo_open')) {
            $fi = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($fi, $path);
            finfo_close($fi);
            return $mime ?: null;
        }
        $info = @getimagesize($path);
        return $info['mime'] ?? null;
    }

    private function uploadToImgBb(string $tmpName): string
    {
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('Extensão cURL indisponível para upload de imagem.');
        }
        $ch = curl_init('https://api.imgbb.com/1/upload?key=' . self::IMGBB_KEY);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => ['image' => curl_file_create($tmpName)],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 30,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = json_decode((string) $response, true);
        $url = $json['data']['display_url'] ?? $json['data']['url'] ?? null;
        if ($httpCode < 200 || $httpCode >= 300 || empty($json['success']) || !$url) {
            throw new \RuntimeException('Não foi possível enviar a imagem para o ImgBB.');
        }
        return (string) $url;
    }

    private function findFirstValueByKeys(mixed $data, array $keys): mixed
    {
        if (!is_array($data)) {
            return null;
        }
        foreach ($keys as $key) {
            if (array_key_exists($key, $data) && $data[$key] !== null && $data[$key] !== '') {
                return $data[$key];
            }
        }
        foreach ($data as $value) {
            $found = $this->findFirstValueByKeys($value, $keys);
            if ($found !== null) {
                return $found;
            }
        }
        return null;
    }

    private function findFirstMp4Url(mixed $data): ?string
    {
        if (is_string($data) && filter_var($data, FILTER_VALIDATE_URL) && preg_match('#\.mp4(?:\?|$)#i', $data)) {
            return $data;
        }
        if (!is_array($data)) {
            return null;
        }
        foreach ($data as $value) {
            $url = $this->findFirstMp4Url($value);
            if ($url) {
                return $url;
            }
        }
        return null;
    }

    private function waitForProcessedVideo(string $token, array $initialMetadata): array
    {
        $deadline = microtime(true) + self::VIDEO_MP4_WAIT_SECONDS;
        $metadata = $initialMetadata;
        $fileId = (string) ($this->findFirstValueByKeys($metadata, ['file_id', 'video_id', 'id']) ?? '');
        $mp4Url = $this->findFirstMp4Url($metadata);

        while (!$mp4Url && microtime(true) < $deadline) {
            if ($fileId !== '') {
                try {
                    $metadata = array_replace_recursive($metadata, ['file_info' => $this->vids->fileInfo($fileId)]);
                } catch (\Throwable) {
                    // O provedor pode responder enquanto ainda est? preparando o arquivo.
                }
            } else {
                try {
                    $metadata = array_replace_recursive($metadata, ['upload_complete' => $this->vids->completeUpload($token)]);
                } catch (\Throwable) {
                    // Continua tentando at? expirar a janela de processamento.
                }
                $fileId = (string) ($this->findFirstValueByKeys($metadata, ['file_id', 'video_id', 'id']) ?? '');
            }

            $mp4Url = $this->findFirstMp4Url($metadata);
            if ($mp4Url) {
                break;
            }

            usleep(self::VIDEO_MP4_POLL_INTERVAL_MICROSECONDS);
        }

        return [
            'metadata' => $metadata,
            'file_id' => $fileId,
            'mp4_url' => $mp4Url,
        ];
    }

    private function extractDurationSeconds(mixed $data): ?int
    {
        $duration = $this->findFirstValueByKeys($data, ['duration_seconds', 'duration', 'length']);
        if ($duration === null || !is_numeric($duration)) {
            return null;
        }
        $seconds = (int) ceil((float) $duration);
        return $seconds > 0 ? $seconds : null;
    }
}
