import { SignupForm, type SignupConfig } from '../../components/SignupForm';

/**
 * SIGNUP PAGE — Seller (resources/views/auth/register-seller.blade.php).
 * Branch selector + Company Secret Code; cyan submit button like the Blade.
 */
const config: SignupConfig = {
  type: 'seller',
  title: 'Join Our Team',
  subtitle: 'Register as a Seller at World Choice Perfume',
  buttonLabel: 'Register as Seller',
  buttonColor: '#06B6D4', // cyan-500 → cyan-600 gradient on the website
  buttonTextColor: '#FFFFFF',
  nameLabel: 'Full Name',
  branchLabel: 'Branch',
  secretCode: true,
  photo: false,
  note: 'Your account will be reviewed by the admin before activation.',
};

export default function SellerSignupScreen() {
  return <SignupForm config={config} />;
}
