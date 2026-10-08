import { router, useLocalSearchParams } from 'expo-router';
import { useEffect, useState } from 'react';
import { Image, Pressable, StyleSheet, Text, TextInput, View } from 'react-native';
import * as ImagePicker from 'expo-image-picker';
import { AuthField, Banner } from '../../components/authkit';
import { AdminPage, BusyOverlay, Chip, ChipRow, DataCard, GroupLabel, useAsyncData } from '../../components/adminkit';
import { LoadingView, ErrorView, GoldButton } from '../../components/ui';
import { approveNews, createNews, fetchNews, fetchNewsForm, rejectNews, updateNews } from '../../lib/careApi';
import { CC_ACCENT, COLORS, RADIUS } from '../../lib/theme';

/**
 * Compose / edit custom news — HQ only. Without an id it is the website's
 * customer-care/news/create (publishes immediately, reviewed_by stamped as
 * the member); with an id it is the edit form. The image is optional and
 * goes through Cloudinary, exactly like the website's upload.
 */
export default function CareNewsForm() {
  const params = useLocalSearchParams<{ id?: string }>();
  const isEdit = Boolean(params.id);

  const { data, error, loading, reload } = useAsyncData<{
    post?: {
      title?: string;
      content?: string;
      branch_id?: number;
      status?: 'approved' | 'pending' | 'rejected';
      rejection_reason?: string | null;
      created_at?: string | null;
    };
    branches?: { id: number; name: string }[];
  }>(
    async () => (isEdit ? await fetchNewsForm(params.id!) : await fetchNews()),
    [params.id],
  );

  const [title, setTitle] = useState('');
  const [content, setContent] = useState('');
  const [branchId, setBranchId] = useState<string>('');
  const [image, setImage] = useState<{ uri: string; name: string; type: string } | null>(null);
  const [seeded, setSeeded] = useState(false);
  const [busy, setBusy] = useState(false);
  const [submitError, setSubmitError] = useState<string | null>(null);
  const [fields, setFields] = useState<Record<string, string>>({});

  useEffect(() => {
    if (!data || seeded) return;
    const post = data.post;
    if (post) {
      setTitle(post.title ?? '');
      setContent(post.content ?? '');
      setBranchId(String(post.branch_id ?? ''));
    }
    setSeeded(true);
  }, [data, seeded]);

  const branches = data?.branches ?? [];
  const post = isEdit ? data?.post : undefined;
  const [moderating, setModerating] = useState<'approve' | 'reject' | null>(null);
  const [rejecting, setRejecting] = useState(false);
  const [reason, setReason] = useState('');

  /** The edit blade's Moderation card: approve, or reject with a reason. */
  const moderate = async (kind: 'approve' | 'reject') => {
    if (!params.id || moderating) return;
    const text = reason.trim();
    if (kind === 'reject' && !text) {
      setSubmitError('Enter a rejection reason — the designer will see it.');
      return;
    }
    setModerating(kind);
    setSubmitError(null);
    try {
      if (kind === 'approve') {
        await approveNews(params.id);
      } else {
        await rejectNews(params.id, text);
      }
      await reload();
      setReason('');
      setRejecting(false);
    } catch (e) {
      setSubmitError((e as { message?: string }).message ?? 'That action failed.');
    } finally {
      setModerating(null);
    }
  };

  const pickImage = async () => {
    const result = await ImagePicker.launchImageLibraryAsync({ mediaTypes: ['images'], quality: 0.85 });
    if (!result.canceled && result.assets[0]) {
      const a = result.assets[0];
      setImage({ uri: a.uri, name: a.fileName ?? `news-${Date.now()}.jpg`, type: a.mimeType ?? 'image/jpeg' });
    }
  };

  const submit = async () => {
    if (busy) return;
    setBusy(true);
    setSubmitError(null);
    setFields({});
    try {
      const fieldsPayload = { title: title.trim(), content: content.trim(), branch_id: branchId };
      if (isEdit) {
        await updateNews(params.id!, fieldsPayload, image);
      } else {
        await createNews(fieldsPayload, image);
      }
      router.back();
    } catch (e) {
      const err = e as { message?: string; fields?: Record<string, string> };
      setSubmitError(err.message ?? 'The post could not be saved.');
      setFields(err.fields ?? {});
    } finally {
      setBusy(false);
    }
  };

  return (
    <AdminPage title={isEdit ? 'Edit Post' : 'Compose News'} eyebrow="Head Quarters" accent={CC_ACCENT.main} onBack={() => router.back()}>
      {error ? <ErrorView message={error} onRetry={reload} /> : null}
      {loading && !data ? <LoadingView label="Loading…" /> : null}
      {submitError ? <Banner kind="error" message={submitError} /> : null}

      <AuthField label="Title" value={title} onChangeText={setTitle} placeholder="Post title" icon="create-outline" autoCapitalize="sentences" error={fields.title} maxLength={255} />
      <AuthField label="Content" value={content} onChangeText={setContent} placeholder="Write the post…" icon="document-text-outline" autoCapitalize="sentences" error={fields.content} />

      <GroupLabel>Publishing branch</GroupLabel>
      <ChipRow>
        {branches.map((b) => (
          <Chip key={b.id} label={b.name} active={branchId === String(b.id)} onPress={() => setBranchId(String(b.id))} />
        ))}
      </ChipRow>
      {fields.branch_id ? <Banner kind="error" message={fields.branch_id} /> : null}

      <GroupLabel>Cover image (optional)</GroupLabel>
      <Pressable onPress={pickImage} style={({ pressed }) => [styles.imagePick, pressed && { opacity: 0.75 }]} accessibilityRole="button">
        {image ? (
          <Image source={{ uri: image.uri }} style={styles.preview} resizeMode="cover" />
        ) : (
          <View style={styles.imagePlaceholder}>
            <Text style={styles.imageHint}>Tap to choose a photo</Text>
          </View>
        )}
      </Pressable>

      <GoldButton
        label={busy ? 'Saving…' : isEdit ? 'Save Changes' : 'Publish'}
        icon="cloud-upload-outline"
        loading={busy}
        disabled={!title.trim() || !content.trim() || !branchId}
        onPress={submit}
        style={{ marginTop: 14 }}
      />

      {post ? (
        <>
          <GroupLabel>Status</GroupLabel>
          <DataCard
            title={
              post.status === 'approved'
                ? 'Approved & Published'
                : post.status === 'rejected'
                  ? 'Rejected'
                  : 'Pending Review'
            }
            badge={post.status ?? 'pending'}
            badgeTone={post.status === 'approved' ? 'success' : post.status === 'rejected' ? 'danger' : 'warning'}
            lines={[
              post.rejection_reason ? `Reason: ${post.rejection_reason}` : null,
              post.created_at ? post.created_at : null,
            ]}
          />

          {post.status !== 'approved' ? (
            <>
              <GroupLabel>Moderation</GroupLabel>
              <GoldButton
                label={moderating === 'approve' ? 'Publishing…' : 'Approve & Publish'}
                icon="checkmark-circle-outline"
                loading={moderating === 'approve'}
                onPress={() => moderate('approve')}
              />
              <Pressable
                onPress={() => setRejecting((v) => !v)}
                style={({ pressed }) => [styles.rejectToggle, pressed && { opacity: 0.7 }]}
                accessibilityRole="button"
              >
                <Text style={styles.rejectToggleText}>{rejecting ? 'Cancel' : 'Reject'}</Text>
              </Pressable>
              {rejecting ? (
                <>
                  <TextInput
                    value={reason}
                    onChangeText={setReason}
                    placeholder="Rejection reason (required) — the designer will see this reason."
                    placeholderTextColor={COLORS.textMuted}
                    multiline
                    maxLength={500}
                    style={styles.reasonInput}
                    autoCapitalize="sentences"
                  />
                  <GoldButton
                    label={moderating === 'reject' ? 'Rejecting…' : 'Confirm Reject'}
                    icon="close-circle-outline"
                    loading={moderating === 'reject'}
                    onPress={() => moderate('reject')}
                  />
                </>
              ) : null}
            </>
          ) : null}
        </>
      ) : null}

      <BusyOverlay visible={busy} label="Saving post…" />
    </AdminPage>
  );
}

