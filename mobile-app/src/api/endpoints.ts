import { api } from './client';
import type { Offer, Article, GeoInfo, GiveawayInfo, Review } from './types';

// === Публичные API (соответствуют PHP /api/...) ===

/** Получить все активные офферы */
export async function getOffers(category?: string): Promise<Offer[]> {
  const params: Record<string, string> = {};
  if (category) params.category = category;
  return api.get<Offer[]>('/api/offers', params);
}

/** Получить офферы по ID */
export async function getOffersByIds(ids: number[]): Promise<Offer[]> {
  return api.get<Offer[]>('/api/offers', { ids: ids.join(',') });
}

/** Получить гео-информацию */
export async function getGeoInfo(): Promise<GeoInfo> {
  return api.get<GeoInfo>('/api/geo');
}

/** Активный розыгрыш */
export async function getActiveGiveaway(): Promise<GiveawayInfo | null> {
  return api.get<GiveawayInfo | null>('/api/giveaway/active');
}

/** Подписка на рассылку */
export async function subscribe(email: string): Promise<{ success: boolean; message: string }> {
  return api.post('/api/subscribe', { email, source: 'mobile_app' });
}

/** Отправить отзыв */
export async function submitReview(offerId: number, authorName: string, rating: number, comment: string): Promise<{ success: boolean; message: string }> {
  return api.post('/api/reviews', { offerId, authorName, rating, comment });
}

// === User API ===

/** Регистрация */
export async function registerUser(email: string, password: string, name: string): Promise<{ success: boolean; message: string }> {
  return api.post('/api/user/register', {
    email,
    password,
    name,
    agreedTerms: true,
    agreedMarketing: true,
    agreedFinance: true,
  });
}

/** Верификация кода */
export async function verifyUser(email: string, code: string): Promise<{ success: boolean }> {
  return api.post('/api/user/verify', { email, code });
}

/** Логин */
export async function loginUser(email: string, password: string): Promise<{ success: boolean; name: string }> {
  return api.post('/api/user/login', { email, password });
}

/** Выход */
export async function logoutUser(): Promise<{ success: boolean }> {
  return api.post('/api/user/logout');
}

/** Профиль */
export async function getUserProfile(): Promise<{
  profile: { id: number; email: string; name: string; created_at: string };
  applications: Array<{
    id: number;
    offer_id: number;
    offer_title: string;
    offer_slug: string;
    logo_url: string;
    created_at: string;
  }>;
}> {
  return api.get('/api/user/profile');
}

// === Прямые URL для WebView/Deep Linking ===

export function getOfferUrl(slug: string): string {
  return `${api.getImageUrl('')}/offer/${slug}`;
}

export function getClickUrl(offerId: number): string {
  return `${api.getImageUrl('')}/click/${offerId}`;
}

export function getArticleUrl(slug: string): string {
  return `${api.getImageUrl('')}/articles/${slug}`;
}
