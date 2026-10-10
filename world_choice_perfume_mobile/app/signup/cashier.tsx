import { SignupForm, type SignupConfig } from '../../components/SignupForm';
import { BUTTON } from '../../lib/theme';

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
  buttonColor: BUTTON.fill, // brand button colour — #F89A1E in every theme
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
