<?php
class TMDBHelper {
    private const API_KEY = 'dc6299fd1adb4e32cf16017eecb33295';
    private const BASE_URL = 'https://api.themoviedb.org/3';

    /**
     * MÉTODOS ESTÁTICOS DA API DE CONTEÚDO (Home)
     * Usado via cURL Multi para processar vários conteúdos em paralelo.
     */
    public static function getRichData(array $items) {
        $mh = curl_multi_init();
        $handles = [];
        $results = [];

        foreach ($items as $index => $item) {
            if (empty($item['id_tmdb'])) continue;
            
            $type = ($item['tipo'] === 'serie') ? 'tv' : 'movie';
            $url = self::BASE_URL . "/{$type}/{$item['id_tmdb']}?api_key=" . self::API_KEY . "&language=pt-BR&append_to_response=credits,images,release_dates,content_ratings";
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => 3
            ]);
            curl_multi_add_handle($mh, $ch);
            $handles[$index] = $ch;
        }

        $running = null;
        do {
            curl_multi_exec($mh, $running);
            curl_multi_select($mh);
        } while ($running > 0);

        foreach ($handles as $index => $ch) {
            $response = curl_multi_getcontent($ch);
            $results[$index] = json_decode($response, true);
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        
        curl_multi_close($mh);
        return $results;
    }

    /**
     * MÉTODOS DE INSTÂNCIA DA API DE EXIBIÇÃO (Player)
     * Utilizados pelo ExhibitionService para buscar as sinopses e temporadas.
     */
    
    // Função utilitária para requisições simples em tempo real
    public function fetch(string $endpoint): ?array {
        $url = self::BASE_URL . $endpoint . "&api_key=" . self::API_KEY . "&language=pt-BR";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 3, // Timeout de segurança para não prender o PHP
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return null;
        }

        return json_decode($response, true);
    }

    // Busca informações gerais da série (Lista de todas as temporadas disponíveis)
    public function getSeriesInfo(int $tmdbId): ?array {
        return $this->fetch("/tv/{$tmdbId}?");
    }

    // Busca a capa, sinopse e nome de um episódio específico
    public function getEpisodeMetadata(int $tmdbId, int $season, int $episode): ?array {
        return $this->fetch("/tv/{$tmdbId}/season/{$season}/episode/{$episode}?");
    }

    // Busca TODOS os episódios de uma temporada inteira
    public function getSeasonEpisodes(int $tmdbId, int $season): ?array {
        return $this->fetch("/tv/{$tmdbId}/season/{$season}?");
    }

    // Busca logos do conteúdo (movie ou tv) — PT-BR com fallback para EN
    public function getContentImages(int $tmdbId, string $type): ?array {
        $mediaType = ($type === 'serie' || $type === 'series' || $type === 'tv') ? 'tv' : 'movie';
        // include_image_language sem restrição de language= para trazer logos PT e EN
        $url = self::BASE_URL . "/{$mediaType}/{$tmdbId}/images?api_key=" . self::API_KEY . "&include_image_language=pt,en,null";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 3,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) return null;
        return json_decode($response, true);
    }
}