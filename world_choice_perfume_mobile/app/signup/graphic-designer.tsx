import { SignupForm, type SignupConfig } from '../../components/SignupForm';

/**
 * SIGNUP PAGE — Graphic Designer (resources/views/auth/register-graphic-designer.blade.php).
 * The only type without a branch selector; carries the Company Secret Code
 * field; purple submit button like the Blade.
 */
const config: SignupConfig = {
  type: 'graphic-designer',
  title: 'Join Our Team',
  subtitle: 'Register as a Graphic Designer at World Choice Perfume',
  buttonLabel: 'Register as Graphic Designer',
  buttonColor: '#A855F7', // purple-500 → purple-600 gradient on the website
  buttonTextColor: '#FFFFFF',
  nameLabel: 'Full Name',
  secretCode: true,
  photo: false,
  note: 'Your account will be reviewed and approved by the Super Admin before activation.',
};

export default function GraphicDesignerSignupScreen() {
  return <SignupForm config={config} />;
}
