import { SignupForm, type SignupConfig } from '../../components/SignupForm';
import { BUTTON } from '../../lib/theme';

/**
 * SIGNUP PAGE — Branch Admin (resources/views/auth/register-branch-admin.blade.php).
 * Linked from the login page as "Admin Sign Up". Uses the fancy
 * "Select Your Branch" label, carries the Company Secret Code field, and —
 * like its Blade counterpart — shows no approval note under the button.
 */
const config: SignupConfig = {
  type: 'branch-admin',
  title: 'Branch Admin',
  subtitle: 'Register as a Branch Administrator',
  buttonLabel: 'Register as Branch Admin',
  buttonColor: BUTTON.fill, // brand button colour — #F89A1E in every theme
  buttonTextColor: '#0D0D0D', // text-dark-900
  nameLabel: 'Your Full Name',
  branchLabel: 'Select Your Branch',
  secretCode: true,
  photo: false,
};

export default function BranchAdminSignupScreen() {
  return <SignupForm config={config} />;
}
