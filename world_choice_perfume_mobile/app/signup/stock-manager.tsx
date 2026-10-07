import { SignupForm, type SignupConfig } from '../../components/SignupForm';

/**
 * SIGNUP PAGE — Stock Manager (resources/views/auth/register-stock-manager.blade.php).
 * Branch selector + Company Secret Code; emerald submit button like the Blade.
 */
const config: SignupConfig = {
  type: 'stock-manager',
  title: 'Join Our Team',
  subtitle: 'Register as a Stock Manager at World Choice Perfume',
  buttonLabel: 'Register as Stock Manager',
  buttonColor: '#10B981', // emerald-500 → emerald-600 gradient on the website
  buttonTextColor: '#FFFFFF',
  nameLabel: 'Full Name',
  branchLabel: 'Branch',
  secretCode: true,
  photo: false,
  note: 'Your account will be reviewed by the branch admin before activation.',
};

export default function StockManagerSignupScreen() {
  return <SignupForm config={config} />;
}
