import { GraduationCap } from 'lucide-react';

export const minorsArticle = {
    id: 'menores-de-idade',
    category: 'Diretrizes',
    title: 'Menores de idade usando a Wasm Cloud',
    excerpt: 'Regras para uso educativo, limites de conta e proibicao de transacoes por menores.',
    icon: GraduationCap,
    content: [
        {
            heading: 'Uso permitido para fins educativos',
            body: [
                'Menores de idade podem usar a Wasm Cloud apenas para projetos escolares, estudos, blogs pessoais educativos, prototipos de aprendizagem, atividades academicas e exercicios tecnicos sem finalidade comercial.',
                'A plataforma deve ser usada de maneira segura, proporcional e acompanhada por responsaveis quando aplicavel. O objetivo e permitir aprendizado, publicacao educativa e experimentacao tecnica saudavel.',
            ],
        },
        {
            heading: 'O que menores de idade nao podem fazer',
            body: [
                'Menores de idade nao podem usar a Wasm Cloud para operar negocios, vender produtos ou servicos, receber pagamentos, processar transacoes, administrar SaaS comercial, hospedar marketplaces, capturar dados sensiveis, coletar documentos, operar conteudo adulto, apostas, jogos de azar, atividades financeiras, criptomoedas, conteudo ilegal, conteudo de odio, assedio, golpes, phishing, malware, scraping abusivo ou qualquer projeto que coloque terceiros em risco.',
                'Tambem nao e permitido que menores publiquem sistemas que exijam responsabilidade contratual, financeira, fiscal, trabalhista, medica, juridica, educacional formal ou qualquer outra area que dependa de capacidade legal plena ou autorizacao especifica.',
            ],
        },
        {
            heading: 'Transacoes sao proibidas para menores',
            body: [
                'Menores de idade nao podem realizar transacoes dentro da Wasm Cloud. Isso inclui contratar planos pagos, cadastrar cartoes, usar Pix, boleto, pagar consumo, receber pagamentos por projetos ou assumir qualquer obrigacao financeira dentro da plataforma.',
                'Quando houver necessidade de pagamento, a operacao devera ser feita por uma pessoa responsavel e legalmente apta, seguindo os termos comerciais vigentes.',
            ],
        },
        {
            heading: 'Encerramento por violacao das diretrizes',
            body: [
                'Caso seja detectado que uma conta de menor de idade esta relacionada a projeto proibido pelas diretrizes da Wasm Cloud, a conta podera ser encerrada imediatamente. Projetos associados tambem poderao ser pausados, removidos ou bloqueados para proteger usuarios, terceiros e a plataforma.',
                'A Wasm Cloud podera solicitar informacoes adicionais, aplicar restricoes preventivas e preservar registros necessarios para seguranca, auditoria e cumprimento de obrigacoes legais.',
            ],
        },
    ],
};
