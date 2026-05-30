import { CreditCard } from 'lucide-react';

export const paymentProcessingArticle = {
    id: 'processamento-pagamentos',
    category: 'Pagamentos',
    title: 'Como os pagamentos sao processados',
    excerpt: 'Stripe para cartao/boleto, AbacatePay para Pix e politica de nao armazenamento de cartao.',
    icon: CreditCard,
    content: [
        {
            heading: 'Provedores de pagamento',
            body: [
                'Pagamentos com cartao e boleto sao processados por Stripe. Pagamentos via Pix sao processados pela AbacatePay. Esses provedores foram escolhidos para separar a operacao financeira sensivel da infraestrutura principal da Wasm Cloud.',
                'A Wasm Cloud nao armazena dados completos de cartao do usuario. Quando necessario, usamos fluxos de pagamento/tokenizacao fornecidos pelo provedor de pagamento.',
            ],
        },
        {
            heading: 'Cartoes e dados sensiveis',
            body: [
                'Dados de cartao devem ser tratados com extremo cuidado. A LGPD exige medidas de seguranca tecnicas e administrativas adequadas para proteger dados pessoais. Alem disso, o padrao PCI DSS restringe fortemente o armazenamento de dados de autenticacao sensiveis de cartao apos autorizacao.',
                'Por isso, a Wasm Cloud nao guarda numero completo, CVV ou dados sensiveis de autenticacao de cartao no banco da aplicacao. Essa abordagem reduz risco, protege o usuario e deixa a responsabilidade tecnica de armazenamento/tokenizacao com provedores especializados.',
            ],
        },
        {
            heading: 'Pix',
            body: [
                'Para Pix, utilizamos AbacatePay como provedor de pagamento. A documentacao de Pix e seus comprovantes/status ficam vinculados ao fluxo do provedor e ao projeto correspondente.',
            ],
        },
    ],
};
