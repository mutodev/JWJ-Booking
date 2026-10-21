<template>
  <el-dialog
    :model-value="modelValue"
    width="min(92vw, 500px)"
    :close-on-click-modal="!busy"
    :close-on-press-escape="!busy"
    class="verification-dialog"
    @close="close"
  >
    <div class="verification-content">
      <div class="shield"><i class="bi bi-shield-check"></i></div>
      <h2>One quick verification</h2>
      <p class="subtitle">Choose where you’d like to receive your 6-digit code. This helps us protect your reservation request.</p>

      <div v-if="!challengeId" class="method-list">
        <button type="button" class="method-card" :disabled="busy" @click="requestCode('email')">
          <span class="method-icon"><i class="bi bi-envelope"></i></span>
          <span><strong>Email me</strong><small>{{ maskedEmail }}</small></span>
          <i class="bi bi-chevron-right arrow"></i>
        </button>
        <button type="button" class="method-card method-card--disabled" disabled aria-disabled="true">
          <span class="method-icon"><i class="bi bi-chat-dots"></i></span>
          <span>
            <strong>Text me <span class="coming-soon">Coming soon</span></strong>
            <small>{{ maskedPhone }}</small>
          </span>
          <i class="bi bi-lock-fill arrow"></i>
        </button>
        <div v-if="error" class="verification-error">{{ error }}</div>
      </div>

      <div v-else class="code-step">
        <p class="sent-message">We sent a code to <strong>{{ destination }}</strong>.</p>
        <input
          ref="codeInput"
          v-model="code"
          class="code-input"
          inputmode="numeric"
          autocomplete="one-time-code"
          maxlength="6"
          aria-label="6-digit verification code"
          placeholder="000000"
          @input="sanitizeCode"
          @keyup.enter="verifyCode"
        />
        <div v-if="error" class="verification-error">{{ error }}</div>
        <el-button class="verify-button" :loading="busy" :disabled="code.length !== 6" @click="verifyCode">
          Verify and submit
        </el-button>
        <div class="secondary-actions">
          <button type="button" class="link-button" :disabled="busy || countdown > 0" @click="requestCode(method)">
            {{ countdown > 0 ? `Resend in ${countdown}s` : 'Resend code' }}
          </button>
          <span>·</span>
          <button type="button" class="link-button" :disabled="busy" @click="chooseAnother">Use another method</button>
        </div>
      </div>
    </div>
  </el-dialog>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import api from '@/services/axios';

const props = defineProps({
  modelValue: Boolean,
  sessionId: { type: String, required: true },
  customer: { type: Object, default: () => ({}) },
});
const emit = defineEmits(['update:modelValue', 'verified']);
const challengeId = ref('');
const method = ref('');
const destination = ref('');
const code = ref('');
const error = ref('');
const busy = ref(false);
const countdown = ref(0);
const codeInput = ref(null);
let timer = null;

const maskedEmail = computed(() => {
  const [local = '', domain = ''] = String(props.customer.email || '').split('@');
  return `${local.slice(0, 1)}${'*'.repeat(Math.max(2, local.length - 1))}@${domain}`;
});
const maskedPhone = computed(() => {
  const digits = String(props.customer.phone || '').replace(/\D/g, '');
  return `•••• ${digits.slice(-4)}`;
});

