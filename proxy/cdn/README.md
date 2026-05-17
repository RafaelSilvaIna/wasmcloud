# Pipocine Internal CDN Proxy

Os endpoints publicos ficam em `/cdn/*` e entram por `routes/cdn/index.php`.

Fluxo:

1. `/api/v2/episode-url` resolve o conteudo e emite links temporarios internos.
2. `/cdn/video/{token}.mp4` valida sessao/token e entrega MP4 fragmentado apenas com video.
3. `/cdn/audio/{profile}/{token}.m4a` valida sessao/token e entrega MP4 audio-only com processamento FFmpeg.
4. O navegador nunca recebe a URL externa original.

Este proxy nao armazena upload. Ele apenas resolve a URL existente no banco, protege, transmuxa em tempo real e encerra quando a conexao fecha.
