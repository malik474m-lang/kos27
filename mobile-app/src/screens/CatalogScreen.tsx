import React, { useEffect, useState, useCallback } from 'react';
import {
  View,
  Text,
  FlatList,
  TouchableOpacity,
  TextInput,
  RefreshControl,
  StyleSheet,
  ScrollView,
} from 'react-native';
import { Colors, CATEGORIES, BORROWER_CATEGORIES } from '../constants/config';
import { getOffers } from '../api/endpoints';
import type { Offer } from '../api/types';
import OfferCard from '../components/OfferCard';
import GradientHeader from '../components/GradientHeader';
import LoadingScreen from '../components/LoadingScreen';
import { useFavorites } from '../hooks/useFavorites';
import type { CategoryKey } from '../constants/config';
import { trackCategoryView, trackOfferClick } from '../api/tracker';

interface CatalogScreenProps {
  navigation: any;
  route: any;
}

export default function CatalogScreen({ navigation, route }: CatalogScreenProps) {
  const category: CategoryKey = route.params?.category || 'microloans';
  const catInfo = CATEGORIES[category];

  const [allOffers, setAllOffers] = useState<Offer[]>([]);
  const [filteredOffers, setFilteredOffers] = useState<Offer[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [showFilters, setShowFilters] = useState(false);
  const { isFavorite, toggleFavorite } = useFavorites();

  // Фильтры
  const [filterAmount, setFilterAmount] = useState('');
  const [filterTerm, setFilterTerm] = useState('');
  const [filterBorrower, setFilterBorrower] = useState('');

  const loadData = useCallback(async () => {
    try {
      const data = await getOffers(category);
      setAllOffers(data);
      applyFilters(data, filterAmount, filterTerm, filterBorrower);
    } catch (e) {
      console.error('Failed to load offers', e);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [category]);

  useEffect(() => {
    setLoading(true);
    loadData();
    trackCategoryView(category);
  }, [loadData]);

  const applyFilters = (offers: Offer[], amount: string, term: string, borrower: string) => {
    let result = [...offers];
    const amountNum = parseInt(amount);
    const termNum = parseInt(term);
    if (amountNum > 0) {
      result = result.filter(o => o.amount_min <= amountNum && o.amount_max >= amountNum);
    }
    if (termNum > 0) {
      result = result.filter(o => o.term_min_days <= termNum && o.term_max_days >= termNum);
    }
    if (borrower && borrower !== 'any') {
      result = result.filter(o => o.borrower_category === borrower || o.borrower_category === 'any');
    }
    setFilteredOffers(result);
  };

  const handleFilter = () => {
    applyFilters(allOffers, filterAmount, filterTerm, filterBorrower);
    setShowFilters(false);
  };

  const resetFilters = () => {
    setFilterAmount('');
    setFilterTerm('');
    setFilterBorrower('');
    setFilteredOffers(allOffers);
    setShowFilters(false);
  };

  if (loading) return <LoadingScreen message="Загружаем предложения..." />;

  return (
    <View style={styles.container}>
      <GradientHeader
        title={`${catInfo.icon} ${catInfo.title}`}
        subtitle={`${filteredOffers.length} предложений`}
      />

      {/* Кнопка фильтров */}
      <View style={styles.filterBar}>
        <TouchableOpacity
          style={[styles.filterBtn, showFilters && styles.filterBtnActive]}
          onPress={() => setShowFilters(!showFilters)}
        >
          <Text style={[styles.filterBtnText, showFilters && styles.filterBtnTextActive]}>
            🔍 Фильтры
          </Text>
        </TouchableOpacity>
        {(filterAmount || filterTerm || filterBorrower) && (
          <TouchableOpacity onPress={resetFilters}>
            <Text style={styles.resetLink}>Сбросить</Text>
          </TouchableOpacity>
        )}
      </View>

      {/* Панель фильтров */}
      {showFilters && (
        <View style={styles.filterPanel}>
          <View style={styles.filterRow}>
            <View style={styles.filterField}>
              <Text style={styles.filterLabel}>Сумма (₽)</Text>
              <TextInput
                style={styles.filterInput}
                value={filterAmount}
                onChangeText={setFilterAmount}
                placeholder="50000"
                keyboardType="numeric"
                placeholderTextColor={Colors.textMuted}
              />
            </View>
            <View style={styles.filterField}>
              <Text style={styles.filterLabel}>Срок (дней)</Text>
              <TextInput
                style={styles.filterInput}
                value={filterTerm}
                onChangeText={setFilterTerm}
                placeholder="30"
                keyboardType="numeric"
                placeholderTextColor={Colors.textMuted}
              />
            </View>
          </View>
          <Text style={styles.filterLabel}>Категория заёмщика</Text>
          <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.borrowerScroll}>
            {BORROWER_CATEGORIES.map(bc => (
              <TouchableOpacity
                key={bc.key}
                style={[styles.borrowerChip, filterBorrower === bc.key && styles.borrowerChipActive]}
                onPress={() => setFilterBorrower(bc.key)}
              >
                <Text style={[styles.borrowerChipText, filterBorrower === bc.key && styles.borrowerChipTextActive]}>
                  {bc.label}
                </Text>
              </TouchableOpacity>
            ))}
          </ScrollView>
          <TouchableOpacity style={styles.applyFilterBtn} onPress={handleFilter}>
            <Text style={styles.applyFilterBtnText}>Применить</Text>
          </TouchableOpacity>
        </View>
      )}

      {/* Список офферов */}
      <FlatList
        data={filteredOffers}
        keyExtractor={item => String(item.id)}
        renderItem={({ item }) => (
          <OfferCard
            offer={item}
            isFavorite={isFavorite(item.id)}
            onToggleFavorite={toggleFavorite}
            onPress={(o) => { trackOfferClick(o.id, o.title); navigation.navigate('OfferDetail', { offer: o }); }}
          />
        )}
        contentContainerStyle={styles.list}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); loadData(); }} tintColor={Colors.primary} />
        }
        ListEmptyComponent={
          <View style={styles.emptyState}>
            <Text style={styles.emptyIcon}>🔍</Text>
            <Text style={styles.emptyText}>Предложения не найдены</Text>
            <Text style={styles.emptyHint}>Попробуйте изменить параметры фильтра</Text>
          </View>
        }
        showsVerticalScrollIndicator={false}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: Colors.background,
  },
  filterBar: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 16,
    paddingVertical: 12,
  },
  filterBtn: {
    backgroundColor: Colors.white,
    paddingHorizontal: 16,
    paddingVertical: 10,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: Colors.border,
  },
  filterBtnActive: {
    backgroundColor: Colors.primary,
    borderColor: Colors.primary,
  },
  filterBtnText: {
    fontSize: 14,
    fontWeight: '600',
    color: Colors.text,
  },
  filterBtnTextActive: {
    color: Colors.white,
  },
  resetLink: {
    fontSize: 14,
    color: Colors.error,
    fontWeight: '500',
  },
  filterPanel: {
    backgroundColor: Colors.white,
    marginHorizontal: 16,
    padding: 16,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: Colors.surfaceBorder,
    marginBottom: 12,
  },
  filterRow: {
    flexDirection: 'row',
    gap: 12,
    marginBottom: 12,
  },
  filterField: {
    flex: 1,
  },
  filterLabel: {
    fontSize: 13,
    fontWeight: '500',
    color: Colors.textSecondary,
    marginBottom: 6,
  },
  filterInput: {
    backgroundColor: Colors.background,
    borderWidth: 1,
    borderColor: Colors.border,
    borderRadius: 10,
    paddingHorizontal: 14,
    paddingVertical: 10,
    fontSize: 14,
    color: Colors.text,
  },
  borrowerScroll: {
    marginBottom: 16,
  },
  borrowerChip: {
    backgroundColor: Colors.background,
    paddingHorizontal: 14,
    paddingVertical: 8,
    borderRadius: 20,
    marginRight: 8,
    borderWidth: 1,
    borderColor: Colors.border,
  },
  borrowerChipActive: {
    backgroundColor: Colors.primary,
    borderColor: Colors.primary,
  },
  borrowerChipText: {
    fontSize: 13,
    color: Colors.text,
    fontWeight: '500',
  },
  borrowerChipTextActive: {
    color: Colors.white,
  },
  applyFilterBtn: {
    backgroundColor: Colors.primary,
    paddingVertical: 14,
    borderRadius: 10,
    alignItems: 'center',
  },
  applyFilterBtnText: {
    color: Colors.white,
    fontWeight: '700',
    fontSize: 15,
  },
  list: {
    paddingHorizontal: 16,
    paddingBottom: 40,
  },
  emptyState: {
    alignItems: 'center',
    paddingVertical: 60,
  },
  emptyIcon: {
    fontSize: 48,
    marginBottom: 16,
  },
  emptyText: {
    fontSize: 18,
    color: Colors.textSecondary,
    fontWeight: '600',
  },
  emptyHint: {
    fontSize: 14,
    color: Colors.textMuted,
    marginTop: 8,
  },
});
