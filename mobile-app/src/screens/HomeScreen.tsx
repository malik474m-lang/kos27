import React, { useEffect, useState, useCallback } from 'react';
import {
  View,
  Text,
  ScrollView,
  FlatList,
  TouchableOpacity,
  RefreshControl,
  StyleSheet,
  Linking,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { Colors, CATEGORIES } from '@/constants/config';
import { getOffers, getActiveGiveaway } from '@/api/endpoints';
import type { Offer, Article, GiveawayInfo } from '@/api/types';
import OfferCard from '@/components/OfferCard';
import CategoryCard from '@/components/CategoryCard';
import LoadingScreen from '@/components/LoadingScreen';
import { useFavorites } from '@/hooks/useFavorites';
import { formatMoney } from '@/utils/format';

interface HomeScreenProps {
  navigation: any;
}

export default function HomeScreen({ navigation }: HomeScreenProps) {
  const [offers, setOffers] = useState<Offer[]>([]);
  const [giveaway, setGiveaway] = useState<GiveawayInfo | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const { isFavorite, toggleFavorite } = useFavorites();

  const loadData = useCallback(async () => {
    try {
      const [offersData, giveawayData] = await Promise.all([
        getOffers(),
        getActiveGiveaway().catch(() => null),
      ]);
      setOffers(offersData.slice(0, 6));
      setGiveaway(giveawayData);
    } catch (e) {
      console.error('Failed to load home data', e);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  useEffect(() => {
    loadData();
  }, [loadData]);

  const onRefresh = () => {
    setRefreshing(true);
    loadData();
  };

  if (loading) return <LoadingScreen message="Загружаем предложения..." />;

  return (
    <ScrollView
      style={styles.container}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={Colors.primary} />}
      showsVerticalScrollIndicator={false}
    >
      {/* Hero секция */}
      <LinearGradient
        colors={[Colors.gradientStart, Colors.gradientEnd]}
        start={{ x: 0, y: 0 }}
        end={{ x: 1, y: 1 }}
        style={styles.hero}
      >
        <Text style={styles.heroTitle}>
          Подберите лучший{'\n'}займ или кредит
        </Text>
        <Text style={styles.heroSubtitle}>
          Сравнивайте условия от проверенных партнёров
        </Text>
        <View style={styles.heroBtns}>
          <TouchableOpacity
            style={styles.heroBtn}
            onPress={() => navigation.navigate('Calculator')}
          >
            <Text style={styles.heroBtnText}>🧮 Калькулятор</Text>
          </TouchableOpacity>
          <TouchableOpacity
            style={styles.heroBtnSecondary}
            onPress={() => navigation.navigate('Catalog', { category: 'microloans' })}
          >
            <Text style={styles.heroBtnSecondaryText}>Все предложения →</Text>
          </TouchableOpacity>
        </View>
      </LinearGradient>

      {/* Категории */}
      <View style={styles.categoriesRow}>
        <CategoryCard
          icon="💵"
          title="Займы"
          subtitle="Микрозаймы онлайн"
          onPress={() => navigation.navigate('Catalog', { category: 'microloans' })}
        />
        <CategoryCard
          icon="🏦"
          title="Кредиты"
          subtitle="Банковские"
          onPress={() => navigation.navigate('Catalog', { category: 'credits' })}
        />
      </View>
      <View style={styles.categoriesRow}>
        <CategoryCard
          icon="💳"
          title="Кредитные"
          subtitle="С кредитным лимитом"
          onPress={() => navigation.navigate('Catalog', { category: 'credit_cards' })}
        />
        <CategoryCard
          icon="🪪"
          title="Дебетовые"
          subtitle="С кэшбеком"
          onPress={() => navigation.navigate('Catalog', { category: 'debit_cards' })}
        />
      </View>

      {/* Баннер розыгрыша */}
      {giveaway && (
        <TouchableOpacity
          style={styles.giveawayBanner}
          onPress={() => Linking.openURL('https://kosmozaim.ru/giveaway')}
        >
          <Text style={styles.giveawayIcon}>🎁</Text>
          <View style={styles.giveawayContent}>
            <Text style={styles.giveawayTitle}>{giveaway.title}</Text>
            <Text style={styles.giveawayPrize}>
              Приз: {formatMoney(giveaway.prize_amount)} • {giveaway.entries_count} участников
            </Text>
          </View>
          <Text style={styles.giveawayArrow}>→</Text>
        </TouchableOpacity>
      )}

      {/* Лучшие предложения */}
      <View style={styles.section}>
        <View style={styles.sectionHeader}>
          <Text style={styles.sectionTitle}>Лучшие предложения</Text>
          <TouchableOpacity onPress={() => navigation.navigate('Catalog', { category: 'microloans' })}>
            <Text style={styles.sectionLink}>Все →</Text>
          </TouchableOpacity>
        </View>
        {offers.map(offer => (
          <OfferCard
            key={offer.id}
            offer={offer}
            isFavorite={isFavorite(offer.id)}
            onToggleFavorite={toggleFavorite}
            onPress={(o) => navigation.navigate('OfferDetail', { offer: o })}
          />
        ))}
      </View>

      {/* Баннер калькулятора */}
      <TouchableOpacity onPress={() => navigation.navigate('Calculator')}>
        <LinearGradient
          colors={[Colors.primary, '#7e3af2']}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 1 }}
          style={styles.calcBanner}
        >
          <Text style={styles.calcBannerTitle}>Калькулятор займа</Text>
          <Text style={styles.calcBannerSub}>Рассчитайте стоимость и подберите предложение</Text>
          <View style={styles.calcBannerBtn}>
            <Text style={styles.calcBannerBtnText}>Открыть калькулятор</Text>
          </View>
        </LinearGradient>
      </TouchableOpacity>

      {/* Навигация разделов */}
      <View style={styles.navSection}>
        <Text style={styles.sectionTitle}>Разделы</Text>
        <View style={styles.navGrid}>
          {[
            { icon: '📰', title: 'Статьи', screen: 'Articles' },
            { icon: '❓', title: 'FAQ', screen: 'FAQ' },
            { icon: '🧮', title: 'Калькулятор', screen: 'Calculator' },
            { icon: '❤️', title: 'Избранное', screen: 'Favorites' },
          ].map(item => (
            <TouchableOpacity
              key={item.screen}
              style={styles.navItem}
              onPress={() => navigation.navigate(item.screen)}
            >
              <Text style={styles.navIcon}>{item.icon}</Text>
              <Text style={styles.navLabel}>{item.title}</Text>
            </TouchableOpacity>
          ))}
        </View>
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
  hero: {
    paddingHorizontal: 20,
    paddingTop: 60,
    paddingBottom: 32,
  },
  heroTitle: {
    fontSize: 28,
    fontWeight: '800',
    color: Colors.white,
    lineHeight: 36,
  },
  heroSubtitle: {
    fontSize: 16,
    color: 'rgba(255,255,255,0.8)',
    marginTop: 10,
    lineHeight: 22,
  },
  heroBtns: {
    flexDirection: 'row',
    gap: 12,
    marginTop: 20,
  },
  heroBtn: {
    backgroundColor: Colors.accent,
    paddingHorizontal: 20,
    paddingVertical: 14,
    borderRadius: 12,
  },
  heroBtnText: {
    color: Colors.white,
    fontWeight: '700',
    fontSize: 15,
  },
  heroBtnSecondary: {
    backgroundColor: 'rgba(255,255,255,0.2)',
    paddingHorizontal: 20,
    paddingVertical: 14,
    borderRadius: 12,
  },
  heroBtnSecondaryText: {
    color: Colors.white,
    fontWeight: '600',
    fontSize: 15,
  },
  categoriesRow: {
    flexDirection: 'row',
    paddingHorizontal: 16,
    gap: 12,
    marginTop: 12,
  },
  giveawayBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#fef3c7',
    marginHorizontal: 16,
    marginTop: 16,
    padding: 16,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: '#fde68a',
    gap: 12,
  },
  giveawayIcon: {
    fontSize: 32,
  },
  giveawayContent: {
    flex: 1,
  },
  giveawayTitle: {
    fontSize: 15,
    fontWeight: '700',
    color: '#92400e',
  },
  giveawayPrize: {
    fontSize: 13,
    color: '#b45309',
    marginTop: 2,
  },
  giveawayArrow: {
    fontSize: 18,
    color: '#b45309',
    fontWeight: '700',
  },
  section: {
    paddingHorizontal: 16,
    marginTop: 24,
  },
  sectionHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 16,
  },
  sectionTitle: {
    fontSize: 20,
    fontWeight: '700',
    color: Colors.text,
  },
  sectionLink: {
    fontSize: 14,
    color: Colors.primary,
    fontWeight: '500',
  },
  calcBanner: {
    marginHorizontal: 16,
    marginTop: 24,
    padding: 28,
    borderRadius: 20,
    alignItems: 'center',
  },
  calcBannerTitle: {
    fontSize: 22,
    fontWeight: '700',
    color: Colors.white,
    marginBottom: 8,
  },
  calcBannerSub: {
    fontSize: 14,
    color: 'rgba(255,255,255,0.8)',
    textAlign: 'center',
    marginBottom: 16,
  },
  calcBannerBtn: {
    backgroundColor: Colors.white,
    paddingHorizontal: 24,
    paddingVertical: 12,
    borderRadius: 10,
  },
  calcBannerBtnText: {
    color: Colors.primary,
    fontWeight: '600',
    fontSize: 15,
  },
  navSection: {
    paddingHorizontal: 16,
    marginTop: 24,
  },
  navGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
    marginTop: 16,
  },
  navItem: {
    width: '47%' as any,
    backgroundColor: Colors.white,
    borderRadius: 12,
    padding: 16,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: Colors.surfaceBorder,
  },
  navIcon: {
    fontSize: 28,
    marginBottom: 8,
  },
  navLabel: {
    fontSize: 14,
    fontWeight: '600',
    color: Colors.text,
  },
});
