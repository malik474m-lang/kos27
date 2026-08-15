// Типы данных, соответствующие структуре MySQL базы kosmozaim.ru

export interface Offer {
  id: number;
  title: string;
  slug: string;
  description: string;
  category: 'microloans' | 'credits' | 'credit_cards' | 'debit_cards';
  amount_min: number;
  amount_max: number;
  term_min_days: number;
  term_max_days: number;
  rate: number;
  rate_unit: 'day' | 'year';
  psk: number;
  free_term_days: number;
  rating: number;
  review_count: number;
  logo_url: string;
  affiliate_url: string;
  is_active: number;
  sort_order: number;
  borrower_category: string;
  extra_fields?: string;
  display_fields?: string;
  created_at: string;
  updated_at: string;
}

export interface Article {
  id: number;
  title: string;
  slug: string;
  excerpt: string;
  content: string;
  meta_title: string;
  meta_description: string;
  cover_image: string;
  is_published: number;
  created_at: string;
  updated_at: string;
}

export interface Review {
  id: number;
  offer_id: number;
  author_name: string;
  rating: number;
  comment: string;
  is_approved: number;
  created_at: string;
}

export interface Tag {
  id: number;
  title: string;
  slug: string;
  category: string;
  icon: string;
  is_active: number;
  sort_order: number;
}

export interface City {
  slug: string;
  name: string;
  prep: string;
  region: string;
}

export interface GeoInfo {
  city: string;
  slug: string | null;
  region: string;
  country: string;
  detected: boolean;
}

export interface GiveawayInfo {
  id: number;
  title: string;
  prize_amount: number;
  start_at: string;
  end_at: string;
  draw_at: string;
  status: string;
  entries_count: number;
}

export interface UserProfile {
  id: number;
  email: string;
  name: string;
  created_at: string;
}

export interface UserApplication {
  id: number;
  offer_id: number;
  offer_title: string;
  offer_slug: string;
  logo_url: string;
  created_at: string;
  status?: string;
}

export interface FAQ {
  q: string;
  a: string;
}

export interface CalculatorResult {
  amount: number;
  term: number;
  rate: number;
  total: number;
  overpay: number;
  daily: number;
  yearlyRate: number;
}
