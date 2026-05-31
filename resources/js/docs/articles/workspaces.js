import { BriefcaseBusiness } from 'lucide-react';

export const workspacesArticle = {
    id: 'workspaces',
    category: 'Organizacao',
    title: 'Sobre Workspaces',
    excerpt: 'Entenda como workspaces organizam projetos, equipe, permissoes e modelo de pagamento.',
    icon: BriefcaseBusiness,
    content: [
        {
            heading: 'O que e um workspace',
            body: [
                'Um workspace e o ambiente principal onde o usuario organiza projetos dentro da Wasm Cloud. Ele funciona como uma camada de gestao: em vez de cada projeto ficar solto na conta, os projetos passam a pertencer a um workspace com regras, equipe e modelo de pagamento bem definidos.',
                'Essa estrutura ajuda usuarios individuais, equipes pequenas e empresas a separar contextos de trabalho. Um workspace pode representar uma empresa, um cliente, uma escola, uma equipe de desenvolvimento ou um conjunto de projetos relacionados.',
            ],
        },
        {
            heading: 'Modelo de plano aplicado ao workspace',
            body: [
                'O modelo de pagamento do workspace pode ser Micro, Medio ou Large. Quando um workspace esta configurado em um determinado modelo, esse modelo afeta todos os projetos dentro dele. Isso torna o controle financeiro mais previsivel, porque o usuario entende que os projetos daquele workspace compartilham a mesma base de beneficios.',
                'Essa decisao e diferente de tratar cada projeto como uma assinatura isolada. O objetivo e dar mais controle: o usuario escolhe o modelo do workspace e sabe que os projetos internos seguem essa politica ate que o modelo seja alterado.',
            ],
        },
        {
            heading: 'Equipe e permissoes',
            body: [
                'Pelo workspace, o usuario podera convidar membros da equipe para acessar os projetos daquele ambiente. Cada membro podera receber permissoes especificas, permitindo separar quem pode visualizar, editar, administrar projetos, acessar configuracoes, gerenciar pagamentos ou operar recursos sensiveis.',
                'A gestao de permissoes deve sempre seguir o principio de menor privilegio. Isso significa conceder apenas o acesso necessario para cada pessoa executar sua funcao dentro do workspace.',
            ],
        },
        {
            heading: 'Controle e organizacao',
            body: [
                'Workspaces tambem ajudam a separar responsabilidades. Um usuario pode ter um workspace para estudos, outro para clientes e outro para uma equipe de produto. Cada workspace guarda seu contexto e permite organizar projetos, membros, plano e configuracoes de forma independente.',
                'Por seguranca e simplicidade operacional, a conta tera limite de ate 8 workspaces. Esse limite ajuda a manter a experiencia organizada e evita criacoes abusivas.',
            ],
        },
    ],
};
