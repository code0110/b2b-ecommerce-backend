<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { registerB2C, registerB2B, login } from '@/services/auth';

const router = useRouter();
const activeTab = ref('b2c'); // 'b2c' sau 'b2b'

// B2C
const b2c = ref({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
  accept_terms: false,
});

const b2cError = ref('');
const b2cLoading = ref(false);

const submitB2C = async () => {
  b2cError.value = '';

  if (!b2c.value.accept_terms) {
    b2cError.value = 'Trebuie să accepți Termenii și Condițiile.';
    return;
  }

  b2cLoading.value = true;

  try {
    await registerB2C({ ...b2c.value });

    const user = await login(b2c.value.email, b2c.value.password);

    const roleCodes = (user.roles || []).map((r) => r.code);
    if (roleCodes.includes('customer_b2c')) {
      router.push({ name: 'ClientDashboard' });
    } else {
      router.push({ name: 'Home' });
    }
  } catch (e) {
    b2cError.value = e.response?.data?.message || 'Înregistrarea B2C a eșuat.';
  } finally {
    b2cLoading.value = false;
  }
};

// B2B
const b2b = ref({
  company_name: '',
  cui: '',
  reg_com: '',
  iban: '',
  contact_name: '',
  email: '',
  phone: '',
  main_address: '',
  want_to_be_partner: false,
});

const b2bError = ref('');
const b2bLoading = ref(false);

const submitB2B = async () => {
  b2bError.value = '';
  b2bLoading.value = true;

  try {
    await registerB2B({ ...b2b.value });

    // de exemplu după B2B trimiți userul la login
    router.push({ name: 'Login' });
  } catch (e) {
    b2bError.value = e.response?.data?.message || 'Înregistrarea B2B a eșuat.';
  } finally {
    b2bLoading.value = false;
  }
};
</script>
