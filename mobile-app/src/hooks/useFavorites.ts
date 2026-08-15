import { useState, useEffect, useCallback } from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';

const FAVORITES_KEY = 'kosmozaim_favorites';

export function useFavorites() {
  const [favoriteIds, setFavoriteIds] = useState<number[]>([]);

  useEffect(() => {
    loadFavorites();
  }, []);

  const loadFavorites = async () => {
    try {
      const raw = await AsyncStorage.getItem(FAVORITES_KEY);
      if (raw) {
        setFavoriteIds(JSON.parse(raw));
      }
    } catch (e) {
      console.warn('Failed to load favorites', e);
    }
  };

  const toggleFavorite = useCallback(async (offerId: number) => {
    setFavoriteIds(prev => {
      const next = prev.includes(offerId)
        ? prev.filter(id => id !== offerId)
        : [...prev, offerId];
      AsyncStorage.setItem(FAVORITES_KEY, JSON.stringify(next)).catch(() => {});
      return next;
    });
  }, []);

  const isFavorite = useCallback((offerId: number) => {
    return favoriteIds.includes(offerId);
  }, [favoriteIds]);

  return { favoriteIds, toggleFavorite, isFavorite };
}
