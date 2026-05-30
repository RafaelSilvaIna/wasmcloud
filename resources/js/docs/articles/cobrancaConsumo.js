import { WalletCards } from 'lucide-react';

export const usageBillingArticle = {
    id: 'cobranca-consumo',
    category: 'Financeiro',
    title: 'Como funciona a cobranca por consumo',
    excerpt: 'Ciclo de apuracao, cartao via Stripe e pausa de projeto em caso de inadimplencia.',
    icon: WalletCards,
    content: [
        {
            heading: 'Apuracao por projeto',
            body: [
                'Toda cobranca por consumo considera os custos gerados por aquele projeto especifico. Isso mantem o controle simples: o usuario acompanha o consumo do projeto, entende o ciclo de cobranca e decide quando ajustar ou cancelar.',
                'Quando o modelo por consumo estiver ativo, o cartao cadastrado pelo usuario sera usado para processar a cobranca recorrente associada ao consumo daquele projeto.',
            ],
        },
        {
            heading: 'Ciclo de cobranca',
            body: [
                'A cobranca por consumo ocorre a cada 20 dias, baseada nos custos de consumo daquele projeto durante o periodo de apuracao. A Wasm Cloud apresenta essa regra de forma clara para que o usuario consiga acompanhar a previsao de pagamento.',
                'Caso o pagamento nao seja realizado em ate 5 dias apos a abertura da cobranca, o projeto podera ser pausado ate que o valor pendente seja pago novamente. Essa pausa protege a plataforma e evita que custos continuem crescendo sem cobertura financeira.',
            ],
        },
        {
            heading: 'Controle do usuario',
            body: [
                'O usuario mantem controle sobre o modelo de assinatura, cancelamento e forma de uso. A ideia e permitir que cada projeto tenha o tamanho certo para sua fase atual, sem obrigar todos os projetos a seguirem o mesmo plano.',
            ],
        },
    ],
};