function close() { if (!busy.value) emit('update:modelValue', false); }
function reset() {
  challengeId.value = ''; method.value = ''; destination.value = '';
  code.value = ''; error.value = ''; countdown.value = 0;
  if (timer) clearInterval(timer);
}
function chooseAnother() { reset(); }
function startCountdown(seconds = 60) {
  countdown.value = seconds;
  if (timer) clearInterval(timer);
  timer = setInterval(() => {
    countdown.value--;
    if (countdown.value <= 0) clearInterval(timer);
  }, 1000);
}
async function requestCode(selectedMethod) {
  busy.value = true; error.value = '';
  try {
    const response = await api.post('/home/contact-verification/request', {
      session_id: props.sessionId, method: selectedMethod,
      email: props.customer.email, phone: props.customer.phone,
      first_name: props.customer.firstName,
    });
    const data = response.data?.data || response.data;
    challengeId.value = data.challenge_id;
    method.value = selectedMethod;
    destination.value = data.destination;
    code.value = '';
    startCountdown(data.resend_in || 60);
    await nextTick();
    codeInput.value?.focus();
  } catch (e) {
    error.value = e.response?.data?.message || 'We could not send the code. Please try again.';
  } finally { busy.value = false; }
}
function sanitizeCode() { code.value = code.value.replace(/\D/g, '').slice(0, 6); error.value = ''; }
async function verifyCode() {
  if (busy.value || code.value.length !== 6) return;
  busy.value = true; error.value = '';
  try {
    const response = await api.post('/home/contact-verification/verify', {
      challenge_id: challengeId.value, code: code.value,
    });
    const data = response.data?.data || response.data;
    emit('verified', data.verification_token);
    emit('update:modelValue', false);
    reset();
  } catch (e) {
    error.value = e.response?.data?.message || 'We could not verify that code.';
    code.value = '';
    await nextTick(); codeInput.value?.focus();
  } finally { busy.value = false; }
}

watch(() => props.modelValue, (open) => { if (!open) reset(); });
onBeforeUnmount(() => { if (timer) clearInterval(timer); });
</script>

<style scoped>
.verification-content { text-align:center; padding:4px 12px 14px; }
.shield { width:64px;height:64px;margin:0 auto 16px;border-radius:50%;display:grid;place-items:center;background:#FFF0F6;color:#db398a;font-size:30px; }
h2 { margin:0 0 8px;color:#1f2937;font-size:24px; }
.subtitle { color:#6b7280;line-height:1.55;margin:0 auto 24px;max-width:410px; }
.method-list { display:grid;gap:12px; }
.method-card { width:100%;display:grid;grid-template-columns:44px 1fr auto;align-items:center;gap:12px;padding:14px;text-align:left;background:#fff;border:1px solid #e5e7eb;border-radius:12px;cursor:pointer;transition:.2s; }
.method-card:hover { border-color:#FF74B7;box-shadow:0 4px 12px rgba(255,116,183,.15);transform:translateY(-1px); }
.method-card:disabled { opacity:.6;cursor:wait; }
.method-card--disabled { opacity:.62;cursor:not-allowed;background:#f9fafb; }
.method-card--disabled:hover { border-color:#e5e7eb;box-shadow:none;transform:none; }
.coming-soon { display:inline-block;margin-left:6px;padding:2px 7px;border-radius:999px;background:#e5e7eb;color:#6b7280;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.03em;vertical-align:middle; }
.method-icon { width:44px;height:44px;border-radius:10px;background:#FFF0F6;display:grid;place-items:center;color:#db398a;font-size:20px; }
.method-card strong,.method-card small { display:block; }.method-card small { color:#6b7280;margin-top:3px; }.arrow { color:#9ca3af; }
.sent-message { color:#4b5563;margin:0 0 16px; }
.code-input { display:block;width:min(100%,280px);margin:0 auto 14px;padding:14px 16px;border:2px solid #d1d5db;border-radius:10px;text-align:center;font-size:28px;font-weight:700;letter-spacing:10px;outline:none; }
.code-input:focus { border-color:#FF74B7;box-shadow:0 0 0 3px rgba(255,116,183,.15); }
.verify-button { width:min(100%,280px);height:46px!important;background:#FF74B7!important;border-color:#FF74B7!important;color:#111!important;font-weight:700!important; }
.verification-error { margin:8px 0 12px;color:#dc2626;font-size:14px; }
.secondary-actions { margin-top:16px;color:#9ca3af;display:flex;justify-content:center;gap:8px; }
.link-button { border:0;background:none;padding:0;color:#6b7280;text-decoration:underline;cursor:pointer; }.link-button:disabled { opacity:.5;cursor:default; }
@media (max-width:480px) { .verification-content { padding-inline:0; }.code-input { font-size:24px;letter-spacing:7px; } }
</style>
