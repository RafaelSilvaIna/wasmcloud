# Pipocine Internal CDN

Esta CDN roda dentro do Pipocine. Ela nao usa provedor externo, nao faz upload e nao revela a URL real do video ao navegador.

## Fluxo

1. O player chama `/api/v2/episode-url`.
2. A API resolve a fonte como antes, mas tambem emite `cdn_internal`.
3. O player carrega:
   - `/cdn/video/{token}.mp4` para video sem audio.
   - `/cdn/audio/{profile}/{token}.m4a` para audio separado.
4. Os endpoints `/cdn/*` validam sessao, profile, User-Agent, assinatura e expiracao.
5. O Pipocine resolve a URL externa no servidor e usa FFmpeg para transmux/processamento em tempo real.

## Requisitos

Instale FFmpeg no servidor e configure se necessario:

```env
FFMPEG_PATH=/usr/bin/ffmpeg
PIPOCINE_CDN_SECRET=uma-chave-longa-unica-do-servidor
PIPOCINE_CDN_TTL=900
```

Se `PIPOCINE_CDN_SECRET` nao existir, o sistema usa uma chave estavel derivada da configuracao local. Em producao, defina a variavel.

## Endpoints

```txt
GET /cdn/video/{token}.mp4
GET /cdn/audio/standard/{token}.m4a
GET /cdn/audio/smart_eq/{token}.m4a
GET /cdn/audio/virtual_surround/{token}.m4a
GET /cdn/audio/safe_boost/{token}.m4a
```

## Observacoes

- O MP4 e fragmentado em tempo real (`frag_keyframe+empty_moov`), ideal para reproduzir sem salvar arquivo.
- O video e entregue com `-an`, sem trilha de audio.
- O audio e entregue com `-vn` e filtros por perfil.
- As URLs sao temporarias e amarradas a sessao/perfil.
- Range byte tradicional nao e exposto porque o conteudo e processado em pipe. Para busca avancada por tempo, o proximo passo e adicionar `?t=segundos` e reiniciar FFmpeg com `-ss`.
