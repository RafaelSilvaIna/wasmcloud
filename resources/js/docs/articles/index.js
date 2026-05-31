import { subscriptionsArticle } from './assinaturas.js';
import { usageBillingArticle } from './cobrancaConsumo.js';
import { paymentProcessingArticle } from './processamentoPagamentos.js';
import { minorsArticle } from './menoresDeIdade.js';
import { workspacesArticle } from './workspaces.js';

export const docsArticles = [
    workspacesArticle,
    subscriptionsArticle,
    usageBillingArticle,
    paymentProcessingArticle,
    minorsArticle,
];

export function withArticleLinks(baseUrl) {
    const cleanBaseUrl = String(baseUrl || '/documentacao').replace(/\/$/, '');

    return docsArticles.map((article) => ({
        ...article,
        href: `${cleanBaseUrl}/${article.id}`,
    }));
}
