import { SignupForm, type SignupConfig } from '../../components/SignupForm';

/**
 * SIGNUP PAGE — Cashier (resources/views/auth/register-cashier.blade.php).
 * The only type without a secret-code field and the only one with an
 * optional profile photo (multipart), matching the Blade form exactly.
 */
const config: SignupConfig = {
  type: 'cashier',
  title: 'Join Our Team',
  subtitle: 'Register as a Cashier at World Choice Perfume',
  buttonLabel: 'Register as Cashier',
  buttonColor: '#C8A02A', // gold-500 → gold-600 gradient on the website
  buttonTextColor: '#0D0D0D', // text-dark-900
  nameLabel: 'Full Name',
  branchLabel: 'Branch',
  secretCode: false,
  photo: true,
  note: 'Your account will be reviewed by the branch admin before activation.',
};

export default function CashierSignupScreen() {
  return <SignupForm config={config} />;
}
