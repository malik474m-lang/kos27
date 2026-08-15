// Утилиты форматирования — аналоги PHP функций из config.php

/** Форматирование суммы: 50000 → "50 000 ₽" */
export function formatMoney(amount: number): string {
  return Math.round(amount).toLocaleString('ru-RU') + ' ₽';
}

/** Форматирование дней: 30 → "30 дней" */
export function formatDays(days: number): string {
  if (days <= 0) return '0 дней';
  if (days % 365 === 0) {
    const y = days / 365;
    if (y === 1) return '1 год';
    if (y < 5) return `${y} года`;
    return `${y} лет`;
  }
  if (days % 30 === 0) {
    const m = days / 30;
    if (m === 1) return '1 месяц';
    if (m < 5) return `${m} месяца`;
    return `${m} месяцев`;
  }
  if (days === 1) return '1 день';
  if (days < 5) return `${days} дня`;
  return `${days} дней`;
}

/** Единица ставки */
export function getRateUnitLabel(unit: string): string {
  return unit === 'year' ? 'в год' : 'в день';
}

/** Полное отображение ставки */
export function formatRate(rate: number, unit: string, withFrom = true): string {
  const prefix = withFrom ? 'от ' : '';
  return `${prefix}${rate}% ${getRateUnitLabel(unit)}`;
}

/** Форматирование даты: "2026-07-21 17:04:08" → "21.07.2026" */
export function formatDate(dateStr: string): string {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  const day = String(d.getDate()).padStart(2, '0');
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const year = d.getFullYear();
  return `${day}.${month}.${year}`;
}

/** Сокращение текста */
export function truncate(text: string, maxLen: number): string {
  if (!text || text.length <= maxLen) return text || '';
  return text.substring(0, maxLen).trimEnd() + '...';
}

/** Категория на русском */
export function categoryLabel(category: string): string {
  const map: Record<string, string> = {
    microloans: 'Займы',
    credits: 'Кредиты',
    credit_cards: 'Кредитные карты',
    debit_cards: 'Дебетовые карты',
  };
  return map[category] || category;
}

/** Категория заёмщика на русском */
export function borrowerLabel(category: string): string {
  const map: Record<string, string> = {
    employed: 'Работающий',
    unemployed: 'Безработный',
    pensioner: 'Пенсионер',
    student: 'Студент',
    self_employed: 'Самозанятый',
    any: 'Все',
  };
  return map[category] || category;
}

/** Расчёт калькулятора */
export function calculateLoan(amount: number, termDays: number, ratePerDay: number) {
  const interest = (amount * ratePerDay * termDays) / 100;
  const total = amount + interest;
  const daily = termDays > 0 ? total / termDays : 0;
  const yearlyRate = ratePerDay * 365;
  return { total, overpay: interest, daily, yearlyRate };
}

/** Удаление HTML/markdown тегов из текста (для отображения контента статей) */
export function stripHtml(html: string): string {
  if (!html) return '';
  return html
    .replace(/<[^>]*>/g, '')
    .replace(/\*\*/g, '')
    .replace(/#{1,6}\s/g, '')
    .replace(/\n{3,}/g, '\n\n')
    .trim();
}
