import { subscriptionsArticle } from './assinaturas.js';
import { usageBillingArticle } from './cobrancaConsumo.js';
import { paymentProcessingArticle } from './processamentoPagamentos.js';

export const docsArticles = [
    subscriptionsArticle,
    usageBillingArticle,
    paymentProcessingArticle,
];

export function withArticleLinks(baseUrl) {
    const cleanBaseUrl = String(baseUrl || '/documentacao').replace(/\/$/, '');

    return docsArticles.map((article) => ({
        ...article,
        href: `${cleanBaseUrl}/${article.id}`,
    }));
}