const styles = StyleSheet.create({
  imagePick: { borderRadius: RADIUS.md, overflow: 'hidden' },
  preview: { width: '100%', height: 180, borderRadius: RADIUS.md, backgroundColor: COLORS.surface },
  imagePlaceholder: {
    height: 120,
    borderRadius: RADIUS.md,
    borderWidth: 1,
    borderStyle: 'dashed',
    borderColor: CC_ACCENT.border,
    backgroundColor: CC_ACCENT.soft,
    alignItems: 'center',
    justifyContent: 'center',
  },
  imageHint: { color: CC_ACCENT.light, fontWeight: '700', fontSize: 13 },
  reasonInput: {
    minHeight: 84,
    borderWidth: 1,
    borderColor: 'rgba(248, 113, 113, 0.4)',
    backgroundColor: COLORS.bgRaised,
    borderRadius: RADIUS.md,
    padding: 12,
    color: COLORS.text,
    fontSize: 14,
    textAlignVertical: 'top',
  },
  rejectToggle: {
    marginTop: 8,
    borderWidth: 1,
    borderColor: 'rgba(248, 113, 113, 0.4)',
    borderRadius: RADIUS.pill,
    paddingHorizontal: 14,
    paddingVertical: 8,
    alignItems: 'center',
  },
  rejectToggleText: { color: COLORS.danger, fontWeight: '800', fontSize: 12 },
});
