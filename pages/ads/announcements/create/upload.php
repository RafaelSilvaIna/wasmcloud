<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../hooks/ads/AdsAuthHook.php';
require_once __DIR__ . '/../../../../hooks/ads/AdsDesktopOnlyHook.php';
\Hooks\Ads\AdsAuthHook::requireCommercialLogin();
require_once __DIR__ . '/../../../../components/ads/AdsDashboardShell.php';
require_once __DIR__ . '/../../../../components/ads/AdsDesktopOnlyNotice.php';
require_once __DIR__ . '/../../../../models/ads/AdsCampaignModel.php';

$account = $activeAdsAccount ?? [];
$mobile = \Hooks\Ads\AdsDesktopOnlyHook::isMobileRequest();
$draftToken = strtolower((string) ($_GET['draft'] ?? ''));
$campaign = (new \Models\Ads\AdsCampaignModel($pdo))->findByDraftToken((int) ($account['id'] ?? 0), $draftToken);
if (!$mobile && (!$campaign || ($campaign['status'] ?? '') !== 'draft')) {
    header('Location: /ads/anuncios/criar');
    exit;
}
$isVideo = ($campaign['creative_type'] ?? '') === 'video';
$alreadyUploaded = !empty($campaign['creative_url']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload de m?dia ? PipoCine Ads</title>
    <link rel="icon" type="image/png" href="/assets/img/ads/favicon.png">
    <?php AdsDashboardShell::headAssets(); ?>
    <link rel="stylesheet" href="/assets/css/ads-campaigns.css">
</head>
<body class="ads-dashboard-body">
<?php AdsDashboardShell::start($account, 'Criar an?ncio', 'campaign_create', false); ?>
    <?php if ($mobile): ?>
        <?php AdsDesktopOnlyNotice::render(); ?>
    <?php else: ?>
        <section class="ads-stage-layout">
            <aside class="ads-stage-rail">
                <span class="ads-eyebrow">Etapa 2 de 4</span>
                <h2>M?dia</h2>
                <div class="ads-step-list">
                    <div class="ads-step-row"><span class="ads-step-dot">1</span><span>Formato</span></div>
                    <div class="ads-step-row active"><span class="ads-step-dot">2</span><span>M?dia</span></div>
                    <div class="ads-step-row"><span class="ads-step-dot">3</span><span>Detalhes</span></div>
                    <div class="ads-step-row"><span class="ads-step-dot">4</span><span>Revis?o</span></div>
                </div>
            </aside>
            <article class="ads-stage-card">
                <span class="ads-eyebrow"><?= $isVideo ? 'V?deo' : 'Foto ou GIF' ?></span>
                <h2><?= $isVideo ? 'Envie seu v?deo.' : 'Envie sua imagem.' ?></h2>
                <p><?= $isVideo
                        ? 'O arquivo ser? enviado em partes para o provedor de v?deo e depois servido pela CDN interna do PipoCine.'
                        : 'A imagem ser? processada pelo ImgBB e depois entregue pela CDN interna do PipoCine.' ?></p>

                <div class="ads-format-notes">
                    <?php if ($isVideo): ?>
                        <strong>V?deo</strong>
                        <span>Formatos aceitos: MP4, MKV, FLV, WEBM e MOV.</span>
                        <span>V?deos acima de 20 segundos ainda podem existir, mas n?o poder?o ser marcados como obrigat?rios.</span>
                    <?php else: ?>
                        <strong>Imagem ou GIF</strong>
                        <span>Formatos aceitos: JPG, PNG, GIF e WEBP.</span>
                        <span>Arquivos visuais seguem por processamento de imagem e preservam uma pr?via leve.</span>
                    <?php endif; ?>
                </div>

                <div class="ads-upload-box" id="upload-box" <?= $alreadyUploaded ? 'hidden' : '' ?>>
                    <div class="ads-upload-head">
                        <div>
                            <strong><?= $isVideo ? 'Selecione o v?deo do an?ncio' : 'Selecione a imagem do an?ncio' ?></strong>
                            <span><?= $isVideo ? 'Envio em partes, com acompanhamento visual.' : 'Processamento leve e pr?via preservada.' ?></span>
                        </div>
                        <label class="ads-file-trigger" for="file">Escolher arquivo</label>
                    </div>
                    <input id="file" class="ads-native-file" type="file" accept="<?= $isVideo ? '.mp4,.mkv,.flv,.webm,.mov,video/*' : 'image/jpeg,image/png,image/gif,image/webp' ?>">
                    <div class="ads-file-card" id="file-card">
                        <strong id="file-name">Nenhum arquivo selecionado</strong>
                        <span id="file-meta">Escolha um arquivo para iniciar o envio.</span>
                    </div>
                    <div class="ads-progress"><span id="progress"></span></div>
                    <div class="ads-muted" id="progress-label">Nenhum arquivo enviado.</div>
                    <div class="ads-message" id="message"></div>
                    <div class="ads-actions">
                        <button class="ads-primary-button" id="upload-button" type="button">Enviar m?dia</button>
                    </div>
                </div>
                <div class="ads-actions" id="next-wrap" <?= $alreadyUploaded ? '' : 'hidden' ?>>
                    <a class="ads-primary-link" href="/ads/anuncios/criar/detalhes?draft=<?= urlencode($draftToken) ?>">Avan?ar</a>
                </div>
            </article>
        </section>
        <script>
            const draftToken = '<?= htmlspecialchars($draftToken, ENT_QUOTES, 'UTF-8') ?>';
            const isVideo = <?= $isVideo ? 'true' : 'false' ?>;
            const fileInput = document.getElementById('file');
            const uploadButton = document.getElementById('upload-button');
            const progress = document.getElementById('progress');
            const progressLabel = document.getElementById('progress-label');
            const message = document.getElementById('message');
            const nextWrap = document.getElementById('next-wrap');
            const fileName = document.getElementById('file-name');
            const fileMeta = document.getElementById('file-meta');

            <?php if ($alreadyUploaded): ?>
            setProgress(100, 'M?dia j? enviada.');
            <?php endif; ?>

            function setProgress(value, label) {
                progress.style.width = `${Math.max(0, Math.min(100, value))}%`;
                progressLabel.textContent = label;
            }

            function formatBytes(bytes) {
                if (!bytes) return '0 B';
                const units = ['B', 'KB', 'MB', 'GB'];
                const index = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
                return `${(bytes / (1024 ** index)).toFixed(index === 0 ? 0 : 1)} ${units[index]}`;
            }

            fileInput.addEventListener('change', () => {
                const file = fileInput.files[0];
                fileName.textContent = file ? file.name : 'Nenhum arquivo selecionado';
                fileMeta.textContent = file ? `${formatBytes(file.size)} ? ${file.type || 'tipo n?o identificado'}` : 'Escolha um arquivo para iniciar o envio.';
            });

            async function readVideoDuration(file) {
                return new Promise((resolve) => {
                    const video = document.createElement('video');
                    video.preload = 'metadata';
                    video.onloadedmetadata = () => {
                        URL.revokeObjectURL(video.src);
                        resolve(Number.isFinite(video.duration) ? video.duration : 0);
                    };
                    video.onerror = () => resolve(0);
                    video.src = URL.createObjectURL(file);
                });
            }

            function markUploaded(redirect) {
                uploadButton.hidden = true;
                fileInput.disabled = true;
                nextWrap.hidden = false;
                if (redirect) {
                    setTimeout(() => location.href = redirect, 650);
                }
            }

            async function uploadImage(file) {
                return new Promise((resolve, reject) => {
                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', `/api/ads/campaigns/${draftToken}/image`);
                    xhr.upload.onprogress = (event) => {
                        if (event.lengthComputable) {
                            const percent = Math.round((event.loaded / event.total) * 100);
                            setProgress(percent, `Enviando imagem... ${percent}%`);
                        }
                    };
                    xhr.onload = () => {
                        try {
                            resolve(JSON.parse(xhr.responseText));
                        } catch (_) {
                            reject(new Error('Resposta inv?lida do servidor.'));
                        }
                    };
                    xhr.onerror = () => reject(new Error('Falha no upload da imagem.'));
                    const body = new FormData();
                    body.append('image', file);
                    xhr.send(body);
                });
            }

            async function uploadVideo(file) {
                const duration = await readVideoDuration(file);
                const tokenResponse = await fetch(`/api/ads/campaigns/${draftToken}/video-token`, {method: 'POST'});
                const tokenData = await tokenResponse.json();
                if (!tokenData.success) return tokenData;

                const chunkSize = tokenData.chunk_size_bytes || 5 * 1024 * 1024;
                let start = 0;
                while (start < file.size) {
                    const chunk = file.slice(start, Math.min(start + chunkSize, file.size));
                    await new Promise((resolve, reject) => {
                        const xhr = new XMLHttpRequest();
                        xhr.open('POST', tokenData.upload_url);
                        xhr.onload = () => xhr.status >= 200 && xhr.status < 300 ? resolve() : reject(new Error('Falha ao enviar parte do v?deo.'));
                        xhr.onerror = () => reject(new Error('Falha ao enviar parte do v?deo.'));
                        xhr.upload.onprogress = (event) => {
                            if (event.lengthComputable) {
                                const uploaded = start + event.loaded;
                                const percent = Math.round((uploaded / file.size) * 100);
                                setProgress(percent, `Enviando v?deo... ${percent}%`);
                            }
                        };
                        const body = new FormData();
                        body.append('chunk', chunk, file.name);
                        body.append('name', file.name);
                        body.append('total', String(file.size));
                        body.append('start', String(start));
                        body.append('token', tokenData.token);
                        xhr.send(body);
                    });
                    start += chunk.size;
                }

                const processingMessages = [
                    'Upload conclu?do. Aguardando o MP4 do provedor...',
                    'Nosso servidor est? verificando o processamento do v?deo...',
                    'Preparando a vers?o final para a CDN do PipoCine...'
                ];
                let processingIndex = 0;
                setProgress(100, processingMessages[processingIndex]);
                const processingTicker = setInterval(() => {
                    processingIndex = (processingIndex + 1) % processingMessages.length;
                    setProgress(100, processingMessages[processingIndex]);
                }, 2400);
                try {
                    const complete = await fetch(`/api/ads/campaigns/${draftToken}/video-complete`, {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({
                            token: tokenData.token,
                            duration_seconds: duration,
                            original_filename: file.name,
                            file_size_bytes: file.size
                        })
                    });
                    return complete.json();
                } finally {
                    clearInterval(processingTicker);
                }
            }

            uploadButton.addEventListener('click', async () => {
                const file = fileInput.files[0];
                if (!file) {
                    message.textContent = 'Selecione um arquivo antes de continuar.';
                    return;
                }
                uploadButton.disabled = true;
                message.textContent = '';
                try {
                    const data = isVideo ? await uploadVideo(file) : await uploadImage(file);
                    if (!data.success) {
                        uploadButton.disabled = false;
                        message.textContent = data.message || 'N?o foi poss?vel enviar a m?dia.';
                        return;
                    }
                    setProgress(100, 'M?dia enviada com sucesso.');
                    markUploaded(data.redirect);
                } catch (error) {
                    uploadButton.disabled = false;
                    message.textContent = error.message || 'Falha no upload.';
                }
            });
        </script>
    <?php endif; ?>
<?php AdsDashboardShell::end(); ?>
</body>
</html>
