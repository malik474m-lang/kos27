import React, { useState, useEffect, useMemo } from 'react';
import {
  View,
  Text,
  ScrollView,
  StyleSheet,
} from 'react-native';
import Slider from '@react-native-community/slider';
import { Colors } from '@/constants/config';
import { formatMoney, formatDays, calculateLoan } from '@/utils/format';
import { getOffers } from '@/api/endpoints';
import type { Offer } from '@/api/types';
import OfferCard from '@/components/OfferCard';
import GradientHeader from '@/components/GradientHeader';
import { useFavorites } from '@/hooks/useFavorites';

interface CalculatorScreenProps {
  navigation: any;
}

export default function CalculatorScreen({ navigation }: CalculatorScreenProps) {
  const [amount, setAmount] = useState(30000);
  const [term, setTerm] = useState(30);
  const [rate, setRate] = useState(1);
  const [allOffers, setAllOffers] = useState<Offer[]>([]);
  const { isFavorite, toggleFavorite } = useFavorites();

  useEffect(() => {
    getOffers().then(setAllOffers).catch(() => {});
  }, []);

  const result = useMemo(() => calculateLoan(amount, term, rate), [amount, term, rate]);

  const matchedOffers = useMemo(() => {
    return allOffers.filter(o =>
      o.amount_min <= amount && o.amount_max >= amount &&
      o.term_min_days <= term && o.term_max_days >= term
    ).slice(0, 5);
  }, [allOffers, amount, term]);

  return (
    <ScrollView style={styles.container} showsVerticalScrollIndicator={false}>
      <GradientHeader title="🧮 Калькулятор займа" subtitle="Рассчитайте стоимость и найдите подходящее предложение" />

      {/* Параметры */}
      <View style={styles.card}>
        <Text style={styles.cardTitle}>Параметры займа</Text>

        {/* Сумма */}
        <View style={styles.sliderBlock}>
          <View style={styles.sliderHeader}>
            <Text style={styles.sliderLabel}>Сумма займа</Text>
            <Text style={styles.sliderValue}>{formatMoney(amount)}</Text>
          </View>
          <Slider
            style={styles.slider}
            minimumValue={1000}
            maximumValue={1000000}
            step={1000}
            value={amount}
            onValueChange={setAmount}
            minimumTrackTintColor={Colors.primary}
            maximumTrackTintColor={Colors.border}
            thumbTintColor={Colors.primary}
          />
          <View style={styles.sliderRange}>
            <Text style={styles.rangeText}>1 000 ₽</Text>
            <Text style={styles.rangeText}>1 000 000 ₽</Text>
          </View>
        </View>

        {/* Срок */}
        <View style={styles.sliderBlock}>
          <View style={styles.sliderHeader}>
            <Text style={styles.sliderLabel}>Срок</Text>
            <Text style={styles.sliderValue}>{formatDays(term)}</Text>
          </View>
          <Slider
            style={styles.slider}
            minimumValue={1}
            maximumValue={365}
            step={1}
            value={term}
            onValueChange={setTerm}
            minimumTrackTintColor={Colors.primary}
            maximumTrackTintColor={Colors.border}
            thumbTintColor={Colors.primary}
          />
          <View style={styles.sliderRange}>
            <Text style={styles.rangeText}>1 день</Text>
            <Text style={styles.rangeText}>365 дней</Text>
          </View>
        </View>

        {/* Ставка */}
        <View style={styles.sliderBlock}>
          <View style={styles.sliderHeader}>
            <Text style={styles.sliderLabel}>Ставка (% в день)</Text>
            <Text style={styles.sliderValue}>{rate.toFixed(2)}%</Text>
          </View>
          <Slider
            style={styles.slider}
            minimumValue={0}
            maximumValue={5}
            step={0.01}
            value={rate}
            onValueChange={setRate}
            minimumTrackTintColor={Colors.primary}
            maximumTrackTintColor={Colors.border}
            thumbTintColor={Colors.primary}
          />
          <View style={styles.sliderRange}>
            <Text style={styles.rangeText}>0%</Text>
            <Text style={styles.rangeText}>5%</Text>
          </View>
        </View>

        {/* Результат */}
        <View style={styles.resultBlock}>
          <Text style={styles.resultTitle}>Результат расчёта</Text>
          <View style={styles.resultGrid}>
            <View style={styles.resultItem}>
              <Text style={styles.resultLabel}>Сумма к возврату</Text>
              <Text style={styles.resultValue}>{formatMoney(result.total)}</Text>
            </View>
            <View style={styles.resultItem}>
              <Text style={styles.resultLabel}>Переплата</Text>
              <Text style={[styles.resultValue, { color: Colors.error }]}>
                {formatMoney(result.overpay)}
              </Text>
            </View>
            <View style={styles.resultItem}>
              <Text style={styles.resultLabel}>Ежедневный платёж</Text>
              <Text style={styles.resultValue}>{formatMoney(result.daily)}</Text>
            </View>
            <View style={styles.resultItem}>
              <Text style={styles.resultLabel}>Ставка годовая</Text>
              <Text style={styles.resultValue}>{result.yearlyRate.toFixed(1)}%</Text>
            </View>
          </View>
        </View>
      </View>

      {/* Подходящие предложения */}
      <View style={styles.offersSection}>
        <Text style={styles.offersTitle}>
          Подходящие предложения {matchedOffers.length > 0 ? `(${matchedOffers.length})` : ''}
        </Text>
        {matchedOffers.length > 0 ? (
          matchedOffers.map(offer => (
            <OfferCard
              key={offer.id}
              offer={offer}
              isFavorite={isFavorite(offer.id)}
              onToggleFavorite={toggleFavorite}
              onPress={(o) => navigation.navigate('OfferDetail', { offer: o })}
            />
          ))
        ) : (
          <View style={styles.noOffers}>
            <Text style={styles.noOffersIcon}>🔍</Text>
            <Text style={styles.noOffersText}>Подходящих предложений не найдено</Text>
            <Text style={styles.noOffersHint}>Измените параметры</Text>
          </View>
        )}
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
  card: {
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
  cardTitle: {
    fontSize: 20,
    fontWeight: '700',
    color: Colors.text,
    marginBottom: 20,
  },
  sliderBlock: {
    marginBottom: 24,
  },
  sliderHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 8,
  },
  sliderLabel: {
    fontSize: 14,
    fontWeight: '500',
    color: Colors.textSecondary,
  },
  sliderValue: {
    fontSize: 18,
    fontWeight: '700',
    color: Colors.primary,
  },
  slider: {
    width: '100%',
    height: 40,
  },
  sliderRange: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: -4,
  },
  rangeText: {
    fontSize: 11,
    color: Colors.textMuted,
  },
  resultBlock: {
    backgroundColor: Colors.background,
    borderRadius: 16,
    padding: 20,
    marginTop: 8,
  },
  resultTitle: {
    fontSize: 16,
    fontWeight: '700',
    color: Colors.text,
    marginBottom: 16,
  },
  resultGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 16,
  },
  resultItem: {
    width: '46%' as any,
  },
  resultLabel: {
    fontSize: 11,
    color: Colors.textSecondary,
    textTransform: 'uppercase',
    letterSpacing: 0.5,
  },
  resultValue: {
    fontSize: 18,
    fontWeight: '700',
    color: Colors.text,
    marginTop: 4,
  },
  offersSection: {
    paddingHorizontal: 16,
    marginTop: 8,
  },
  offersTitle: {
    fontSize: 20,
    fontWeight: '700',
    color: Colors.text,
    marginBottom: 16,
  },
  noOffers: {
    alignItems: 'center',
    paddingVertical: 40,
    backgroundColor: Colors.white,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: Colors.surfaceBorder,
  },
  noOffersIcon: {
    fontSize: 40,
    marginBottom: 12,
  },
  noOffersText: {
    fontSize: 16,
    color: Colors.textSecondary,
    fontWeight: '600',
  },
  noOffersHint: {
    fontSize: 14,
    color: Colors.textMuted,
    marginTop: 6,
  },
});
