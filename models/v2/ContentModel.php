<?php
class ContentModel {
    private $db;

    public function __construct($pdo) {
        $this->db = $pdo;
    }

    public function getByCategory($category, $limit) {
        $catMap = [
            'lancamentos' => ['tipo' => null, 'gen' => null, 'ord' => 'data_lancamento DESC'],
            'lancamentos_filmes' => ['tipo' => 'filme', 'gen' => null, 'ord' => 'data_lancamento DESC'],
            'lancamentos_series' => ['tipo' => 'serie', 'gen' => null, 'ord' => 'data_lancamento DESC'],
            'top_filmes' => ['tipo' => 'filme', 'gen' => null, 'ord' => 'nota DESC'],
            'top_series' => ['tipo' => 'serie', 'gen' => null, 'ord' => 'nota DESC'],
            'em_alta' => ['tipo' => null, 'gen' => null, 'ord' => 'id DESC'],
            'antigos' => ['tipo' => null, 'gen' => null, 'ord' => 'data_lancamento ASC'],
            'acao_filmes' => ['tipo' => 'filme', 'gen' => 'Ação', 'ord' => 'id DESC'],
            'acao_series' => ['tipo' => 'serie', 'gen' => 'Ação', 'ord' => 'id DESC'],
            'comedia_filmes' => ['tipo' => 'filme', 'gen' => 'Comédia', 'ord' => 'id DESC'],
            'comedia_series' => ['tipo' => 'serie', 'gen' => 'Comédia', 'ord' => 'id DESC'],
            'terror_filmes' => ['tipo' => 'filme', 'gen' => 'Terror', 'ord' => 'id DESC'],
            'terror_series' => ['tipo' => 'serie', 'gen' => 'Terror', 'ord' => 'id DESC'],
            'romance_filmes' => ['tipo' => 'filme', 'gen' => 'Romance', 'ord' => 'id DESC'],
            'romance_series' => ['tipo' => 'serie', 'gen' => 'Romance', 'ord' => 'id DESC'],
            'ficcao_filmes' => ['tipo' => 'filme', 'gen' => 'Ficção', 'ord' => 'id DESC'],
            'ficcao_series' => ['tipo' => 'serie', 'gen' => 'Ficção', 'ord' => 'id DESC'],
            'animacao_filmes' => ['tipo' => 'filme', 'gen' => 'Animação', 'ord' => 'id DESC'],
            'animacao_series' => ['tipo' => 'serie', 'gen' => 'Animação', 'ord' => 'id DESC'],
            'suspense_filmes' => ['tipo' => 'filme', 'gen' => 'Suspense', 'ord' => 'id DESC'],
            'suspense_series' => ['tipo' => 'serie', 'gen' => 'Suspense', 'ord' => 'id DESC'],
            'drama_filmes' => ['tipo' => 'filme', 'gen' => 'Drama', 'ord' => 'id DESC'],
            'drama_series' => ['tipo' => 'serie', 'gen' => 'Drama', 'ord' => 'id DESC'],
            'fantasia_filmes' => ['tipo' => 'filme', 'gen' => 'Fantasia', 'ord' => 'id DESC'],
            'fantasia_series' => ['tipo' => 'serie', 'gen' => 'Fantasia', 'ord' => 'id DESC'],
            'misterio_filmes' => ['tipo' => 'filme', 'gen' => 'Mistério', 'ord' => 'id DESC'],
            'misterio_series' => ['tipo' => 'serie', 'gen' => 'Mistério', 'ord' => 'id DESC'],
            'crime_filmes' => ['tipo' => 'filme', 'gen' => 'Crime', 'ord' => 'id DESC'],
            'crime_series' => ['tipo' => 'serie', 'gen' => 'Crime', 'ord' => 'id DESC'],
            'documentario_filmes' => ['tipo' => 'filme', 'gen' => 'Documentário', 'ord' => 'id DESC'],
            'documentario_series' => ['tipo' => 'serie', 'gen' => 'Documentário', 'ord' => 'id DESC'],
            'familia_filmes' => ['tipo' => 'filme', 'gen' => 'Família', 'ord' => 'id DESC'],
            'familia_series' => ['tipo' => 'serie', 'gen' => 'Família', 'ord' => 'id DESC'],
            'guerra_filmes' => ['tipo' => 'filme', 'gen' => 'Guerra', 'ord' => 'id DESC'],
            'guerra_series' => ['tipo' => 'serie', 'gen' => 'Guerra', 'ord' => 'id DESC'],
            'historia_filmes' => ['tipo' => 'filme', 'gen' => 'História', 'ord' => 'id DESC'],
            'historia_series' => ['tipo' => 'serie', 'gen' => 'História', 'ord' => 'id DESC'],
            'musica_filmes' => ['tipo' => 'filme', 'gen' => 'Música', 'ord' => 'id DESC'],
            'musica_series' => ['tipo' => 'serie', 'gen' => 'Música', 'ord' => 'id DESC'],
            'kids' => ['tipo' => null, 'gen' => 'Família', 'ord' => 'id DESC']
        ];

        $conf = $catMap[$category] ?? $catMap['lancamentos'];
        $sql = "SELECT id, id_tmdb, titulo, poster, capa, nota, data_lancamento, tipo FROM conteudos WHERE 1=1";
        $params = [];

        if ($conf['tipo']) {
            $sql .= " AND tipo = ?";
            $params[] = $conf['tipo'];
        }

        if ($conf['gen']) {
            $sql .= " AND genero LIKE ?";
            $params[] = '%' . $conf['gen'] . '%';
        }

        $sql .= " ORDER BY " . $conf['ord'] . " LIMIT " . (int)$limit;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}