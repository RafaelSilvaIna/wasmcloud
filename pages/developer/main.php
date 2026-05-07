<?php
/**
 * PipoCine Developer Portal
 * Apresentação minimalista da API para desenvolvedores
 */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="PipoCine API - A API mais segura para construir plataformas de streaming">
    <title>PipoCine API — Segurança e Simplicidade</title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #050508;
            color: #fff;
            line-height: 1.7;
        }
        
        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 24px;
        }
        
        /* Navbar */
        .nav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            padding: 20px 0;
            background: rgba(5, 5, 8, 0.9);
            backdrop-filter: blur(20px);
            z-index: 100;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .nav .container {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: #fff;
            font-weight: 700;
            font-size: 18px;
        }
        
        .logo-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #e50914, #ff6b6b);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .logo-dev {
            color: #ff6b6b;
            font-size: 13px;
            font-weight: 500;
            margin-left: 4px;
        }
        
        .nav-cta {
            padding: 10px 20px;
            background: #e50914;
            color: #fff;
            text-decoration: none;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .nav-cta:hover {
            background: #ff1a25;
            transform: translateY(-1px);
        }
        
        /* Hero */
        .hero {
            min-height: 90vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 140px 0 80px;
            position: relative;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(229, 9, 20, 0.2), transparent 70%);
            border-radius: 50%;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 0;
            animation: pulse 4s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 0.3; transform: translate(-50%, -50%) scale(1); }
            50% { opacity: 0.6; transform: translate(-50%, -50%) scale(1.1); }
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 700px;
        }
        
        .hero-badge {
            display: inline-block;
            padding: 8px 16px;
            background: rgba(229, 9, 20, 0.15);
            border: 1px solid rgba(229, 9, 20, 0.3);
            border-radius: 20px;
            font-size: 13px;
            color: #ff6b6b;
            margin-bottom: 24px;
        }
        
        .hero h1 {
            font-size: clamp(2.5rem, 6vw, 4rem);
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 20px;
        }
        
        .hero h1 span {
            color: #e50914;
        }
        
        .hero p {
            font-size: 18px;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 40px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .hero-btn {
            display: inline-block;
            padding: 16px 40px;
            background: #e50914;
            color: #fff;
            text-decoration: none;
            border-radius: 30px;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .hero-btn:hover {
            background: #ff1a25;
            transform: translateY(-2px);
            box-shadow: 0 10px 40px rgba(229, 9, 20, 0.3);
        }
        
        /* Trust Section */
        .trust {
            padding: 60px 0;
            background: #0a0a0f;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .trust-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 40px;
            text-align: center;
        }
        
        .trust-item h3 {
            font-size: 14px;
            font-weight: 600;
            color: #fff;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .trust-item p {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.5);
        }
        
        /* Pricing */
        .pricing {
            padding: 100px 0;
            background: #050508;
        }
        
        .section-header {
            text-align: center;
            margin-bottom: 60px;
        }
        
        .section-header h2 {
            font-size: clamp(1.8rem, 4vw, 2.5rem);
            margin-bottom: 12px;
        }
        
        .section-header p {
            color: rgba(255, 255, 255, 0.6);
            font-size: 16px;
        }
        
        .pricing-card {
            max-width: 420px;
            margin: 0 auto;
            background: #12151c;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 40px;
            position: relative;
        }
        
        .pricing-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #e50914, #ff6b6b);
            border-radius: 20px 20px 0 0;
        }
        
        .pricing-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 6px 14px;
            background: #e50914;
            color: #fff;
            font-size: 12px;
            font-weight: 600;
            border-radius: 6px;
        }
        
        .pricing-header {
            margin-bottom: 30px;
        }
        
        .pricing-header h3 {
            font-size: 24px;
            margin-bottom: 4px;
        }
        
        .pricing-header p {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.5);
        }
        
        .price {
            display: flex;
            align-items: baseline;
            gap: 4px;
            margin-bottom: 30px;
        }
        
        .price-currency {
            font-size: 24px;
            color: rgba(255, 255, 255, 0.6);
        }
        
        .price-value {
            font-size: 56px;
            font-weight: 800;
            color: #e50914;
        }
        
        .price-period {
            color: rgba(255, 255, 255, 0.5);
        }
        
        .price-note {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.4);
            margin-top: 8px;
        }
        
        .pricing-cta {
            display: block;
            width: 100%;
            padding: 16px;
            background: #e50914;
            color: #fff;
            text-decoration: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 30px;
            transition: all 0.2s;
        }
        
        .pricing-cta:hover {
            background: #ff1a25;
        }
        
        .features {
            list-style: none;
        }
        
        .features li {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 0;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.8);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .features li:last-child {
            border-bottom: none;
        }
        
        .features li::before {
            content: '✓';
            width: 20px;
            height: 20px;
            background: rgba(34, 197, 94, 0.15);
            color: #22c55e;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            flex-shrink: 0;
        }
        
        /* Simple */
        .simple {
            padding: 100px 0;
            background: #0a0a0f;
        }
        
        .simple-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
        }
        
        .simple-card {
            background: #12151c;
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            padding: 30px;
            text-align: center;
            transition: all 0.2s;
        }
        
        .simple-card:hover {
            border-color: rgba(229, 9, 20, 0.3);
            transform: translateY(-4px);
        }
        
        .simple-icon {
            width: 56px;
            height: 56px;
            background: rgba(229, 9, 20, 0.1);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 24px;
        }
        
        .simple-card h3 {
            font-size: 18px;
            margin-bottom: 10px;
        }
        
        .simple-card p {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.6);
            line-height: 1.6;
        }
        
        /* Footer */
        .footer {
            padding: 40px 0;
            background: #050508;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            text-align: center;
        }
        
        .footer p {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.4);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .trust-grid {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            
            .pricing-card {
                padding: 30px 20px;
            }
            
            .price-value {
                font-size: 42px;
            }
        }
    </style>
