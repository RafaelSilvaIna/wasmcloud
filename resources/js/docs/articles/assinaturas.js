import { BadgeCheck } from 'lucide-react';

export const subscriptionsArticle = {
    id: 'assinaturas',
    category: 'Financeiro',
    title: 'Como funcionam as assinaturas',
    excerpt: 'Entenda beneficios por projeto, planos de processamento e controle de cancelamento.',
    icon: BadgeCheck,
    content: [
        {
            heading: 'Assinatura por projeto, nao assinatura global',
            body: [
                'Na Wasm Cloud, cada projeto pode ter o proprio modelo de beneficios. Isso significa que um projeto pode operar no processamento Micro, enquanto outro projeto pode usar Medio, Large ou pagar por consumo. A conta do usuario organiza os projetos, mas a assinatura nao precisa ser global para todos eles.',
                'Esse modelo da ao usuario liberdade para pagar somente pelo que faz sentido para cada aplicacao. Um ambiente de teste pode continuar no Micro, enquanto um SaaS em producao pode usar processamento Medio, Large ou consumo sob demanda.',
            ],
        },
        {
            heading: 'Modelos de processamento',
            body: [
                'Micro e o modelo gratuito, indicado para iniciar, validar uma ideia e manter projetos menores. Medio e Large sao planos pagos para projetos que precisam de mais capacidade operacional. O pagamento por consumo e uma alternativa para projetos em que a demanda varia e o usuario prefere pagar conforme uso.',
                'Cada projeto preserva sua propria configuracao, historico e beneficios. Alterar um projeto nao muda automaticamente os demais.',
            ],
        },
        {
            heading: 'Cancelamento com beneficios preservados',
            body: [
                'O usuario pode cancelar quando quiser. Caso tenha pago por um ciclo Medio ou Large e cancele antes do fim do periodo contratado, os beneficios continuam disponiveis ate o encerramento do ciclo ja pago.',
                'Esse comportamento evita surpresa e respeita o periodo de uso ja contratado. Depois do fim do ciclo, o projeto segue para o estado/plano aplicavel conforme a regra vigente no painel.',
            ],
        },
        {
            heading: 'Base legal e transparencia',
            body: [
                'Esta documentacao descreve regras operacionais do produto e nao substitui contrato, termos de uso ou aconselhamento juridico. Em relacao a dados pessoais, a Wasm Cloud observa principios da Lei Geral de Protecao de Dados, como finalidade, necessidade, transparencia e seguranca.',
                'O usuario deve conseguir entender o que esta contratando, como cancelar e quais beneficios permanecem ativos durante o periodo pago. Essa clareza e parte essencial de uma relacao de consumo transparente.',
            ],
        },
    ],
};
