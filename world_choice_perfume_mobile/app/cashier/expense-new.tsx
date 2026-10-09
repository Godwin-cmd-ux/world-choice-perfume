import { router } from 'expo-router';
import { useState } from 'react';
import { AuthField, Banner } from '../../components/authkit';
import { AdminPage, BusyOverlay, Chip, ChipRow, GroupLabel } from '../../components/adminkit';
import { GoldButton } from '../../components/ui';
import { createCashierExpense } from '../../lib/cashierApi';
import { CASHIER_ACCENT } from '../../lib/theme';
import { cashierMenu } from '../../components/cashiersidebar';

/** The six categories the website's store rule accepts — hardcoded there too. */
const CATEGORIES = ['electricity', 'water', 'transport', 'cleaning', 'packaging', 'other'];

/**
 * Record Expense — the mobile twin of cashier/expenses/create + POST
 * /api/cashier/expenses. The server's rules are the website's verbatim:
 * category from the six, amount ≥ 0.01, description at least 10 characters,
 * and the write is refused while monitoring another branch (403).
 */
export default function CashierExpenseNew() {
  const [category, setCategory] = useState('other');
  const [amount, setAmount] = useState('');
  const [description, setDescription] = useState('');

  const [busy, setBusy] = useState(false);
  const [err, setErr] = useState<string | null>(null);
  const [fields, setFields] = useState<Record<string, string>>({});

  const submit = async () => {
    setBusy(true);
    setErr(null);
    setFields({});
    try {
      await createCashierExpense({
        category,
        amount: Number(amount),
        description: description.trim(),
      });
      router.back();
    } catch (e) {
      const error = e as { message?: string; fields?: Record<string, string> };
      setErr(error.message ?? 'The expense could not be recorded.');
      setFields(error.fields ?? {});
    } finally {
      setBusy(false);
    }
  };

  const amountValue = Number(amount || 0);
  const canSubmit = Boolean(category) && amountValue >= 0.01 && description.trim().length >= 10;

  return (
    <AdminPage title="Record Expense" eyebrow="Cashier" accent={CASHIER_ACCENT.main} onBack={() => router.back()} onMenu={cashierMenu.open}>
      {err ? <Banner kind="error" message={err} /> : null}

      <GroupLabel>Category</GroupLabel>
      <ChipRow>
        {CATEGORIES.map((c) => (
          <Chip
            key={c}
            label={c.charAt(0).toUpperCase() + c.slice(1)}
            active={category === c}
            onPress={() => setCategory(c)}
          />
        ))}
      </ChipRow>

      <GroupLabel>Amount</GroupLabel>
      <AuthField
        label="Amount (TZS)"
        value={amount}
        onChangeText={setAmount}
        placeholder="0"
        icon="cash-outline"
        keyboardType="numeric"
        error={fields.amount}
      />

      <GroupLabel>Description</GroupLabel>
      <AuthField
        label="What was it for?"
        value={description}
        onChangeText={setDescription}
        placeholder="e.g. Monthly electricity prepayment for the shop"
        icon="create-outline"
        error={fields.description}
      />
      <Banner
        kind={description.trim().length >= 10 ? 'success' : 'connection'}
        message="The website requires at least 10 characters so the record is useful later."
      />

      <GoldButton
        label={busy ? 'Recording…' : 'Record Expense'}
        icon="checkmark-circle-outline"
        loading={busy}
        disabled={!canSubmit}
        onPress={submit}
        style={{ marginTop: 14 }}
      />

      <BusyOverlay visible={busy} label="Recording…" />
    </AdminPage>
  );
}
