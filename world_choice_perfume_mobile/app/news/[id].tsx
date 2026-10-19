import { Ionicons } from '@expo/vector-icons';
import { Image } from 'expo-image';
import { router, useLocalSearchParams } from 'expo-router';
import { useCallback, useEffect, useState } from 'react';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { ScreenHeader } from '../../components/ScreenHeader';
import { EmptyView, ErrorView, LoadingView } from '../../components/ui';
import { errorMessage, fetchNewsPost, type NewsPost } from '../../lib/api';
import { COLORS, RADIUS } from '../../lib/theme';
import { formatPostDate } from '../(tabs)/news';

/**
 * NEWS POST — one published announcement, read whole (GET /api/news/{post},
 * the JSON twin of the website's /news/{post} page). The news tab shows an
 * excerpt of every card and opens this screen on a tap; the back button in
 * the header returns to the list.
 */
export default function NewsPostScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const [post, setPost] = useState<NewsPost | null>(null);
  const [status, setStatus] = useState<'loading' | 'error' | 'ready'>('loading');
  const [error, setError] = useState('');

  const load = useCallback(async () => {
    setError('');
    setStatus('loading');
    try {
      const payload = await fetchNewsPost(id ?? '');
      setPost(payload.post ?? null);
      setStatus('ready');
    } catch (e) {
      setError(errorMessage(e));
      setStatus('error');
    }
  }, [id]);

  useEffect(() => {
    load();
  }, [load]);

  const paragraphs = (post?.content ?? '')
    .split(/\R+/)
    .map((line) => line.trim())
    .filter((line) => line !== '');

  const body = () => {
    if (status === 'loading') return <LoadingView label="Loading post…" />;
    if (status === 'error') return <ErrorView message={error} onRetry={() => load()} />;
    if (!post) {
      return <EmptyView icon="newspaper-outline" title="Post not found." hint="It may have been unpublished. Go back to the list for the rest." />;
    }

    return (
      <ScrollView contentContainerStyle={styles.content} showsVerticalScrollIndicator={false}>
        {post.image_url ? (
          <Image source={{ uri: post.image_url }} style={styles.image} contentFit="cover" transition={200} />
        ) : null}

        <View style={styles.metaRow}>
          {post.branch?.name ? (
            <View style={styles.branchChip}>
              <Ionicons name="storefront-outline" size={11} color={COLORS.goldBright} />
              <Text style={styles.branchChipText}>{post.branch.name}</Text>
            </View>
          ) : null}
          <Text style={styles.date}>{formatPostDate(post.created_at)}</Text>
        </View>

        <Text style={styles.title}>{post.title}</Text>

        <View style={styles.bodyWrap}>
          {paragraphs.map((paragraph, index) => (
            <Text key={String(index)} style={styles.paragraph}>
              {paragraph}
            </Text>
          ))}
        </View>

        <Pressable
          onPress={() => router.back()}
          style={({ pressed }) => [styles.backLink, pressed && { opacity: 0.7 }]}
          accessibilityRole="button"
        >
          <Ionicons name="arrow-back" size={14} color={COLORS.goldBright} />
          <Text style={styles.backLinkText}>All news</Text>
        </Pressable>
      </ScrollView>
    );
  };

  return (
    <SafeAreaView style={styles.safe} edges={['top']}>
      <ScreenHeader title="News" subtitle="Article" />
      {body()}
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: {
    flex: 1,
    backgroundColor: COLORS.bg,
  },
  content: {
    paddingHorizontal: 18,
    paddingBottom: 32,
    gap: 12,
  },
  image: {
    width: '100%',
    height: 210,
    borderRadius: RADIUS.lg,
    backgroundColor: COLORS.bgRaised,
  },
  metaRow: {
    flexDirection: 'row',
    alignItems: 'center',
    flexWrap: 'wrap',
    gap: 10,
  },
  branchChip: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    backgroundColor: COLORS.goldSoft,
    borderWidth: 1,
    borderColor: COLORS.goldBorder,
    borderRadius: RADIUS.pill,
    paddingHorizontal: 10,
    paddingVertical: 4,
  },
  branchChipText: {
    color: COLORS.goldBright,
    fontSize: 11,
    fontWeight: '600',
  },
  date: {
    color: COLORS.textMuted,
    fontSize: 11.5,
  },
  title: {
    color: COLORS.text,
    fontSize: 22,
    fontWeight: '800',
    lineHeight: 29,
  },
  bodyWrap: {
    gap: 10,
  },
  paragraph: {
    color: COLORS.textSecondary,
    fontSize: 14.5,
    lineHeight: 23,
  },
  backLink: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    alignSelf: 'center',
    marginTop: 10,
    backgroundColor: COLORS.goldSoft,
    borderWidth: 1,
    borderColor: COLORS.goldBorder,
    borderRadius: RADIUS.pill,
    paddingHorizontal: 14,
    paddingVertical: 8,
  },
  backLinkText: {
    color: COLORS.goldBright,
    fontWeight: '700',
    fontSize: 12,
  },
});
