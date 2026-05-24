# Requisicao Formal da CDN Pipocine

Documento tecnico para formalizar a entrega de video do Pipocine por CDN/proxy interno, sem bypass de controles da origem.

## 1. Identificacao

- Produto: Pipocine
- Dominio publico: `https://pipocine.site`
- Endpoint publico de reproducao CDN: `https://pipocine.site/video/cdn?token={idtoken}`
- API que emite dados de playback: `https://pipocine.site/api/v2/episode-url`
- Objetivo: entregar midia ao player do Pipocine usando URL same-origin, mantendo token temporario, sessao de usuario e fallback para reproducao direta quando a origem nao permitir leitura server-side.

## 2. Fluxo Completo

1. O navegador abre o player:
   - `GET /player?id={tmdb_id}&type={filme|serie}&s={temporada}&e={episodio}&audio={dub|leg}`

2. O player solicita a fonte:
   - `GET /api/v2/episode-url?id={tmdb_id}&type={tipo}&s={temporada}&e={episodio}&audio={audio}`

3. O servidor Pipocine busca a URL original no banco `cineveo`.

4. O servidor valida a origem para CDN:
   - URL precisa ser `http` ou `https`;
   - host nao pode resolver para rede privada/local;
   - probe server-side precisa retornar `200` ou `206`;
   - conteudo nao pode ser HTML/JSON quando esperado video;
   - playlist HLS precisa iniciar com `#EXTM3U`.

5. Se a origem aceitar leitura server-side:
   - a API retorna `cdn_internal.enabled=true`;
   - o player usa `/video/cdn?token={idtoken}`;
   - o navegador nunca recebe a URL externa como URL principal.

6. Se a origem negar leitura server-side, por exemplo `403`:
   - a API retorna `cdn_internal.enabled=false`;
   - `cdn_internal.direct_only=true`;
   - o player usa a URL direta como fallback.

## 3. Requisicao do Player para a API

### Endpoint

```http
GET /api/v2/episode-url?id=229891&type=serie&s=1&e=1&audio=dub HTTP/1.1
Host: pipocine.site
Accept: application/json
Cookie: CINEVEO_SECURE_V2={sessao_http_only}
User-Agent: {user_agent_do_navegador}
```

### Parametros

- `id`: ID TMDB do conteudo.
- `type`: `filme`, `serie`, `series` ou `tv`.
- `s`: temporada, minimo `1`.
- `e`: episodio, minimo `1`.
- `audio`: `dub` ou `leg`.

### Resposta com CDN habilitada

```json
{
  "success": true,
  "url": "https://origem-autorizada.example/video.mp4",
  "media_type": "mp4",
  "audio": "dub",
  "next_episode": null,
  "meta": {
    "nome": "Episodio 1",
    "imagem": "https://image.tmdb.org/t/p/w300/exemplo.jpg",
    "sinopse": ""
  },
  "cdn_internal": {
    "enabled": true,
    "mode": "internal_origin_proxy",
    "video_url": "/video/cdn?token={idtoken_opaco}",
    "audio_urls": [],
    "media_type": "mp4",
    "expires_in": 14400,
    "expires_at": 1779560000
  }
}
```

### Resposta quando a origem bloqueia CDN

```json
{
  "success": true,
  "url": "https://origem.example/video.mp4",
  "media_type": "mp4",
  "audio": "dub",
  "cdn_internal": {
    "enabled": false,
    "mode": "source_passthrough",
    "reason": "CDN interna ignorada: origem bloqueia leitura server-side. Usando reproducao direta do navegador.",
    "direct_only": true,
    "probe": {
      "reason": "origin_http_403",
      "http_status": 403,
      "cached": false
    }
  }
}
```

## 4. Token `idtoken`

O `idtoken` e opaco para o navegador. Ele e salvo no servidor em `data/cdn-tokens` e contem os dados necessarios para resolver a fonte sem expor a URL original como URL principal da pagina.

### Dados internos do token

- `v`: versao do token.
- `kind`: `video` ou `audio`.
- `uid`: ID do usuario logado.
- `pid`: ID do perfil ativo.
- `sid`: hash da sessao PHP.
- `uah`: hash do User-Agent.
- `iat`: timestamp de emissao.
- `exp`: timestamp de expiracao.
- `nonce`: valor aleatorio.
- `id`: ID TMDB.
- `type`: `filme` ou `serie`.
- `s`: temporada.
- `e`: episodio.
- `audio`: `dub` ou `leg`.
- `url`: URL original resolvida no servidor.
- `origin`: origem HTTP da URL original.
- `media_type`: `mp4`, `m3u8`, `webm`, `mkv`, `ts`, `m4s`, `m4a`, `aac` ou `auto`.

### Validade

- Padrao: `14400` segundos.
- Minimo aceito: `300` segundos.
- Maximo aceito: `21600` segundos.
- Configuravel por `PIPOCINE_CDN_TTL`.

## 5. Requisicao do Navegador para a CDN Pipocine

```http
GET /video/cdn?token={idtoken_opaco} HTTP/1.1
Host: pipocine.site
Range: bytes=0-
Accept: video/webm,video/ogg,video/*;q=0.9,application/octet-stream;q=0.8,*/*;q=0.5
Cookie: CINEVEO_SECURE_V2={sessao_http_only}
User-Agent: {user_agent_do_navegador}
Referer: https://pipocine.site/player
```

### Validacoes da CDN

