# Pipocine Internal CDN Proxy

Os endpoints publicos entram por `routes/cdn/index.php`.

Fluxo principal:

1. `/api/v2/episode-url` resolve o conteudo no servidor e emite um token opaco salvo em `data/cdn-tokens`.
2. `/video/cdn?token={idtoken}` valida sessao, perfil, user-agent e expiracao do token.
3. MP4/WebM/MKV/segmentos sao entregues por proxy same-origin com suporte a `Range` e `206 Partial Content`.
4. Playlists HLS (`.m3u8`) sao reescritas: manifestos, segmentos, mapas e chaves recebem tokens internos novos.
5. O navegador nao recebe a URL externa original quando a CDN interna esta ativa.

Configuracao:

- `PIPOCINE_CDN_INTERNAL_ENABLED=0` desativa a CDN interna.
- `PIPOCINE_CDN_TTL=14400` controla a validade dos tokens em segundos (minimo 300, maximo 21600).
- `PIPOCINE_CDN_PARTNER_AUTH_JSON` habilita credenciais formais por host, quando a origem autorizar o Pipocine.

Exemplo de credencial autorizada:

```json
{
  "media.exemplo.com": {
    "enabled": true,
    "headers": {
      "CF-Access-Client-Id": "id-do-servico",
      "CF-Access-Client-Secret": "segredo-do-servico"
    },
    "mtls_cert": "C:/secure/pipocine-client.pem",
    "mtls_key": "C:/secure/pipocine-client.key"
  }
}
```

Headers de navegador como `Cookie`, `Referer`, `Origin`, `Host`, `User-Agent` e `Sec-*` sao ignorados de proposito. Esta camada existe para autenticacao server-to-server, nao para simular usuario final.

Modo legado:

- `PIPOCINE_CDN_MODE=legacy_split_ffmpeg` reativa `/cdn/video/{token}.mp4` e `/cdn/audio/{profile}/{token}.m4a` com FFmpeg para video/audio separados.

Limite importante:

- Este proxy nao tenta quebrar Cloudflare, captcha, bot challenge, DRM, paywall ou bloqueio de origem. Para entrega estavel em escala, a fonte precisa autorizar acesso server-side ou o conteudo precisa estar em storage/CDN controlado pelo Pipocine.
