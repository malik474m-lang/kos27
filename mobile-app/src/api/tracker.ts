import { API_BASE_URL } from '../constants/config';

let sessionId = 'app_' + Date.now() + '_' + Math.random().toString(36).slice(2, 8);

export function trackEvent(event: string, data?: Record<string, any>) {
  try {
    fetch(`${API_BASE_URL}/api/app-track`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ event, sessionId, ...data }),
    }).catch(() => {});
  } catch (e) {}
}

export function trackAppOpen() { trackEvent('app_open'); }
export function trackPageView(screen: string) { trackEvent('page_view', { screen }); }
export function trackOfferClick(offerId: number, offerTitle: string) { trackEvent('offer_click', { offerId, offerTitle }); }
export function trackOfferApply(offerId: number, offerTitle: string) { trackEvent('offer_apply', { offerId, offerTitle }); }
export function trackArticleView(articleId: number, articleTitle: string) { trackEvent('article_view', { articleId, articleTitle }); }
export function trackCalculatorUse() { trackEvent('calculator_use'); }
export function trackFavoriteAdd(offerId: number, offerTitle: string) { trackEvent('favorite_add', { offerId, offerTitle }); }
export function trackCategoryView(category: string) { trackEvent('category_view', { category }); }
