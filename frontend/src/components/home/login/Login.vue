<template>
  <div class="d-flex align-items-center justify-content-center min-vh-100 bg-light py-4">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-8 col-lg-5">
          <div class="card shadow-sm">
            <div class="card-body p-4">
              <template v-if="stage === 'credentials'">
                <h4 class="card-title mb-4 text-center">
                  <i class="bi bi-person-fill"></i> Sign In
                </h4>

                <form @submit.prevent="submitCredentials">
                  <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input id="email" type="email" v-model.trim="email" class="form-control" autocomplete="username" required />
                  </div>
                  <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input id="password" type="password" v-model="password" class="form-control" autocomplete="current-password" minlength="6" required />
                  </div>
                  <button type="submit" class="btn btn-primary w-100" :disabled="submitting">
                    <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
                    <i v-else class="bi bi-box-arrow-in-right me-1"></i>
                    Continue
                  </button>
                </form>
              </template>

              <template v-else-if="stage === 'setup'">
                <h4 class="card-title text-center mb-2">
                  <i class="bi bi-shield-lock-fill"></i> Set up two-step verification
                </h4>
                <p class="text-muted text-center small mb-3">
                  Scan this QR code with Google Authenticator or Microsoft Authenticator.
                </p>

                <div class="text-center mb-3">
                  <img v-if="qrDataUrl" :src="qrDataUrl" class="mfa-qr" alt="Authenticator setup QR code" />
                </div>

                <div class="mb-3">
                  <label class="form-label">Manual setup key</label>
                  <div class="input-group">
                    <input class="form-control font-monospace" :value="secret" readonly />
                    <button class="btn btn-outline-secondary" type="button" @click="copySecret">Copy</button>
                  </div>
                </div>

                <form @submit.prevent="submitMfa">
                  <div class="mb-3">
                    <label for="setup-code" class="form-label">Enter the 6-digit code to confirm</label>
                    <input
                      id="setup-code"
                      v-model.trim="code"
                      class="form-control text-center code-input"
                      inputmode="numeric"
                      autocomplete="one-time-code"
                      maxlength="6"
                      pattern="[0-9]{6}"
                      required
                    />
                  </div>
                  <button type="submit" class="btn btn-primary w-100" :disabled="submitting">
                    <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
                    Activate authenticator
                  </button>
                </form>
              </template>

              <template v-else-if="stage === 'verify'">
                <h4 class="card-title text-center mb-2">
                  <i class="bi bi-shield-check"></i> Verification code
                </h4>
                <p class="text-muted text-center small mb-4">
                  Enter the code from your authenticator app or one of your recovery codes.
                </p>

                <form @submit.prevent="submitMfa">
                  <div class="mb-3">
                    <input
                      id="mfa-code"
                      v-model.trim="code"
                      class="form-control text-center code-input"
                      autocomplete="one-time-code"
                      placeholder="000000 or recovery code"
                      required
                      autofocus
                    />
                  </div>
                  <button type="submit" class="btn btn-primary w-100" :disabled="submitting">
                    <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
                    Verify and sign in
                  </button>
                  <button type="button" class="btn btn-link w-100 mt-2" @click="restart">Back to sign in</button>
                </form>
              </template>

              <template v-else>
                <h4 class="card-title text-center mb-2">
                  <i class="bi bi-key-fill"></i> Save your recovery codes
                </h4>
                <p class="text-muted small">
                  Each code can be used once if you lose access to your phone. Store them somewhere private; they will not be shown again.
                </p>
                <div class="recovery-codes mb-3">
                  <code v-for="item in recoveryCodes" :key="item">{{ item }}</code>
                </div>
                <button type="button" class="btn btn-outline-secondary w-100 mb-2" @click="copyRecoveryCodes">
                  <i class="bi bi-clipboard me-1"></i> Copy recovery codes
                </button>
                <button type="button" class="btn btn-primary w-100" @click="finishEnrollment">
                  I saved the codes — continue
                </button>
              </template>
            </div>

            <div v-if="stage === 'credentials'" class="card-footer text-center">
              <router-link to="/reset-password" class="text-decoration-none">Forgot your password?</router-link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import { useToast } from "vue-toastification";
import QRCode from "qrcode";
import api from "@/services/axios";

const router = useRouter();
const toast = useToast();

const stage = ref("credentials");
const email = ref("");
const password = ref("");
const code = ref("");
const challengeToken = ref("");
const secret = ref("");
const qrDataUrl = ref("");
const recoveryCodes = ref([]);
const submitting = ref(false);

async function submitCredentials() {
  if (submitting.value) return;
  submitting.value = true;

  try {
    const response = await api.post("/auth/login", {
      email: email.value,
      password: password.value,
    });
    const result = response?.data || response;

    challengeToken.value = result.challenge_token;
    password.value = "";
    code.value = "";

    if (result.mfa_setup_required) {
      secret.value = result.secret;
      qrDataUrl.value = await QRCode.toDataURL(result.provisioning_uri, {
        width: 240,
        margin: 1,
        errorCorrectionLevel: "M",
      });
      stage.value = "setup";
    } else {
      stage.value = "verify";
    }
  } finally {
    submitting.value = false;
  }
}

async function submitMfa() {
  if (submitting.value) return;
  submitting.value = true;

  try {
    const response = await api.post("/auth/mfa/verify", {
      challenge_token: challengeToken.value,
      code: code.value,
    });
    const result = response?.data || response;
    recoveryCodes.value = result.recovery_codes || [];
    secret.value = "";
    qrDataUrl.value = "";

    if (recoveryCodes.value.length > 0) {
      stage.value = "recovery";
    } else {
      await router.replace("/admin");
    }
  } finally {
    submitting.value = false;
  }
}

function restart() {
  localStorage.clear();
  stage.value = "credentials";
  password.value = "";
  code.value = "";
  challengeToken.value = "";
  secret.value = "";
  qrDataUrl.value = "";
}

async function copySecret() {
  await navigator.clipboard.writeText(secret.value);
  toast.success("Setup key copied");
}

async function copyRecoveryCodes() {
  await navigator.clipboard.writeText(recoveryCodes.value.join("\n"));
  toast.success("Recovery codes copied");
}

async function finishEnrollment() {
  await router.replace("/admin");
}
</script>

<style scoped>
.mfa-qr {
  width: 240px;
  max-width: 100%;
  height: auto;
  border: 1px solid #dee2e6;
  border-radius: 0.5rem;
}

.code-input {
  font-size: 1.35rem;
  letter-spacing: 0.12em;
}

.recovery-codes {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.5rem;
  padding: 1rem;
  border: 1px solid #dee2e6;
  border-radius: 0.5rem;
  background: #f8f9fa;
}

.recovery-codes code {
  color: #212529;
  text-align: center;
  font-size: 0.95rem;
}
</style>
