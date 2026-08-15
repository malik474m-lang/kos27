import React, { useEffect, useState } from 'react';
import { View, Text, ScrollView, TouchableOpacity, Linking, StyleSheet } from 'react-native';
import { Colors, API_BASE_URL } from '../constants/config';
import { getActiveGiveaway } from '../api/endpoints';
import type { GiveawayInfo } from '../api/types';
import GradientHeader from '../components/GradientHeader';
import LoadingScreen from '../components/LoadingScreen';
import { formatMoney } from '../utils/format';
import { trackPageView } from '../api/tracker';

export default function GiveawayScreen() {
  const [giveaway, setGiveaway] = useState<GiveawayInfo | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    getActiveGiveaway().then(setGiveaway); trackPageView('Giveaway').catch(() => {}).finally(() => setLoading(false));
  }, []);

  if (loading) return <LoadingScreen message="Загрузка..." />;

  if (!giveaway) {
    return (
      <View style={styles.container}>
        <GradientHeader title="🎁 Розыгрыши" subtitle="Денежные призы для участников" />
        <View style={styles.empty}>
          <Text style={styles.emptyIcon}>🎁</Text>
          <Text style={styles.emptyTitle}>Нет активных розыгрышей</Text>
          <Text style={styles.emptyText}>Следите за обновлениями — скоро будет новый розыгрыш!</Text>
        </View>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <GradientHeader title="🎁 Розыгрыш" subtitle={giveaway.title} />
      <ScrollView contentContainerStyle={styles.content} showsVerticalScrollIndicator={false}>
        <View style={styles.card}>
          <Text style={styles.prizeIcon}>🏆</Text>
          <Text style={styles.prizeAmount}>{formatMoney(giveaway.prize_amount)}</Text>
          <Text style={styles.prizeLabel}>Призовой фонд</Text>
        </View>
        <View style={styles.statsRow}>
          <View style={styles.stat}>
            <Text style={styles.statValue}>{giveaway.entries_count}</Text>
            <Text style={styles.statLabel}>Участников</Text>
          </View>
          <View style={styles.stat}>
            <Text style={styles.statValue}>{giveaway.status === 'active' ? '🟢' : '🔴'}</Text>
            <Text style={styles.statLabel}>{giveaway.status === 'active' ? 'Активен' : giveaway.status}</Text>
          </View>
        </View>
        <TouchableOpacity style={styles.joinBtn} onPress={() => Linking.openURL(`${API_BASE_URL}/giveaway`)}>
          <Text style={styles.joinBtnText}>Участвовать на сайте →</Text>
        </TouchableOpacity>
        <View style={{ height: 40 }} />
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: Colors.background },
  content: { padding: 16 },
  card: { backgroundColor: Colors.white, borderRadius: 20, padding: 32, alignItems: 'center', borderWidth: 1, borderColor: Colors.surfaceBorder, marginBottom: 16 },
  prizeIcon: { fontSize: 48, marginBottom: 12 },
  prizeAmount: { fontSize: 36, fontWeight: '800', color: Colors.accent },
  prizeLabel: { fontSize: 15, color: Colors.textSecondary, marginTop: 4 },
  statsRow: { flexDirection: 'row', gap: 12, marginBottom: 16 },
  stat: { flex: 1, backgroundColor: Colors.white, borderRadius: 16, padding: 20, alignItems: 'center', borderWidth: 1, borderColor: Colors.surfaceBorder },
  statValue: { fontSize: 24, fontWeight: '700', color: Colors.text },
  statLabel: { fontSize: 13, color: Colors.textSecondary, marginTop: 4 },
  joinBtn: { backgroundColor: Colors.accent, borderRadius: 14, paddingVertical: 18, alignItems: 'center' },
  joinBtnText: { color: Colors.white, fontSize: 17, fontWeight: '700' },
  empty: { alignItems: 'center', paddingTop: 80, paddingHorizontal: 40 },
  emptyIcon: { fontSize: 60, marginBottom: 20 },
  emptyTitle: { fontSize: 20, fontWeight: '700', color: Colors.text, marginBottom: 8 },
  emptyText: { fontSize: 15, color: Colors.textSecondary, textAlign: 'center', lineHeight: 22 },
});
