<template>
  <PrivacyPolicyModal
    v-if="showsPrivacyNotice"
    @consent-changed="handleConsentChanged"
  />
  <router-view />
</template>

<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import PrivacyPolicyModal from '@/components/home/PrivacyPolicyModal.vue';
import { setMarketingConsent } from '@/services/marketingTracking.js';

const route = useRoute();
const publicCustomerRoutes = new Set([
  'home',
  'confirmation-update',
  'payment-success',
  'payment-cancel',
  'pay-gateway',
]);

const showsPrivacyNotice = computed(() => publicCustomerRoutes.has(route.name));

function handleConsentChanged(preference) {
  setMarketingConsent(preference === 'all');
}
</script>
