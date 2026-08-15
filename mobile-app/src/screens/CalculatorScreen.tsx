import React, { useState, useEffect, useMemo } from 'react';
import {
  View,
  Text,
  ScrollView,
  TextInput,
  StyleSheet,
  TouchableOpacity,
} from 'react-native';
import { Colors } from '../constants/config';
import { formatMoney, formatDays, calculateLoan } from '../utils/format';
import { getOffers } from '../api/endpoints';
import type { Offer } from '../api/types';
import OfferCard from '../components/OfferCard';
import GradientHeader from '../components/GradientHeader';
import { useFavorites } from '../hooks/useFavorites';
import { trackCalculatorUse, trackOfferClick, trackPageView } from '../api/tracker';

interface CalculatorScreenProps {
  navigation: any;
}

export default function CalculatorScreen({ navigation }: CalculatorScreenProps) {
  const [amount, setAmount] = useState('30000');
  const [term, setTerm] = useState('30');
  const [rate, setRate] = useState('1');
  const [allOffers, setAllOffers] = useState<Offer[]>([]);
  const { isFavorite, toggleFavorite } = useFavorites();

  useEffect(() => {
    getOffers().then(setAllOffers).catch(() => {});
    trackPageView('Calculator');
  }, []);

  const amountNum = parseInt(amount) || 0;
  const termNum = parseInt(term) || 0;
  const rateNum = parseFloat(rate) || 0;

  const result = useMemo(() => calculateLoan(amountNum, termNum, rateNum), [amountNum, termNum, rateNum]);

  const matchedOffers = useMemo(() => {
    return allOffers.filter(o =>
      o.amount_min <= amountNum && o.amount_max >= amountNum &&
      o.term_min_days <= termNum && o.term_max_days >= termNum
    ).slice(0, 5);
  }, [allOffers, amountNum, termNum]);

  const presets = [
    { label: '10 000', amount: '10000', term: '14' },
    { label: '30 000', amount: '30000', term: '30' },
    { label: '50 000', amount: '50000', term: '60' },
    { label: '100 000', amount: '100000', term: '90' },
  ];

  return (
    <ScrollView style={styles.container} showsVerticalScrollIndicator={false}>
      <GradientHeader title="🧮 Калькулятор займа" subtitle="Рассчитайте стоимость и подберите предложение" />

      {/* Быстрый выбор */}
      <View style={styles.presetsRow}>
        {presets.map((p) => (
          <TouchableOpacity
            key={p.label}
            style={[styles.preset, amount === p.amount && styles.presetActive]}
            onPress={() => { setAmount(p.amount); setTerm(p.term); }}
          >
            <Text style={[styles.presetText, amount === p.amount && styles.presetTextActive]}>{p.label} ₽</Text>
          </TouchableOpacity>
        ))}
      </View>

      {/* Параметры */}
      <View style={styles.card}>
        <Text style={styles.cardTitle}>Параметры займа</Text>

        <Text style={styles.inputLabel}>Сумма (₽)</Text>
        <TextInput
          style={styles.input}
          value={amount}
          onChangeText={setAmount}
          keyboardType="numeric"
          placeholder="30000"
          placeholderTextColor={Colors.textMuted}
        />

        <Text style={styles.inputLabel}>Срок (дней)</Text>
        <TextInput
          style={styles.input}
          value={term}
          onChangeText={setTerm}
          keyboardType="numeric"
          placeholder="30"
          placeholderTextColor={Colors.textMuted}
        />

        <Text style={styles.inputLabel}>Ставка (% в день)</Text>
        <TextInput
          style={styles.input}
          value={rate}
          onChangeText={setRate}
          keyboardType="decimal-pad"
          placeholder="1"
          placeholderTextColor={Colors.textMuted}
        />

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
              <Text style={[styles.resultValue, { color: Colors.error }]}>{formatMoney(result.overpay)}</Text>
            </View>
            <View style={styles.resultItem}>
              <Text style={styles.resultLabel}>Ежедневно</Text>
              <Text style={styles.resultValue}>{formatMoney(result.daily)}</Text>
            </View>
            <View style={styles.resultItem}>
              <Text style={styles.resultLabel}>Годовая ставка</Text>
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
              onPress={(o: Offer) => { trackOfferClick(o.id, o.title); navigation.navigate('OfferDetail', { offer: o }); }}
            />
          ))
        ) : (
          <View style={styles.noOffers}>
            <Text style={styles.noOffersIcon}>🔍</Text>
            <Text style={styles.noOffersText}>Предложений не найдено</Text>
          </View>
        )}
      </View>
      <View style={{ height: 40 }} />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: Colors.background },
  presetsRow: { flexDirection: 'row', paddingHorizontal: 16, paddingTop: 16, gap: 8 },
  preset: { flex: 1, backgroundColor: Colors.white, borderRadius: 10, paddingVertical: 10, alignItems: 'center', borderWidth: 1, borderColor: Colors.border },
  presetActive: { backgroundColor: Colors.primary, borderColor: Colors.primary },
  presetText: { fontSize: 13, fontWeight: '600', color: Colors.text },
  presetTextActive: { color: Colors.white },
  card: { backgroundColor: Colors.white, margin: 16, borderRadius: 20, padding: 20, borderWidth: 1, borderColor: Colors.surfaceBorder },
  cardTitle: { fontSize: 20, fontWeight: '700', color: Colors.text, marginBottom: 16 },
  inputLabel: { fontSize: 13, fontWeight: '500', color: Colors.textSecondary, marginBottom: 6, marginTop: 12 },
  input: { backgroundColor: Colors.background, borderWidth: 1, borderColor: Colors.border, borderRadius: 12, paddingHorizontal: 16, paddingVertical: 14, fontSize: 17, fontWeight: '600', color: Colors.text },
  resultBlock: { backgroundColor: Colors.background, borderRadius: 16, padding: 20, marginTop: 20 },
  resultTitle: { fontSize: 16, fontWeight: '700', color: Colors.text, marginBottom: 16 },
  resultGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: 16 },
  resultItem: { width: '46%' as any },
  resultLabel: { fontSize: 11, color: Colors.textSecondary, textTransform: 'uppercase' },
  resultValue: { fontSize: 18, fontWeight: '700', color: Colors.text, marginTop: 4 },
  offersSection: { paddingHorizontal: 16, marginTop: 8 },
  offersTitle: { fontSize: 20, fontWeight: '700', color: Colors.text, marginBottom: 16 },
  noOffers: { alignItems: 'center', paddingVertical: 40, backgroundColor: Colors.white, borderRadius: 16, borderWidth: 1, borderColor: Colors.surfaceBorder },
  noOffersIcon: { fontSize: 40, marginBottom: 12 },
  noOffersText: { fontSize: 16, color: Colors.textSecondary },
});
