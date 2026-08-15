import React, { useEffect, useState, useCallback } from 'react';
import {
  View,
  Text,
  FlatList,
  RefreshControl,
  StyleSheet,
} from 'react-native';
import { Colors, API_BASE_URL } from '@/constants/config';
import type { Article } from '@/api/types';
import ArticleCard from '@/components/ArticleCard';
import GradientHeader from '@/components/GradientHeader';
import LoadingScreen from '@/components/LoadingScreen';
import { api } from '@/api/client';

interface ArticlesScreenProps {
  navigation: any;
}

export default function ArticlesScreen({ navigation }: ArticlesScreenProps) {
  const [articles, setArticles] = useState<Article[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  // Загружаем статьи напрямую с сайта через парсинг JSON
  // Т.к. нет отдельного публичного API для статей, используем offers API-подход
  // Вместо этого сделаем мобильный API endpoint
  const loadArticles = useCallback(async () => {
    try {
      // Запрос к API (нужно добавить endpoint на сервере, или использовать существующий)
      const data = await api.get<Article[]>('/api/articles');
      setArticles(data);
    } catch (e) {
      console.error('Failed to load articles', e);
      // Fallback - пустой массив
      setArticles([]);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  useEffect(() => {
    loadArticles();
  }, [loadArticles]);

  if (loading) return <LoadingScreen message="Загружаем статьи..." />;

  return (
    <View style={styles.container}>
      <GradientHeader title="📰 Статьи" subtitle="Полезные материалы о финансах" />
      <FlatList
        data={articles}
        keyExtractor={item => String(item.id)}
        renderItem={({ item }) => (
          <ArticleCard
            article={item}
            onPress={(a) => navigation.navigate('ArticleDetail', { article: a })}
          />
        )}
        contentContainerStyle={styles.list}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); loadArticles(); }} tintColor={Colors.primary} />
        }
        ListEmptyComponent={
          <View style={styles.emptyState}>
            <Text style={styles.emptyIcon}>📰</Text>
            <Text style={styles.emptyText}>Статьи пока не опубликованы</Text>
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
    paddingVertical: 60,
  },
  emptyIcon: {
    fontSize: 48,
    marginBottom: 16,
  },
  emptyText: {
    fontSize: 16,
    color: Colors.textSecondary,
  },
});
