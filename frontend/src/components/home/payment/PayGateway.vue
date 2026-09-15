<template>
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-8 col-lg-6 text-center">
        <div class="success-card">
          <!-- Loading state -->
          <template v-if="loading">
            <div class="spinner-border mb-4" style="width: 4rem; height: 4rem; color: #FF74B7;" role="status">
              <span class="visually-hidden">Validating payment link...</span>
            </div>
            <h1 class="mb-3">Validating your payment link...</h1>
            <p class="text-muted mb-4">
              Please wait a moment while we take you to a secure payment page.
            </p>
          </template>

          <!-- Error state -->
          <template v-else>
            <div class="error-icon mb-4" :class="iconClass">
              <i :class="iconName"></i>
            </div>
            <h1 class="mb-3">{{ errorTitle }}</h1>
            <p class="text-muted mb-4">{{ errorMessage }}</p>
            <div class="d-grid gap-2">
              <a href="/" class="btn btn-primary btn-lg">Back to Home</a>
            </div>
          </template>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import api from '@/services/axios';

const route = useRoute();

const loading = ref(true);
const reason = ref('error');
const errorMessage = ref('');

const errorTitle = computed(() => {
  switch (reason.value) {
    case 'expired':
      return 'This Link Has Expired';
    case 'paid':
      return 'Already Paid';
    case 'cancelled':
      return 'Payment Cancelled';
    default:
      return 'Something Went Wrong';
  }
});

const iconClass = computed(() => (reason.value === 'paid' ? 'success-icon' : 'error-icon'));
const iconName = computed(() =>
  reason.value === 'paid' ? 'bi bi-check-circle-fill' : 'bi bi-exclamation-triangle-fill'
);

onMounted(async () => {
  const token = route.params.token;

  try {
    // The shared axios wrapper's response interceptor unwraps the axios
    // envelope, so `res` here is already the backend body: { message, data }.
    const res = await api.get(`/pay/${token}`);
    const redirectUrl = res?.data?.redirect_url;

    if (redirectUrl) {
      window.location.href = redirectUrl;
      return;
    }

    reason.value = 'error';
    errorMessage.value = 'Could not generate a payment session. Please contact support.';
  } catch (error) {
    const body = error.response?.data || {};
    reason.value = body.reason || 'error';
    errorMessage.value = body.message || defaultMessageFor(reason.value);
  } finally {
    loading.value = false;
  }
});

function defaultMessageFor(r) {
  switch (r) {
    case 'expired':
      return 'This payment link is no longer valid. Please contact us and we will send you a new one.';
    case 'paid':
      return 'This has already been paid — no further action is needed.';
    case 'cancelled':
      return 'This payment has been cancelled.';
    default:
      return 'We could not process this payment link. Please contact us for assistance.';
  }
}
</script>

<style scoped>
.success-card {
  background: white;
  border-radius: 16px;
  padding: 3rem 2rem;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
}

.success-icon i {
  font-size: 5rem;
  color: #22c55e;
}

.error-icon i {
  font-size: 5rem;
  color: #f59e0b;
}

h1 {
  font-size: 1.8rem;
  font-weight: 700;
  color: #1f2937;
}

.btn-primary {
  background-color: #FF74B7;
  border-color: #FF74B7;
  border-radius: 50px;
  padding: 0.75rem 2rem;
  font-weight: 600;
}

.btn-primary:hover {
  background-color: #e662a5;
  border-color: #e662a5;
}
</style>
