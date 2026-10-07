import { SignupForm, type SignupConfig } from '../../components/SignupForm';

/**
 * SIGNUP PAGE — Customer Care (resources/views/auth/register-customer-care.blade.php).
 * Branch selector + Company Secret Code; blue submit button like the Blade.
 */
const config: SignupConfig = {
  type: 'customer-care',
  title: 'Join Our Team',
  subtitle: 'Register Customer Care at World Choice Perfume',
  buttonLabel: 'Register as Customer Care',
  buttonColor: '#3B82F6', // blue-500 → blue-600 gradient on the website
  buttonTextColor: '#FFFFFF',
  nameLabel: 'Full Name',
  branchLabel: 'Branch',
  secretCode: true,
  photo: false,
  note: 'Your account will be reviewed by the admin before activation.',
};

export default function CustomerCareSignupScreen() {
  return <SignupForm config={config} />;
}
