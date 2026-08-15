// Базовый URL API сайта kosmozaim.ru
// В продакшене указывается реальный домен
export const API_BASE_URL = 'https://kosmozaim.ru';

// Цвета приложения (соответствуют сайту)
export const Colors = {
  primary: '#1a56db',
  primaryDark: '#1244af',
  accent: '#059669',
  accentDark: '#047857',
  purple: '#7e3af2',
  background: '#f9fafb',
  surface: '#ffffff',
  surfaceBorder: '#f1f5f9',
  text: '#111827',
  textSecondary: '#6b7280',
  textMuted: '#9ca3af',
  border: '#e5e7eb',
  borderLight: '#f3f4f6',
  success: '#10b981',
  warning: '#f59e0b',
  error: '#ef4444',
  star: '#eab308',
  white: '#ffffff',
  black: '#000000',
  gradientStart: '#1a56db',
  gradientEnd: '#7e3af2',
};

// Категории финансовых продуктов
export const CATEGORIES = {
  microloans: { key: 'microloans', title: 'Займы', icon: '💵', path: '/zajmy' },
  credits: { key: 'credits', title: 'Кредиты', icon: '🏦', path: '/kredity' },
  credit_cards: { key: 'credit_cards', title: 'Кредитные карты', icon: '💳', path: '/karty/kreditnye' },
  debit_cards: { key: 'debit_cards', title: 'Дебетовые карты', icon: '🪪', path: '/karty/debetovye' },
} as const;

export type CategoryKey = keyof typeof CATEGORIES;

// Категории заёмщиков
export const BORROWER_CATEGORIES = [
  { key: '', label: 'Все категории' },
  { key: 'employed', label: 'Работающий' },
  { key: 'unemployed', label: 'Безработный' },
  { key: 'pensioner', label: 'Пенсионер' },
  { key: 'student', label: 'Студент' },
  { key: 'self_employed', label: 'Самозанятый' },
];
