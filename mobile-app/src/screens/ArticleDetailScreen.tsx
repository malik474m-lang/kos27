import React from 'react';
import { View, Text, ScrollView, Image, StyleSheet } from 'react-native';
import { Colors, API_BASE_URL } from '../constants/config';
import { formatDate, stripHtml } from '../utils/format';
import type { Article } from '../api/types';

interface Props { route: any; }

function normalizeImageUrl(url: string): string {
  if (!url) return '';
  if (url.startsWith('http')) return url;
  let n = url;
  if (n.startsWith('/public/')) n = n.substring(7);
  return `${API_BASE_URL}${n}`;
}

export default function ArticleDetailScreen({ route }: Props) {
  const article: Article = route.params?.article;
  if (!article) return <View style={styles.container}><Text style={styles.errorText}>Статья не найдена</Text></View>;

  const coverUrl = normalizeImageUrl(article.cover_image || '');
  const cleanContent = stripHtml(article.content || '');
  const paragraphs = cleanContent.split('\n').filter((p: string) => p.trim().length > 0);

  return (
    <ScrollView style={styles.container} showsVerticalScrollIndicator={false}>
      {coverUrl ? (
        <Image source={{ uri: coverUrl }} style={styles.cover} resizeMode="cover" />
      ) : null}
      <View style={styles.content}>
        <Text style={styles.title}>{article.title}</Text>
        <Text style={styles.date}>{formatDate(article.created_at)}</Text>
        {paragraphs.map((p: string, i: number) => {
          const trimmed = p.trim();
          if (trimmed.startsWith('# ') || trimmed.startsWith('## ') || trimmed.startsWith('### ')) {
            const heading = trimmed.replace(/^#{1,3}\s*/, '');
            return <Text key={i} style={styles.heading}>{heading}</Text>;
          }
          if (trimmed.startsWith('- ') || trimmed.startsWith('• ')) {
            return <Text key={i} style={styles.listItem}>• {trimmed.substring(2)}</Text>;
          }
          return <Text key={i} style={styles.paragraph}>{trimmed}</Text>;
        })}
      </View>
      <View style={{ height: 40 }} />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: Colors.background },
  cover: { width: '100%', height: 200, backgroundColor: Colors.borderLight },
  content: { padding: 20 },
  title: { fontSize: 24, fontWeight: '800', color: Colors.text, lineHeight: 32, marginBottom: 8 },
  date: { fontSize: 13, color: Colors.textMuted, marginBottom: 20 },
  heading: { fontSize: 19, fontWeight: '700', color: Colors.text, marginTop: 24, marginBottom: 10, lineHeight: 26 },
  paragraph: { fontSize: 16, color: Colors.textSecondary, lineHeight: 26, marginBottom: 14 },
  listItem: { fontSize: 16, color: Colors.textSecondary, lineHeight: 26, marginBottom: 6, paddingLeft: 8 },
  errorText: { fontSize: 16, color: Colors.textSecondary, textAlign: 'center', paddingTop: 60 },
});
