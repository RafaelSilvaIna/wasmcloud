<?php
/**
 * PlatformController
 *
 * Lida com requisições GET /api/v2/plataforma
 *
 * Parâmetros aceitos:
 *  ?marca=netflix          (obrigatório) slug da plataforma
 *  ?tipo=filme|serie       (opcional)    filtra por tipo de conteúdo
 *  ?pagina=1               (opcional)    página atual (default 1)
 *  ?limite=24              (opcional)    itens por página (max 48, default 24)
 *  ?marcas=1               (opcional)    se presente, retorna a lista de marcas suportadas
 */
class PlatformController {
    private PlatformService $service;

    /** Marcas suportadas — usadas para whitelist de validação */
    private const VALID_BRANDS = [
        'netflix', 'prime', 'disney', 'max', 'globoplay', 'appletv', 'paramount'
    ];

    private const VALID_TIPOS = ['filme', 'serie'];

    public function __construct(PlatformService $service) {
        $this->service = $service;
    }

    public function handle(): void {
        // Endpoint especial: lista todas as marcas suportadas com metadados
        if (isset($_GET['marcas'])) {
            $this->sendJson([
                'sucesso' => true,
                'marcas'  => PlatformService::allBrands(),
            ]);
            return;
        }

        // Valida parâmetros de entrada
        $marca = strtolower(trim($_GET['marca'] ?? ''));

        if (!$marca || !in_array($marca, self::VALID_BRANDS, true)) {
            $this->sendJson([
                'sucesso' => false,
                'erro'    => 'Parametro "marca" invalido. Marcas suportadas: ' . implode(', ', self::VALID_BRANDS),
            ], 422);
            return;
        }

        $tipoRaw = strtolower(trim($_GET['tipo'] ?? ''));
        $tipo    = in_array($tipoRaw, self::VALID_TIPOS, true) ? $tipoRaw : null;
        $pagina  = max(1, (int)($_GET['pagina'] ?? 1));
        $limite  = min(48, max(1, (int)($_GET['limite'] ?? 24)));

        try {
            $resultado = $this->service->get($marca, $tipo, $pagina, $limite);

            // Cache de 1 hora — dados de provedor não mudam com frequência
            header('Cache-Control: public, max-age=3600, stale-while-revalidate=600');

            $this->sendJson([
                'sucesso' => true,
                ...$resultado,
            ]);
        } catch (InvalidArgumentException $e) {
            $this->sendJson(['sucesso' => false, 'erro' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            $this->sendJson([
                'sucesso' => false,
                'erro'    => 'Erro interno ao processar plataforma.',
                'detalhe' => $e->getMessage(),
            ], 500);
        }
    }

    private function sendJson(array $data, int $status = 200): void {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
