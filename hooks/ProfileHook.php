<?php
class ProfileHook {
    public static function enforceProfile(): void {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Rotas isentas de verificação de perfil ativo.
        // Estas rotas são acessíveis mesmo sem profile_id na sessão,
        // pois pertencem ao fluxo de seleção/gestão de perfis ou da conta.
        $exempt = [
            '/',                // landing page pública
            '/main',            // landing page (rota alternativa)
            '/login',           // autenticação
            '/select-profile',  // seleção de perfil
            '/manage-profiles', // gestão de perfis (não exige perfil selecionado)
            '/settings',        // configurações da conta CineVEO
        ];
        if (strpos($uri, '/api/') === 0 || in_array($uri, $exempt, true)) {
            return;
        }

        if (isset($_SESSION['user_id']) && !isset($_SESSION['profile_id'])) {
            header('Location: /select-profile');
            exit;
        }
    }
}
