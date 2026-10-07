import { router, useLocalSearchParams } from 'expo-router';
import * as ImagePicker from 'expo-image-picker';
import { useState } from 'react';
import { Image, Pressable, StyleSheet, Text, TextInput, View } from 'react-native';
import { Banner } from '../../components/authkit';
import { AdminPage, BusyOverlay, GroupLabel, useAsyncData } from '../../components/adminkit';
import { ErrorView, GoldButton, LoadingView } from '../../components/ui';
import { errorMessage, isApiError } from '../../lib/api';
import { createGdPost, fetchGdPost, updateGdPost } from '../../lib/gdApi';
import { COLORS, GD_ACCENT, RADIUS } from '../../lib/theme';

/**
 * News create/edit — the mobile twin of the news create/edit Blade forms:
 * same fields (title, content, optional image on create), same server
 * validation surfaced through 422 errors, same save-then-return flow. The
 * website's edit form keeps title + content only (the image is set when the
 * post is first created), so this screen does the same.
 */
export default function GdNewsForm() {
  const params = useLocalSearchParams<{ id?: string }>();
  const editingId = params.id ? String(params.id) : null;

  const existing = useAsyncData(
    () => (editingId ? fetchGdPost(editingId) : Promise.resolve(null)),
    [editingId],
  );

  const [title, setTitle] = useState('');
  const [content, setContent] = useState('');
  const [image, setImage] = useState<{ uri: string; name: string; type: string } | null>(null);
  const [hydrated, setHydrated] = useState(false);

  const [errors, setErrors] = useState<Record<string, string>>({});
  const [formError, setFormError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);

  // Prefill the edit form once the post arrives.
  if (editingId && existing.data?.post && !hydrated) {
    const p = existing.data.post;
    setHydrated(true);
    setTitle(p.title ?? '');
    setContent(p.content ?? '');
  }

  const pickImage = async () => {
    const result = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ['images'],
      allowsEditing: true,
      quality: 0.85,
    });
    if (!result.canceled && result.assets[0]) {
      const a = result.assets[0];
      setImage({ uri: a.uri, name: a.fileName ?? 'news.jpg', type: a.mimeType ?? 'image/jpeg' });
    }
  };

  const submit = async () => {
    if (saving) return;
    setErrors({});
    setFormError(null);
    setSuccess(null);

    // Light client-side mirror of the server rules (server stays authoritative).
    const local: Record<string, string> = {};
    if (!title.trim()) local.title = 'The title field is required.';
    else if (title.trim().length > 255) local.title = 'The title may not be longer than 255 characters.';
    if (!content.trim()) local.content = 'The content field is required.';
    if (Object.keys(local).length) {
      setErrors(local);
      return;
    }

    setSaving(true);
    try {
      const res = editingId
        ? await updateGdPost(editingId, { title: title.trim(), content: content.trim() })
        : await createGdPost({ title: title.trim(), content: content.trim() }, image ?? undefined);
      setSuccess(res.message);
      setTimeout(() => router.back(), 900);
    } catch (e) {
      if (isApiError(e) && e.kind === 'validation') {
        setErrors(e.fields);
        setFormError(e.message);
      } else {
        setFormError(errorMessage(e));
      }
    } finally {
      setSaving(false);
    }
  };

  if (editingId && (existing.loading || (existing.error && !existing.data))) {
    return (
      <AdminPage title="Edit Post" eyebrow="Graphic Designer" accent={GD_ACCENT.main} onBack={() => router.back()}>
        {existing.loading ? (
          <LoadingView label="Loading post…" />
        ) : (
          <ErrorView message={existing.error ?? 'Unable to load.'} onRetry={existing.reload} />
        )}
      </AdminPage>
    );
  }

  return (
    <AdminPage
      title={editingId ? 'Edit Post' : 'New Post'}
      eyebrow="Graphic Designer"
      accent={GD_ACCENT.main}
      onBack={() => router.back()}
    >
      {success ? <Banner kind="success" message={success} /> : null}
      {formError ? <Banner kind="error" message={formError} /> : null}

      <GroupLabel>{editingId ? 'Revise and resubmit' : 'Post details'}</GroupLabel>

      <Field label="Title" required error={errors.title}>
        <TextInput
          value={title}
          onChangeText={setTitle}
          placeholder="e.g. New arrivals this week"
          placeholderTextColor={COLORS.textMuted}
          style={styles.input}
        />
      </Field>

      <Field label="Content" required error={errors.content}>
        <TextInput
          value={content}
          onChangeText={setContent}
          placeholder="Write the post…"
          placeholderTextColor={COLORS.textMuted}
          style={[styles.input, styles.textarea]}
          multiline
          textAlignVertical="top"
        />
      </Field>

      {!editingId ? (
        <>
          <GroupLabel>Cover image (optional)</GroupLabel>
          <Pressable onPress={pickImage} style={({ pressed }) => [styles.photo, pressed && styles.pressed]} accessibilityRole="button">
            {image ? (
              <Image source={{ uri: image.uri }} style={styles.photoImg} resizeMode="cover" />
            ) : (
              <View style={styles.photoEmpty}>
                <Text style={styles.muted}>Add cover image (optional)</Text>
              </View>
            )}
          </Pressable>
        </>
      ) : null}

      <Text style={styles.note}>
        {editingId
          ? 'Edited posts re-enter approval and appear on the site once customer care approves them.'
          : 'Your post will appear on the site once approved by customer care.'}
      </Text>

      <GoldButton
        label={editingId ? 'Resubmit for Approval' : 'Submit for Approval'}
        onPress={submit}
        loading={saving}
        icon="paper-plane-outline"
        style={{ marginTop: 8 }}
      />
      <BusyOverlay visible={saving} label={editingId ? 'Resubmitting…' : 'Submitting…'} />
    </AdminPage>
  );
}

function Field({
  label,
  required,
  error,
  children,
}: {
  label: string;
  required?: boolean;
  error?: string;
  children: React.ReactNode;
}) {
  return (
    <View style={styles.field}>
      <Text style={styles.label}>
        {label}
        {required ? <Text style={{ color: GD_ACCENT.main }}> *</Text> : null}
      </Text>
      {children}
      {error ? <Text style={styles.error}>{error}</Text> : null}
    </View>
  );
}

const styles = StyleSheet.create({
  field: { gap: 6 },
  label: { color: COLORS.textSecondary, fontSize: 13, fontWeight: '700' },
  error: { color: COLORS.danger, fontSize: 12.5 },
  input: {
    backgroundColor: COLORS.surfaceHigh,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: RADIUS.md,
    paddingHorizontal: 13,
    paddingVertical: 12,
    color: COLORS.text,
    fontSize: 15,
  },
  textarea: { minHeight: 150 },
  photo: {
    borderRadius: RADIUS.md,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.surface,
    overflow: 'hidden',
  },
  photoImg: { width: '100%', height: 180 },
  photoEmpty: { height: 110, alignItems: 'center', justifyContent: 'center' },
  muted: { color: COLORS.textMuted, fontSize: 13 },
  note: { color: COLORS.textMuted, fontSize: 12, lineHeight: 17 },
  pressed: { opacity: 0.8 },
});
