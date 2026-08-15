import React, { useState, useMemo } from 'react';
import { View, Text, ScrollView, Image, TouchableOpacity, TextInput, Alert, StyleSheet, Linking } from 'react-native';
import { SvgUri } from 'react-native-svg';
import { Colors, API_BASE_URL } from '../constants/config';
import { formatMoney, formatDays, formatRate, categoryLabel, borrowerLabel, calculateLoan } from '../utils/format';
import { submitReview } from '../api/endpoints';
import type { Offer } from '../api/types';
import { useFavorites } from '../hooks/useFavorites';
import { trackOfferApply, trackCalculatorUse, trackFavoriteAdd } from '../api/tracker';

interface Props { route: any; navigation: any; }

function normalizeImageUrl(url: string): string {
  if (!url) return '';
  if (url.startsWith('http')) return url;
  let n = url;
  if (n.startsWith('/public/')) n = n.substring(7);
  return `${API_BASE_URL}${n}`;
}

export default function OfferDetailScreen({ route, navigation }: Props) {
  const offer: Offer = route.params?.offer;
  const { isFavorite, toggleFavorite } = useFavorites();

  // Калькулятор — предзаполнен данными оффера
  const defaultAmount = offer ? Math.min(offer.amount_max, Math.max(offer.amount_min, 30000)) : 30000;
  const defaultTerm = offer ? Math.min(offer.term_max_days, Math.max(offer.term_min_days, 30)) : 30;
  const defaultRate = offer ? offer.rate : 1;
  const [calcAmount, setCalcAmount] = useState(String(defaultAmount));
  const [calcTerm, setCalcTerm] = useState(String(defaultTerm));
  const calcResult = useMemo(() => {
    const a = parseInt(calcAmount) || 0;
    const t = parseInt(calcTerm) || 0;
    const rateUnit = offer?.rate_unit || 'day';
    const dailyRate = rateUnit === 'year' ? defaultRate / 365 : defaultRate;
    return calculateLoan(a, t, dailyRate);
  }, [calcAmount, calcTerm, defaultRate]);

  // Отзыв
  const [reviewName, setReviewName] = useState('');
  const [reviewRating, setReviewRating] = useState(5);
  const [reviewComment, setReviewComment] = useState('');
  const [submitting, setSubmitting] = useState(false);

  if (!offer) {
    return (
      <View style={styles.errorContainer}>
        <Text style={{ fontSize: 18, color: Colors.textSecondary }}>Предложение не найдено</Text>
        <TouchableOpacity onPress={() => navigation.goBack()}><Text style={{ color: Colors.primary, marginTop: 16 }}>← Назад</Text></TouchableOpacity>
      </View>
    );
  }

  const logoUrl = normalizeImageUrl(offer.logo_url || '');
  const rateUnit = offer.rate_unit || 'day';

  const handleApply = () => { trackOfferApply(offer.id, offer.title); Linking.openURL(`${API_BASE_URL}/click/${offer.id}`); };

  const handleSubmitReview = async () => {
    if (!reviewName.trim() || !reviewComment.trim()) { Alert.alert('Ошибка', 'Заполните имя и комментарий'); return; }
    setSubmitting(true);
    try {
      const result = await submitReview(offer.id, reviewName, reviewRating, reviewComment);
      Alert.alert('Успешно', result.message);
      setReviewName(''); setReviewComment(''); setReviewRating(5);
    } catch (e: any) { Alert.alert('Ошибка', e.message || 'Не удалось отправить'); }
    finally { setSubmitting(false); }
  };

  const metrics = [];
  if (offer.category !== 'debit_cards') metrics.push({ label: offer.category === 'credit_cards' ? 'Лимит' : 'Сумма', value: `${formatMoney(offer.amount_min)} — ${formatMoney(offer.amount_max)}` });
  if (offer.category !== 'credit_cards' && offer.category !== 'debit_cards') metrics.push({ label: 'Срок', value: `${formatDays(offer.term_min_days)} — ${formatDays(offer.term_max_days)}` });
  if (offer.category !== 'debit_cards') { metrics.push({ label: 'Ставка', value: formatRate(offer.rate, rateUnit) }); metrics.push({ label: 'ПСК', value: `${offer.psk}%` }); }
  if (offer.free_term_days > 0) metrics.push({ label: 'Без %', value: formatDays(offer.free_term_days) });
  if (offer.borrower_category && offer.borrower_category !== 'any') metrics.push({ label: 'Заёмщик', value: borrowerLabel(offer.borrower_category) });

  return (
    <ScrollView style={styles.container} showsVerticalScrollIndicator={false}>
      {/* Главная карточка */}
      <View style={styles.card}>
        <View style={styles.header}>
          <View style={styles.logoWrap}>
            {logoUrl && logoUrl.endsWith('.svg') ? <SvgUri uri={logoUrl} width={60} height={60} /> : logoUrl ? <Image source={{ uri: logoUrl }} style={{ width: 60, height: 60 }} resizeMode="contain" /> : <Text style={{ fontSize: 36 }}>🏦</Text>}
          </View>
          <View style={{ flex: 1 }}>
            <Text style={styles.title}>{offer.title}</Text>
            <Text style={styles.subtitle}>{categoryLabel(offer.category)}</Text>
          </View>
        </View>

        {offer.rating > 0 && (
          <View style={styles.ratingRow}>
            <Text style={{ fontSize: 16, color: Colors.star }}>{'★'.repeat(Math.round(offer.rating))}{'☆'.repeat(5 - Math.round(offer.rating))}</Text>
            <Text style={{ fontSize: 14, fontWeight: '700', color: Colors.text }}>{Number(offer.rating).toFixed(1)}</Text>
            {offer.review_count > 0 && <Text style={{ fontSize: 13, color: Colors.textSecondary }}>({offer.review_count})</Text>}
          </View>
        )}

        <View style={styles.metricsGrid}>
          {metrics.map((m, i) => (
            <View key={i} style={styles.metric}>
              <Text style={styles.metricLabel}>{m.label}</Text>
              <Text style={styles.metricValue}>{m.value}</Text>
            </View>
          ))}
        </View>

        {offer.description ? <Text style={styles.description}>{offer.description}</Text> : null}

        <TouchableOpacity style={styles.favBtn} onPress={() => toggleFavorite(offer.id)}>
          <Text style={styles.favBtnText}>{isFavorite(offer.id) ? '❤️ В избранном' : '🤍 В избранное'}</Text>
        </TouchableOpacity>
        <TouchableOpacity style={styles.applyBtn} onPress={handleApply}>
          <Text style={styles.applyBtnText}>Оформить заявку →</Text>
        </TouchableOpacity>
      </View>

      {/* Калькулятор по условиям оффера */}
      <View style={styles.card}>
        <Text style={styles.sectionTitle}>🧮 Калькулятор {offer.title}</Text>
        <Text style={styles.calcHint}>Ставка: {formatRate(offer.rate, rateUnit, false)}</Text>

        <View style={styles.calcRow}>
          <View style={{ flex: 1 }}>
            <Text style={styles.calcLabel}>Сумма (₽)</Text>
            <TextInput style={styles.calcInput} value={calcAmount} onChangeText={setCalcAmount} keyboardType="numeric" />
            <Text style={styles.calcRange}>{formatMoney(offer.amount_min)} — {formatMoney(offer.amount_max)}</Text>
          </View>
          <View style={{ flex: 1 }}>
            <Text style={styles.calcLabel}>Срок (дней)</Text>
            <TextInput style={styles.calcInput} value={calcTerm} onChangeText={setCalcTerm} keyboardType="numeric" />
            <Text style={styles.calcRange}>{offer.term_min_days} — {offer.term_max_days} дн.</Text>
          </View>
        </View>

        <View style={styles.calcResults}>
          <View style={styles.calcResultItem}>
            <Text style={styles.calcResultLabel}>К возврату</Text>
            <Text style={styles.calcResultValue}>{formatMoney(calcResult.total)}</Text>
          </View>
          <View style={styles.calcResultItem}>
            <Text style={styles.calcResultLabel}>Переплата</Text>
            <Text style={[styles.calcResultValue, { color: Colors.error }]}>{formatMoney(calcResult.overpay)}</Text>
          </View>
          <View style={styles.calcResultItem}>
            <Text style={styles.calcResultLabel}>В день</Text>
            <Text style={styles.calcResultValue}>{formatMoney(calcResult.daily)}</Text>
          </View>
          <View style={styles.calcResultItem}>
            <Text style={styles.calcResultLabel}>Годовых</Text>
            <Text style={styles.calcResultValue}>{calcResult.yearlyRate.toFixed(1)}%</Text>
          </View>
        </View>
      </View>

      {/* Отзыв */}
      <View style={styles.card}>
        <Text style={styles.sectionTitle}>Оставить отзыв</Text>
        <TextInput style={styles.input} value={reviewName} onChangeText={setReviewName} placeholder="Ваше имя" placeholderTextColor={Colors.textMuted} />
        <View style={styles.starsRow}>
          {[1,2,3,4,5].map(s => (
            <TouchableOpacity key={s} onPress={() => setReviewRating(s)}>
              <Text style={{ fontSize: 32, color: s <= reviewRating ? Colors.star : Colors.border }}>{s <= reviewRating ? '★' : '☆'}</Text>
            </TouchableOpacity>
          ))}
        </View>
        <TextInput style={[styles.input, { minHeight: 80 }]} value={reviewComment} onChangeText={setReviewComment} placeholder="Ваш отзыв..." placeholderTextColor={Colors.textMuted} multiline textAlignVertical="top" />
        <TouchableOpacity style={[styles.submitBtn, submitting && { opacity: 0.5 }]} onPress={handleSubmitReview} disabled={submitting}>
          <Text style={styles.submitBtnText}>{submitting ? 'Отправка...' : 'Отправить'}</Text>
        </TouchableOpacity>
      </View>
      <View style={{ height: 40 }} />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: Colors.background },
  errorContainer: { flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: Colors.background },
  card: { backgroundColor: Colors.white, margin: 16, marginBottom: 0, borderRadius: 20, padding: 20, borderWidth: 1, borderColor: Colors.surfaceBorder },
  header: { flexDirection: 'row', alignItems: 'center', gap: 16, marginBottom: 16 },
  logoWrap: { width: 72, height: 72, borderRadius: 16, backgroundColor: Colors.borderLight, alignItems: 'center', justifyContent: 'center', overflow: 'hidden' },
  title: { fontSize: 22, fontWeight: '800', color: Colors.text, lineHeight: 28 },
  subtitle: { fontSize: 13, color: Colors.textSecondary, marginTop: 4 },
  ratingRow: { flexDirection: 'row', alignItems: 'center', gap: 6, marginBottom: 16 },
  metricsGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: 10, marginBottom: 16 },
  metric: { backgroundColor: Colors.background, borderRadius: 12, padding: 12, width: '47%' as any },
  metricLabel: { fontSize: 11, color: Colors.textSecondary, textTransform: 'uppercase' },
  metricValue: { fontSize: 15, fontWeight: '700', color: Colors.text, marginTop: 4 },
  description: { fontSize: 15, color: Colors.textSecondary, lineHeight: 22, marginBottom: 16 },
  favBtn: { borderWidth: 1, borderColor: Colors.border, borderRadius: 12, paddingVertical: 14, alignItems: 'center', marginBottom: 10 },
  favBtnText: { fontSize: 15, fontWeight: '600', color: Colors.text },
  applyBtn: { backgroundColor: Colors.accent, borderRadius: 12, paddingVertical: 16, alignItems: 'center' },
  applyBtnText: { color: Colors.white, fontSize: 16, fontWeight: '700' },
  sectionTitle: { fontSize: 18, fontWeight: '700', color: Colors.text, marginBottom: 12 },
  calcHint: { fontSize: 13, color: Colors.textSecondary, marginBottom: 16 },
  calcRow: { flexDirection: 'row', gap: 12, marginBottom: 16 },
  calcLabel: { fontSize: 13, fontWeight: '500', color: Colors.textSecondary, marginBottom: 6 },
  calcInput: { backgroundColor: Colors.background, borderWidth: 1, borderColor: Colors.border, borderRadius: 10, paddingHorizontal: 14, paddingVertical: 12, fontSize: 17, fontWeight: '600', color: Colors.text },
  calcRange: { fontSize: 11, color: Colors.textMuted, marginTop: 4 },
  calcResults: { backgroundColor: Colors.background, borderRadius: 14, padding: 16, flexDirection: 'row', flexWrap: 'wrap', gap: 12 },
  calcResultItem: { width: '46%' as any },
  calcResultLabel: { fontSize: 11, color: Colors.textSecondary, textTransform: 'uppercase' },
  calcResultValue: { fontSize: 17, fontWeight: '700', color: Colors.text, marginTop: 2 },
  input: { backgroundColor: Colors.background, borderWidth: 1, borderColor: Colors.border, borderRadius: 10, paddingHorizontal: 14, paddingVertical: 12, fontSize: 15, color: Colors.text, marginBottom: 12 },
  starsRow: { flexDirection: 'row', gap: 8, marginBottom: 12 },
  submitBtn: { backgroundColor: Colors.primary, borderRadius: 12, paddingVertical: 14, alignItems: 'center', marginTop: 4 },
  submitBtnText: { color: Colors.white, fontSize: 15, fontWeight: '700' },
});
