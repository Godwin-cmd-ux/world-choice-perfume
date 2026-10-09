import { Ionicons } from '@expo/vector-icons';
import { Image } from 'expo-image';
import { useCallback, useEffect, useState } from 'react';
import { RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { ScreenHeader } from '../../components/ScreenHeader';
import { EmptyView, ErrorView, LoadingView, SectionHeading } from '../../components/ui';
import { errorMessage, fetchNews, type NewsPost } from '../../lib/api';
import { COLORS, RADIUS } from '../../lib/theme';

/**
 * NEWS — the website's /news page (customer/news.blade.php) as a dedicated
 * mobile page: the same "Stay Updated" eyebrow, "Latest News" heading and
 * intro, then one card per published post with its image, branch chip, date,
 * title and content, and the same empty state. Posts come from GET /api/news,
 * the JSON twin of the very controller that renders the Blade page, so the
 * app and the website always list the same announcements.
 */
const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

/** "Mar 12, 2026" — the Blade formats with Carbon in Africa/Dar_es_Salaam. */
function formatPostDate(iso: string | null | undefined): string {
  if (!iso) return '';
  const parsed = new Date(iso);
  if (Number.isNaN(parsed.getTime())) return '';
  const local = new Date(parsed.getTime() + 3 * 60 * 60 * 1000);
  return `${MONTHS[local.getUTCMonth()]} ${local.getUTCDate()}, ${local.getUTCFullYear()}`;
}

type Status = 'loading' | 'error' | 'ready';

export default function NewsScreen() {
  const [status, setStatus] = useState<Status>('loading');
  const [posts, setPosts] = useState<NewsPost[]>([]);
  const [error, setError] = useState('');
  const [refreshing, setRefreshing] = useState(false);

  const load = useCallback(async () => {
    setError('');
    try {
      const payload = await fetchNews();
      setPosts(Array.isArray(payload.posts) ? payload.posts : []);
      setStatus('ready');
    } catch (e) {
      setError(errorMessage(e));
      setStatus('error');
    }
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    try {
      await load();
    } finally {
      setRefreshing(false);
    }
  }, [load]);

  const body = () => {
    if (status === 'loading') return <LoadingView label="Loading news…" />;
    if (status === 'error') return <ErrorView message={error} onRetry={() => load()} />;

    return (
      <ScrollView
        contentContainerStyle={styles.content}
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={COLORS.gold} colors={[COLORS.gold]} />
        }
      >
        <SectionHeading eyebrow="Stay Updated" title="Latest" accent="News" />
        <Text style={styles.intro}>
          Stay up to date with our latest announcements, offers, and updates from all branches.
        </Text>

        {posts.length === 0 ? (
          <EmptyView icon="newspaper-outline" title="No news posts yet." hint="Check back soon!" />
        ) : (
          <View style={styles.list}>
            {posts.map((post, index) => (
              <View key={String(post.id ?? index)} style={styles.article}>
                {post.image_url ? (
                  <Image source={{ uri: post.image_url }} style={styles.articleImage} contentFit="cover" transition={200} />
                ) : null}
                <View style={styles.articleBody}>
                  <View style={styles.metaRow}>
                    {post.branch?.name ? (
                      <View style={styles.branchChip}>
                        <Ionicons name="storefront-outline" size={11} color={COLORS.goldBright} />
                        <Text style={styles.branchChipText}>{post.branch.name}</Text>
                      </View>
                    ) : null}
                    <Text style={styles.date}>{formatPostDate(post.created_at)}</Text>
                  </View>
                  <Text style={styles.articleTitle}>{post.title}</Text>
                  <Text style={styles.articleContent}>{post.content}</Text>
                </View>
              </View>
            ))}
          </View>
        )}
      </ScrollView>
    );
  };

  return (
    <SafeAreaView style={styles.safe} edges={['top']}>
      <ScreenHeader title="News" subtitle="Stay Updated" />
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
    paddingBottom: 28,
    gap: 6,
  },
  intro: {
    color: COLORS.textSecondary,
    fontSize: 13.5,
    lineHeight: 21,
    marginBottom: 8,
  },
  list: {
    gap: 18,
  },
  article: {
    backgroundColor: COLORS.surface,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: RADIUS.lg,
    overflow: 'hidden',
  },
  articleImage: {
    width: '100%',
    height: 180,
    backgroundColor: COLORS.surfaceHigh,
  },
  articleBody: {
    padding: 16,
    gap: 8,
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
  articleTitle: {
    color: COLORS.text,
    fontSize: 18,
    fontWeight: '800',
    lineHeight: 24,
  },
  articleContent: {
    color: COLORS.textSecondary,
    fontSize: 13.5,
    lineHeight: 21,
  },
});
