<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { login } from '@/services/auth';

const router = useRouter();

const email = ref('');
const password = ref('');
const remember = ref(false);
const loading = ref(false);
const error = ref('');

const handleSubmit = async () => {
  loading.value = true;
  error.value = '';

  try {
    const user = await login(email.value, password.value, remember.value);

    const roleCodes = (user.roles || []).map((r) => r.code);

    if (roleCodes.some((r) => ['admin', 'operator', 'marketer', 'agent', 'sales_director'].includes(r))) {
      router.push({ name: 'AdminDashboard' });
    } else if (roleCodes.some((r) => ['customer_b2b', 'customer_b2c'].includes(r))) {
      router.push({ name: 'ClientDashboard' });
    } else {
      router.push({ name: 'Home' });
    }
  } catch (e) {
    error.value = e.response?.data?.message || 'Autentificare eșuată';
  } finally {
    loading.value = false;
  }
};
</script>
