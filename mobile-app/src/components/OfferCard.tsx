import React from 'react';
import {
  View,
  Text,
  Image,
  TouchableOpacity,
  StyleSheet,
  Linking,
} from 'react-native';
import { SvgUri } from 'react-native-svg';
import { Colors, API_BASE_URL } from '../constants/config';
import { formatMoney, formatDays, formatRate, truncate } from '../utils/format';
import type { Offer } from '../api/types';

interface OfferCardProps {
  offer: Offer;
  isFavorite?: boolean;
  onToggleFavorite?: (id: number) => void;
  onPress?: (offer: Offer) => void;
  onApply?: (offer: Offer) => void;
}

function normalizeImageUrl(url: string): string {
  if (!url) return '';
  if (url.startsWith('http')) return url;
  let normalized = url;
  if (normalized.startsWith('/public/')) normalized = normalized.substring(7);
  return `${API_BASE_URL}${normalized}`;
}

function OfferLogo({ url, title }: { url: string; title: string }) {
  if (!url) return <Text style={styles.logoFallback}>🏦</Text>;
  const fullUrl = normalizeImageUrl(url);
  if (fullUrl.endsWith('.svg')) {
    return <SvgUri uri={fullUrl} width={48} height={48} />;
  }
  return <Image source={{ uri: fullUrl }} style={styles.logo} resizeMode="contain" />;
}

export default function OfferCard({ offer, isFavorite, onToggleFavorite, onPress, onApply }: OfferCardProps) {
  const rateUnit = offer.rate_unit || 'day';
  const isCard = offer.category === 'credit_cards' || offer.category === 'debit_cards';
  const showAmount = offer.category !== 'debit_cards';
  const showTerm = !isCard;
  const showRate = offer.category !== 'debit_cards';
  const showPsk = offer.category !== 'debit_cards';
  const showFreeTerm = offer.free_term_days > 0;

  const handleApply = () => {
    if (onApply) {
      onApply(offer);
    } else {
      Linking.openURL(`${API_BASE_URL}/click/${offer.id}`);
    }
  };

  return (
    <TouchableOpacity style={styles.card} onPress={() => onPress?.(offer)} activeOpacity={0.7}>
      <View style={styles.header}>
        <View style={styles.logoWrap}>
          <OfferLogo url={offer.logo_url || ''} title={offer.title} />
        </View>
        <View style={styles.titleWrap}>
          <Text style={styles.title} numberOfLines={2}>{offer.title}</Text>
          <View style={styles.badges}>
            {offer.rating > 0 && (
              <View style={styles.ratingBadge}>
                <Text style={styles.ratingText}>★ {Number(offer.rating).toFixed(1)}</Text>
                {offer.review_count > 0 && <Text style={styles.reviewCount}> ({offer.review_count})</Text>}
              </View>
            )}
            {showFreeTerm && (
              <View style={styles.freeBadge}>
                <Text style={styles.freeText}>0% — {formatDays(offer.free_term_days)}</Text>
              </View>
            )}
          </View>
        </View>
      </View>

      <View style={styles.metricsGrid}>
        {showAmount && (
          <View style={styles.metric}>
            <Text style={styles.metricLabel}>{isCard ? 'Лимит' : 'Сумма'}</Text>
            <Text style={styles.metricValue} numberOfLines={1}>{formatMoney(offer.amount_min)} — {formatMoney(offer.amount_max)}</Text>
          </View>
        )}
        {showTerm && (
          <View style={styles.metric}>
            <Text style={styles.metricLabel}>Срок</Text>
            <Text style={styles.metricValue} numberOfLines={1}>{formatDays(offer.term_min_days)} — {formatDays(offer.term_max_days)}</Text>
          </View>
        )}
        {showRate && (
          <View style={styles.metric}>
            <Text style={styles.metricLabel}>Ставка</Text>
            <Text style={styles.metricValue} numberOfLines={1}>{formatRate(offer.rate, rateUnit)}</Text>
          </View>
        )}
        {showPsk && (
          <View style={styles.metric}>
            <Text style={styles.metricLabel}>ПСК</Text>
            <Text style={styles.metricValue}>{offer.psk}%</Text>
          </View>
        )}
      </View>

      {offer.description ? (
        <Text style={styles.description} numberOfLines={2}>{truncate(offer.description, 120)}</Text>
      ) : null}

      <View style={styles.actions}>
        <View style={styles.actionsLeft}>
          <TouchableOpacity onPress={() => onPress?.(offer)}>
            <Text style={styles.detailsLink}>Подробнее →</Text>
          </TouchableOpacity>
          <TouchableOpacity onPress={() => onToggleFavorite?.(offer.id)} style={styles.favBtn}>
            <Text style={styles.favIcon}>{isFavorite ? '❤️' : '🤍'}</Text>
          </TouchableOpacity>
        </View>
        <TouchableOpacity style={styles.applyBtn} onPress={handleApply}>
          <Text style={styles.applyBtnText}>Оформить →</Text>
        </TouchableOpacity>
      </View>
    </TouchableOpacity>
  );
}

const styles = StyleSheet.create({
  card: { backgroundColor: Colors.white, borderRadius: 16, borderWidth: 1, borderColor: Colors.surfaceBorder, padding: 16, marginBottom: 12, shadowColor: '#000', shadowOffset: { width: 0, height: 1 }, shadowOpacity: 0.04, shadowRadius: 3, elevation: 1 },
  header: { flexDirection: 'row', alignItems: 'center', gap: 12 },
  logoWrap: { width: 56, height: 56, borderRadius: 12, backgroundColor: Colors.borderLight, alignItems: 'center', justifyContent: 'center', overflow: 'hidden' },
  logo: { width: 48, height: 48 },
  logoFallback: { fontSize: 28 },
  titleWrap: { flex: 1 },
  title: { fontSize: 16, fontWeight: '700', color: Colors.text, marginBottom: 4 },
  badges: { flexDirection: 'row', flexWrap: 'wrap', gap: 6 },
  ratingBadge: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#fef9c3', paddingHorizontal: 8, paddingVertical: 2, borderRadius: 4 },
  ratingText: { fontSize: 12, fontWeight: '600', color: '#a16207' },
  reviewCount: { fontSize: 12, color: '#ca8a04' },
  freeBadge: { backgroundColor: '#dcfce7', paddingHorizontal: 8, paddingVertical: 2, borderRadius: 4 },
  freeText: { fontSize: 12, fontWeight: '600', color: '#166534' },
  metricsGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: 12, marginTop: 14 },
  metric: { width: '46%' as any },
  metricLabel: { fontSize: 11, color: Colors.textSecondary, textTransform: 'uppercase', letterSpacing: 0.5 },
  metricValue: { fontSize: 14, fontWeight: '600', color: Colors.text, marginTop: 2 },
  description: { fontSize: 14, color: Colors.textSecondary, marginTop: 12, lineHeight: 20 },
  actions: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginTop: 14, gap: 12 },
  actionsLeft: { flexDirection: 'row', alignItems: 'center', gap: 12 },
  detailsLink: { color: Colors.primary, fontWeight: '500', fontSize: 14 },
  favBtn: { padding: 4 },
  favIcon: { fontSize: 18 },
  applyBtn: { backgroundColor: Colors.accent, paddingHorizontal: 20, paddingVertical: 12, borderRadius: 10 },
  applyBtnText: { color: Colors.white, fontWeight: '700', fontSize: 14 },
});
