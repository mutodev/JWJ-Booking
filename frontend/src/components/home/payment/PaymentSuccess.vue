<template>
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-8 col-lg-6 text-center">
        <div class="success-card">
          <!-- Loading state -->
          <template v-if="loading">
            <div class="spinner-border text-success mb-4" role="status" style="width: 4rem; height: 4rem;">
              <span class="visually-hidden">Verifying payment...</span>
            </div>
            <h1 class="mb-3">Verifying Payment...</h1>
            <p class="text-muted mb-4">
              Please wait while we confirm your payment.
            </p>
          </template>

          <!-- Success state -->
          <template v-else-if="verified">
            <div class="success-icon mb-4">
              <i class="bi bi-check-circle-fill"></i>
            </div>
            <h1 class="mb-3">Payment Successful!</h1>
            <p class="text-muted mb-4">
              Thank you for your payment. Your reservation has been confirmed and you will receive a confirmation email shortly.
            </p>
            <div class="d-grid gap-2">
              <a href="/" class="btn btn-primary btn-lg">
                Back to Home
              </a>
            </div>
          </template>

          <!-- Error state -->
          <template v-else>
            <div class="error-icon mb-4">
              <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            <h1 class="mb-3">Payment Verification</h1>
            <p class="text-muted mb-4">
              {{ errorMessage || 'We could not verify your payment at this time. If you completed the payment, please contact us for assistance.' }}
            </p>
            <div class="d-grid gap-2">
              <a href="/" class="btn btn-primary btn-lg">
                Back to Home
              </a>
            </div>
          </template>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';

const route = useRoute();

const loading = ref(true);
const verified = ref(false);
const errorMessage = ref('');

onMounted(async () => {
  const sessionId = route.query.session_id;

  if (!sessionId) {
    loading.value = false;
    errorMessage.value = 'No payment session found.';
    return;
  }

  try {
    await axios.get('/api/stripe/verify-payment', {
      params: { session_id: sessionId }
    });
    verified.value = true;
  } catch (error) {
    console.error('Payment verification error:', error);
    errorMessage.value = error.response?.data?.message || 'Could not verify payment. Please contact support.';
  } finally {
    loading.value = false;
  }
});
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