</head>
<body>

    <!-- Nav -->
    <nav class="nav">
        <div class="container">
            <a href="/developer" class="logo">
                <div class="logo-icon">P</div>
                PipoCine<span class="logo-dev">API</span>
            </a>
            <a href="#planos" class="nav-cta">Começar</a>
        </div>
    </nav>

    <!-- Hero -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <span class="hero-badge">Acesso Antecipado</span>
                <h1>A API mais <span>segura</span> do mercado</h1>
                <p>Construa sua plataforma de streaming com tranquilidade. Segurança de nível bancário, proteção de dados e infraestrutura confiável.</p>
                <a href="#planos" class="hero-btn">Começar por R$ 20,99/mês</a>
            </div>
        </div>
    </section>

    <!-- Trust -->
    <section class="trust">
        <div class="container">
            <div class="trust-grid">
                <div class="trust-item">
                    <h3>🔒 Segurança First</h3>
                    <p>Criptografia em todas as camadas</p>
                </div>
                <div class="trust-item">
                    <h3>🛡️ Privacidade Zero</h3>
                    <p>Seus dados são seus. Sempre.</p>
                </div>
                <div class="trust-item">
                    <h3>⚡ Sempre Online</h3>
                    <p>99.9% de disponibilidade garantida</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing -->
    <section class="pricing" id="planos">
        <div class="container">
            <div class="section-header">
                <h2>Um plano. Tudo incluso.</h2>
                <p>Sem complexidade. Sem surpresas. Comece em minutos.</p>
            </div>
            
            <div class="pricing-card">
                <span class="pricing-badge">POPULAR</span>
                
                <div class="pricing-header">
                    <h3>Developer Pro</h3>
                    <p>Perfeito para startups e projetos em crescimento</p>
                </div>
                
                <div class="price">
                    <span class="price-currency">R$</span>
                    <span class="price-value">20,99</span>
                    <span class="price-period">/mês</span>
                </div>
                <p class="price-note">Cancele quando quiser. Sem taxas ocultas.</p>
                
                <a href="#" class="pricing-cta" onclick="alert('Em breve!'); return false;">Criar Conta</a>
                
                <ul class="features">
                    <li>2 projetos independentes</li>
                    <li>3 chaves de API por projeto</li>
                    <li>200 mil requisições mensais</li>
                    <li>350 GB de tráfego</li>
                    <li>2 domínios por projeto</li>
                    <li>Dashboard completo</li>
                    <li>Analytics em tempo real</li>
                    <li>Autenticação integrada</li>
                    <li>Proteção anti-abuso</li>
                    <li>Logs detalhados</li>
                    <li>Monitoramento 24/7</li>
                    <li>Estatísticas por endpoint</li>
                    <li>Suporte prioritário</li>
                    <li>Espaço para crescer</li>
                    <li>Infraestrutura de alta disponibilidade</li>
                </ul>
            </div>
        </div>
    </section>

    <!-- Simple -->
    <section class="simple">
        <div class="container">
            <div class="section-header">
                <h2>Por que escolher a PipoCine API?</h2>
                <p>Segurança e simplicidade em primeiro lugar</p>
            </div>
            
            <div class="simple-grid">
                <div class="simple-card">
                    <div class="simple-icon">🔐</div>
                    <h3>Segurança Enterprise</h3>
                    <p>Criptografia de ponta a ponta. Seus dados e dos seus usuários protegidos com os mesmos padrões usados por bancos.</p>
                </div>
                
                <div class="simple-card">
                    <div class="simple-icon">🚀</div>
                    <h3>Pronto para Escala</h3>
                    <p>Cresça sem preocupações. Nossa infraestrutura acompanha seu sucesso automaticamente.</p>
                </div>
                
                <div class="simple-card">
                    <div class="simple-icon">📊</div>
                    <h3>Controle Total</h3>
                    <p>Dashboard intuitivo com métricas claras. Saiba exatamente como sua API está performando.</p>
                </div>
                
                <div class="simple-card">
                    <div class="simple-icon">💬</div>
                    <h3>Suporte Humano</h3>
                    <p>Dúvidas? Fale conosco. Suporte rápido e personalizado para ajudar seu projeto decolar.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>© <?= date('Y') ?> PipoCine API. Construído com segurança e simplicidade.</p>
        </div>
    </footer>

</body>
</html>
