import React, { useState } from 'react';
import {
  View,
  Text,
  ScrollView,
  Image,
  TouchableOpacity,
  TextInput,
  Alert,
  StyleSheet,
  Linking,
} from 'react-native';
import { Colors, API_BASE_URL } from '@/constants/config';
import { formatMoney, formatDays, formatRate, categoryLabel, borrowerLabel } from '@/utils/format';
import { submitReview } from '@/api/endpoints';
import type { Offer } from '@/api/types';
import { useFavorites } from '@/hooks/useFavorites';

interface OfferDetailScreenProps {
  route: any;
  navigation: any;
}

function normalizeImageUrl(url: string): string {
  if (!url) return '';
  if (url.startsWith('http')) return url;
  let n = url;
  if (n.startsWith('/public/')) n = n.substring(7);
  return `${API_BASE_URL}${n}`;
}

export default function OfferDetailScreen({ route, navigation }: OfferDetailScreenProps) {
  const offer: Offer = route.params?.offer;
  const { isFavorite, toggleFavorite } = useFavorites();

  // Форма отзыва
  const [reviewName, setReviewName] = useState('');
  const [reviewRating, setReviewRating] = useState(5);
  const [reviewComment, setReviewComment] = useState('');
  const [submitting, setSubmitting] = useState(false);

  if (!offer) {
    return (
      <View style={styles.errorContainer}>
        <Text style={styles.errorText}>Предложение не найдено</Text>
        <TouchableOpacity onPress={() => navigation.goBack()}>
          <Text style={styles.goBackLink}>← Назад</Text>
        </TouchableOpacity>
      </View>
    );
  }

  const logoUrl = normalizeImageUrl(offer.logo_url || '');
  const rateUnit = offer.rate_unit || 'day';

  const handleApply = () => {
    Linking.openURL(`${API_BASE_URL}/click/${offer.id}`);
  };

  const handleSubmitReview = async () => {
    if (!reviewName.trim() || !reviewComment.trim()) {
      Alert.alert('Ошибка', 'Заполните имя и комментарий');
      return;
    }
    setSubmitting(true);
    try {
      const result = await submitReview(offer.id, reviewName, reviewRating, reviewComment);
      Alert.alert('Успешно', result.message);
      setReviewName('');
      setReviewComment('');
      setReviewRating(5);
    } catch (e: any) {
      Alert.alert('Ошибка', e.message || 'Не удалось отправить отзыв');
    } finally {
      setSubmitting(false);
    }
  };

  // Метрики
  const metrics = [];
  if (offer.category !== 'debit_cards') {
    metrics.push({ label: offer.category === 'credit_cards' ? 'Лимит' : 'Сумма', value: `${formatMoney(offer.amount_min)} — ${formatMoney(offer.amount_max)}` });
  }
  if (offer.category !== 'credit_cards' && offer.category !== 'debit_cards') {
    metrics.push({ label: 'Срок', value: `${formatDays(offer.term_min_days)} — ${formatDays(offer.term_max_days)}` });
  }
  if (offer.category !== 'debit_cards') {
    metrics.push({ label: 'Ставка', value: formatRate(offer.rate, rateUnit) });
    metrics.push({ label: 'ПСК', value: `${offer.psk}%` });
  }
  if (offer.free_term_days > 0) {
    metrics.push({ label: 'Льготный период', value: formatDays(offer.free_term_days) });
  }
  if (offer.borrower_category && offer.borrower_category !== 'any') {
    metrics.push({ label: 'Заёмщик', value: borrowerLabel(offer.borrower_category) });
  }

  return (
    <ScrollView style={styles.container} showsVerticalScrollIndicator={false}>
      {/* Главная карточка */}
      <View style={styles.mainCard}>
        <View style={styles.header}>
          <View style={styles.logoWrap}>
            {logoUrl ? (
              <Image source={{ uri: logoUrl }} style={styles.logo} resizeMode="contain" />
            ) : (
              <Text style={styles.logoFallback}>🏦</Text>
            )}
          </View>
          <View style={styles.titleWrap}>
            <Text style={styles.title}>{offer.title}</Text>
            <Text style={styles.category}>{categoryLabel(offer.category)}</Text>
          </View>
        </View>

        {/* Рейтинг */}
        {offer.rating > 0 && (
          <View style={styles.ratingRow}>
            <Text style={styles.ratingStars}>
              {'★'.repeat(Math.round(offer.rating))}{'☆'.repeat(5 - Math.round(offer.rating))}
            </Text>
            <Text style={styles.ratingValue}>{Number(offer.rating).toFixed(1)}</Text>
            {offer.review_count > 0 && (
              <Text style={styles.reviewCount}>({offer.review_count} отзывов)</Text>
            )}
          </View>
        )}

        {/* Метрики */}
        <View style={styles.metricsGrid}>
          {metrics.map((m, i) => (
            <View key={i} style={styles.metric}>
              <Text style={styles.metricLabel}>{m.label}</Text>
              <Text style={styles.metricValue}>{m.value}</Text>
            </View>
          ))}
        </View>

        {/* Описание */}
        {offer.description ? (
          <View style={styles.descriptionBlock}>
            <Text style={styles.descriptionTitle}>Описание</Text>
            <Text style={styles.descriptionText}>{offer.description}</Text>
          </View>
        ) : null}

        {/* Кнопки */}
        <View style={styles.actionRow}>
          <TouchableOpacity
            style={styles.favButton}
            onPress={() => toggleFavorite(offer.id)}
          >
            <Text style={styles.favButtonText}>
              {isFavorite(offer.id) ? '❤️ В избранном' : '🤍 В избранное'}
            </Text>
          </TouchableOpacity>
          <TouchableOpacity style={styles.applyButton} onPress={handleApply}>
            <Text style={styles.applyButtonText}>Оформить заявку →</Text>
          </TouchableOpacity>
        </View>
      </View>

      {/* Калькулятор */}
      <TouchableOpacity
        style={styles.calcBanner}
        onPress={() => navigation.navigate('Calculator')}
      >
        <Text style={styles.calcBannerIcon}>🧮</Text>
        <View style={styles.calcBannerContent}>
          <Text style={styles.calcBannerTitle}>Рассчитать стоимость</Text>
          <Text style={styles.calcBannerSub}>Калькулятор займа</Text>
        </View>
        <Text style={styles.calcBannerArrow}>→</Text>
      </TouchableOpacity>

      {/* Форма отзыва */}
      <View style={styles.reviewForm}>
        <Text style={styles.sectionTitle}>Оставить отзыв</Text>

        <Text style={styles.inputLabel}>Ваше имя</Text>
        <TextInput
          style={styles.input}
          value={reviewName}
          onChangeText={setReviewName}
          placeholder="Иван"
          placeholderTextColor={Colors.textMuted}
        />

        <Text style={styles.inputLabel}>Оценка</Text>
        <View style={styles.starsRow}>
          {[1, 2, 3, 4, 5].map(star => (
            <TouchableOpacity key={star} onPress={() => setReviewRating(star)}>
              <Text style={[styles.star, star <= reviewRating && styles.starActive]}>
                {star <= reviewRating ? '★' : '☆'}
              </Text>
            </TouchableOpacity>
          ))}
        </View>

        <Text style={styles.inputLabel}>Комментарий</Text>
        <TextInput
          style={[styles.input, styles.textArea]}
          value={reviewComment}
          onChangeText={setReviewComment}
          placeholder="Поделитесь опытом..."
          placeholderTextColor={Colors.textMuted}
          multiline
          numberOfLines={4}
          textAlignVertical="top"
        />

        <TouchableOpacity
          style={[styles.submitBtn, submitting && styles.submitBtnDisabled]}
          onPress={handleSubmitReview}
          disabled={submitting}
        >
          <Text style={styles.submitBtnText}>
            {submitting ? 'Отправка...' : 'Отправить отзыв'}
          </Text>
        </TouchableOpacity>
      </View>

      <View style={{ height: 40 }} />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: Colors.background,
  },
  errorContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: Colors.background,
  },
  errorText: {
    fontSize: 18,
    color: Colors.textSecondary,
  },
  goBackLink: {
    fontSize: 16,
    color: Colors.primary,
    marginTop: 16,
  },
  mainCard: {
    backgroundColor: Colors.white,
    margin: 16,
    borderRadius: 20,
    padding: 20,
    borderWidth: 1,
    borderColor: Colors.surfaceBorder,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.05,
    shadowRadius: 8,
    elevation: 2,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 16,
    marginBottom: 16,
  },
  logoWrap: {
    width: 72,
    height: 72,
    borderRadius: 16,
    backgroundColor: Colors.borderLight,
    alignItems: 'center',
    justifyContent: 'center',
    overflow: 'hidden',
  },
  logo: {
    width: 60,
    height: 60,
  },
  logoFallback: {
    fontSize: 36,
  },
  titleWrap: {
    flex: 1,
  },
  title: {
    fontSize: 22,
    fontWeight: '800',
    color: Colors.text,
    lineHeight: 28,
  },
  category: {
    fontSize: 13,
    color: Colors.textSecondary,
    marginTop: 4,
  },
  ratingRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginBottom: 16,
  },
  ratingStars: {
    fontSize: 16,
    color: Colors.star,
  },
  ratingValue: {
    fontSize: 14,
    fontWeight: '700',
    color: Colors.text,
  },
  reviewCount: {
    fontSize: 13,
    color: Colors.textSecondary,
  },
  metricsGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
    marginBottom: 16,
  },
  metric: {
    backgroundColor: Colors.background,
    borderRadius: 12,
    padding: 14,
    minWidth: '46%' as any,
    flex: 1,
  },
  metricLabel: {
    fontSize: 11,
    color: Colors.textSecondary,
    textTransform: 'uppercase',
    letterSpacing: 0.5,
  },
  metricValue: {
    fontSize: 16,
    fontWeight: '700',
    color: Colors.text,
    marginTop: 4,
  },
  descriptionBlock: {
    marginTop: 4,
    marginBottom: 16,
  },
  descriptionTitle: {
    fontSize: 16,
    fontWeight: '700',
    color: Colors.text,
    marginBottom: 8,
  },
  descriptionText: {
    fontSize: 15,
    color: Colors.textSecondary,
    lineHeight: 22,
  },
  actionRow: {
    gap: 12,
  },
  favButton: {
    borderWidth: 1,
    borderColor: Colors.border,
    borderRadius: 12,
    paddingVertical: 14,
    alignItems: 'center',
  },
  favButtonText: {
    fontSize: 15,
    fontWeight: '600',
    color: Colors.text,
  },
  applyButton: {
    backgroundColor: Colors.accent,
    borderRadius: 12,
    paddingVertical: 16,
    alignItems: 'center',
    marginTop: 8,
  },
  applyButtonText: {
    color: Colors.white,
    fontSize: 16,
    fontWeight: '700',
  },
  calcBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: Colors.white,
    marginHorizontal: 16,
    marginTop: 4,
    padding: 16,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: Colors.surfaceBorder,
    gap: 12,
  },
  calcBannerIcon: {
    fontSize: 28,
  },
  calcBannerContent: {
    flex: 1,
  },
  calcBannerTitle: {
    fontSize: 15,
    fontWeight: '700',
    color: Colors.text,
  },
  calcBannerSub: {
    fontSize: 13,
    color: Colors.textSecondary,
  },
  calcBannerArrow: {
    fontSize: 18,
    color: Colors.primary,
    fontWeight: '700',
  },
  reviewForm: {
    backgroundColor: Colors.white,
    margin: 16,
    borderRadius: 20,
    padding: 20,
    borderWidth: 1,
    borderColor: Colors.surfaceBorder,
  },
  sectionTitle: {
    fontSize: 20,
    fontWeight: '700',
    color: Colors.text,
    marginBottom: 16,
  },
  inputLabel: {
    fontSize: 13,
    fontWeight: '500',
    color: Colors.textSecondary,
    marginBottom: 6,
    marginTop: 8,
  },
  input: {
    backgroundColor: Colors.background,
    borderWidth: 1,
    borderColor: Colors.border,
    borderRadius: 10,
    paddingHorizontal: 14,
    paddingVertical: 12,
    fontSize: 15,
    color: Colors.text,
  },
  textArea: {
    minHeight: 100,
  },
  starsRow: {
    flexDirection: 'row',
    gap: 8,
    marginBottom: 8,
  },
  star: {
    fontSize: 32,
    color: Colors.border,
  },
  starActive: {
    color: Colors.star,
  },
  submitBtn: {
    backgroundColor: Colors.primary,
    borderRadius: 12,
    paddingVertical: 14,
    alignItems: 'center',
    marginTop: 16,
  },
  submitBtnDisabled: {
    opacity: 0.5,
  },
  submitBtnText: {
    color: Colors.white,
    fontSize: 15,
    fontWeight: '700',
  },
});
