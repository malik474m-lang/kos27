import React, { useEffect, useState, useCallback } from 'react';
import { View, Text, FlatList, StyleSheet } from 'react-native';
import { Colors } from '../constants/config';
import { getOffersByIds } from '../api/endpoints';
import type { Offer } from '../api/types';
import OfferCard from '../components/OfferCard';
import GradientHeader from '../components/GradientHeader';
import { useFavorites } from '../hooks/useFavorites';

interface FavoritesScreenProps {
  navigation: any;
}

export default function FavoritesScreen({ navigation }: FavoritesScreenProps) {
  const { favoriteIds, isFavorite, toggleFavorite } = useFavorites();
  const [offers, setOffers] = useState<Offer[]>([]);
  const [loading, setLoading] = useState(true);

  const loadFavorites = useCallback(async () => {
    if (favoriteIds.length === 0) {
      setOffers([]);
      setLoading(false);
      return;
    }
    try {
      const data = await getOffersByIds(favoriteIds);
      setOffers(data);
    } catch (e) {
      console.error('Failed to load favorites', e);
    } finally {
      setLoading(false);
    }
  }, [favoriteIds]);

  useEffect(() => {
    loadFavorites();
  }, [loadFavorites]);

  return (
    <View style={styles.container}>
      <GradientHeader title="❤️ Избранное" subtitle={`${favoriteIds.length} предложений`} />
      <FlatList
        data={offers}
        keyExtractor={item => String(item.id)}
        renderItem={({ item }) => (
          <OfferCard
            offer={item}
            isFavorite={isFavorite(item.id)}
            onToggleFavorite={toggleFavorite}
            onPress={(o) => navigation.navigate('OfferDetail', { offer: o })}
          />
        )}
        contentContainerStyle={styles.list}
        ListEmptyComponent={
          <View style={styles.emptyState}>
            <Text style={styles.emptyIcon}>🤍</Text>
            <Text style={styles.emptyTitle}>Список пуст</Text>
            <Text style={styles.emptyText}>
              Добавляйте предложения в избранное, нажимая 🤍 на карточке
            </Text>
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
  list: {
    padding: 16,
  },
  emptyState: {
    alignItems: 'center',
    paddingVertical: 80,
    paddingHorizontal: 40,
  },
  emptyIcon: {
    fontSize: 60,
    marginBottom: 20,
  },
  emptyTitle: {
    fontSize: 20,
    fontWeight: '700',
    color: Colors.text,
    marginBottom: 8,
  },
  emptyText: {
    fontSize: 15,
    color: Colors.textSecondary,
    textAlign: 'center',
    lineHeight: 22,
  },
});
