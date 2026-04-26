<?php
class TMDBHelper {
    private const API_KEY = 'dc6299fd1adb4e32cf16017eecb33295';
    private const BASE_URL = 'https://api.themoviedb.org/3';

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
}