- Metodo precisa ser `GET` ou `HEAD`.
- Usuario precisa estar autenticado.
- Perfil ativo precisa existir.
- `Referer`, quando enviado, precisa ser same-origin.
- Token precisa existir no storage server-side.
- Token nao pode estar expirado.
- `uid`, `pid`, `sid` e `uah` precisam bater com a sessao atual.

## 6. Requisicao Server-Side da CDN Pipocine para a Origem

Esta e a requisicao que a origem precisa autorizar. Ela sai do servidor Pipocine, nao do navegador do usuario.

```http
GET {source_url} HTTP/1.1
Host: {host_da_origem}
User-Agent: PipocineMediaProxy/1.0 (+https://pipocine.site)
Accept-Encoding: identity
Connection: keep-alive
Accept: video/*,audio/*,application/octet-stream,*/*;q=0.8
Range: bytes={inicio}-{fim}
```

### Metodos necessarios

- `GET`: obrigatorio.
- `HEAD`: recomendado, mas nao obrigatorio.

### Status aceitos

- `200 OK`: aceito para conteudo completo.
- `206 Partial Content`: recomendado para seek e streaming profissional.
- `416 Range Not Satisfiable`: aceito quando o range e invalido, desde que inclua `Content-Range: bytes */{size}`.

### Headers de resposta esperados

```http
Content-Type: video/mp4
Content-Length: {bytes}
Accept-Ranges: bytes
Content-Range: bytes {inicio}-{fim}/{total}
ETag: "{etag_opcional}"
Last-Modified: {data_opcional}
```

## 7. HLS

Para HLS, a primeira URL precisa retornar uma playlist valida.

### Playlist

```http
GET {source_url}.m3u8 HTTP/1.1
Accept: application/vnd.apple.mpegurl,application/x-mpegURL,text/plain,*/*;q=0.8
User-Agent: PipocineMediaProxy/1.0 (+https://pipocine.site)
```

Resposta esperada:

```m3u8
#EXTM3U
#EXT-X-VERSION:3
#EXTINF:6.000,
segment-0001.ts
```

A CDN Pipocine reescreve automaticamente:

- segmentos `.ts`;
- segmentos `.m4s`;
- `#EXT-X-MAP:URI="..."`;
- `#EXT-X-KEY:URI="..."`;
- playlists filhas.

Cada URI reescrita recebe novo token interno:

```text
/video/cdn?token={idtoken_do_segmento}
```

## 8. Motivos de Bloqueio

### `origin_http_403`

A origem negou acesso ao servidor Pipocine.

Acao correta:

- liberar o IP de saida do Pipocine;
- emitir token/signed URL valido para o servidor;
- fornecer API ou CDN autorizada;
- usar storage/CDN propria do Pipocine.

### `origin_returned_non_media`

A origem retornou HTML/JSON em vez de video.

Possiveis causas:

- pagina de erro;
- captcha;
- anti-bot;
- link expirado;
- hotlink protection.

### `origin_not_hls_playlist`

A URL marcada como HLS nao retornou playlist iniciada por `#EXTM3U`.

## 9. Solicitacao Formal para a Origem

Assunto: Liberacao de acesso server-side para CDN Pipocine

Prezados,

Solicitamos autorizacao tecnica para que o dominio `pipocine.site` entregue conteudos licenciados/autorizados por meio de proxy/CDN interno, mantendo controle de sessao, expiracao de token e entrega same-origin ao player.

Dados da integracao:

- Dominio consumidor: `https://pipocine.site`
- Endpoint publico do player: `https://pipocine.site/player`
- Endpoint CDN: `https://pipocine.site/video/cdn?token={idtoken}`
- User-Agent server-side: `PipocineMediaProxy/1.0 (+https://pipocine.site)`
- IP publico de saida do servidor Pipocine: `{IP_PUBLICO_DO_SERVIDOR_PIPOCINE}`
- Metodos necessarios: `GET` e, preferencialmente, `HEAD`
- Suporte necessario: `Range` / `206 Partial Content`
- Tipos de midia: `video/mp4`, `application/vnd.apple.mpegurl`, `video/mp2t`, `video/webm`, `audio/mp4`, `audio/aac`

Pedimos que o servidor/CDN de origem permita requisicoes server-side vindas do IP informado, com suporte a streaming parcial via `Range`, sem exigir desafio interativo de navegador para estes endpoints de midia.

Caso usem signed URLs, solicitamos documentacao para emissao server-side de URLs assinadas com expiracao, escopo por conteudo e suporte a segmentos HLS quando aplicavel.

Atenciosamente,

Equipe Pipocine

## 10. Dados que Nao Devem Ser Enviados em Documento

- Senhas de banco.
- `PIPOCINE_CDN_SECRET`.
- Cookies reais de usuario.
- Tokens reais de sessao.
- `idtoken` real ainda valido.
- URLs privadas que contenham assinatura ativa.
- Chaves de DRM.

## 11. Checklist de Aceite

- Origem responde `200` ou `206` para o IP do Pipocine.
- `Range: bytes=0-1` funciona.
- `Content-Type` e de midia, nao HTML.
- HLS retorna `#EXTM3U`.
- Segmentos HLS tambem aceitam acesso server-side.
- Link nao exige captcha/desafio interativo.
- Expiracao da URL e maior que o buffer minimo do player.
- CDN Pipocine consegue reproduzir sem fallback direto.
