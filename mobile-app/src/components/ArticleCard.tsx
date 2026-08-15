import React from 'react';
import { View, Text, Image, TouchableOpacity, StyleSheet } from 'react-native';
import { Colors, API_BASE_URL } from '../constants/config';
import { formatDate, truncate } from '../utils/format';
import type { Article } from '../api/types';

interface ArticleCardProps {
  article: Article;
  onPress?: (article: Article) => void;
  compact?: boolean;
}

function normalizeImageUrl(url: string): string {
  if (!url) return '';
  if (url.startsWith('http')) return url;
  let normalized = url;
  if (normalized.startsWith('/public/')) normalized = normalized.substring(7);
  return `${API_BASE_URL}${normalized}`;
}

export default function ArticleCard({ article, onPress, compact }: ArticleCardProps) {
  const coverUrl = normalizeImageUrl(article.cover_image || '');

  if (compact) {
    return (
      <TouchableOpacity
        style={styles.compactCard}
        onPress={() => onPress?.(article)}
        activeOpacity={0.7}
      >
        {coverUrl ? (
          <Image source={{ uri: coverUrl }} style={styles.compactImage} resizeMode="cover" />
        ) : (
          <View style={[styles.compactImage, styles.imagePlaceholder]}>
            <Text style={styles.placeholderIcon}>📰</Text>
          </View>
        )}
        <Text style={styles.compactTitle} numberOfLines={2}>{article.title}</Text>
      </TouchableOpacity>
    );
  }

  return (
    <TouchableOpacity
      style={styles.card}
      onPress={() => onPress?.(article)}
      activeOpacity={0.7}
    >
      {coverUrl ? (
        <Image source={{ uri: coverUrl }} style={styles.coverImage} resizeMode="cover" />
      ) : (
        <View style={[styles.coverImage, styles.imagePlaceholder]}>
          <Text style={styles.placeholderIcon}>📰</Text>
        </View>
      )}
      <View style={styles.content}>
        <Text style={styles.title} numberOfLines={2}>{article.title}</Text>
        {article.excerpt ? (
          <Text style={styles.excerpt} numberOfLines={3}>
            {truncate(article.excerpt, 150)}
          </Text>
        ) : null}
        <Text style={styles.date}>{formatDate(article.created_at)}</Text>
      </View>
    </TouchableOpacity>
  );
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: Colors.white,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: Colors.surfaceBorder,
    overflow: 'hidden',
    marginBottom: 16,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.04,
    shadowRadius: 3,
    elevation: 1,
  },
  coverImage: {
    width: '100%',
    height: 160,
    backgroundColor: Colors.borderLight,
  },
  imagePlaceholder: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  placeholderIcon: {
    fontSize: 40,
  },
  content: {
    padding: 16,
  },
  title: {
    fontSize: 17,
    fontWeight: '700',
    color: Colors.text,
    marginBottom: 8,
    lineHeight: 24,
  },
  excerpt: {
    fontSize: 14,
    color: Colors.textSecondary,
    lineHeight: 20,
    marginBottom: 10,
  },
  date: {
    fontSize: 12,
    color: Colors.textMuted,
  },
  // Compact variant
  compactCard: {
    backgroundColor: Colors.white,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: Colors.surfaceBorder,
    overflow: 'hidden',
    width: 200,
    marginRight: 12,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.04,
    shadowRadius: 3,
    elevation: 1,
  },
  compactImage: {
    width: '100%',
    height: 100,
    backgroundColor: Colors.borderLight,
  },
  compactTitle: {
    fontSize: 13,
    fontWeight: '600',
    color: Colors.text,
    padding: 10,
    lineHeight: 18,
  },
});
