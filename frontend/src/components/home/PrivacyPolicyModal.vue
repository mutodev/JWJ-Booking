<template>
  <el-dialog
    v-model="dialogVisible"
    title="Privacy Policy & Terms of Service"
    width="90%"
    :close-on-click-modal="false"
    :close-on-press-escape="false"
    :show-close="false"
    center
    class="privacy-modal"
  >
    <div class="privacy-content">
      <div class="privacy-header">
        <i class="bi bi-shield-check fs-1 text-primary mb-3"></i>
        <h3>We Value Your Privacy</h3>
        <p class="text-muted">
          Before you continue, please review and accept our privacy policy and terms of service.
        </p>
      </div>

      <div class="policy-sections">
        <!-- Privacy Policy Section -->
        <div class="policy-section">
          <h5><i class="bi bi-file-lock me-2"></i>Privacy Policy</h5>
          <div class="policy-text">
            <p>
              At JamWithJamie, we are committed to protecting your privacy and ensuring the security
              of your personal information. This Privacy Policy explains how we collect, use, and
              safeguard your data.
            </p>

            <h6>Information We Collect:</h6>
            <ul>
              <li>Personal contact information (name, email, phone number)</li>
              <li>Event details (date, location, service preferences)</li>
              <li>Payment and billing information</li>
              <li>Communication preferences</li>
            </ul>

            <h6>How We Use Your Information:</h6>
            <ul>
              <li>To process and fulfill your service reservations</li>
              <li>To communicate with you about your bookings</li>
              <li>To improve our services and customer experience</li>
              <li>To send promotional materials (with your consent)</li>
            </ul>

            <h6>Data Protection:</h6>
            <p>
              We implement industry-standard security measures to protect your personal information.
              Your data is stored securely and is never shared with third parties without your
              explicit consent, except as required by law.
            </p>
          </div>
        </div>

        <!-- Terms of Service Section -->
        <div class="policy-section">
          <h5><i class="bi bi-file-earmark-text me-2"></i>Terms of Service</h5>
          <div class="policy-text">
            <p>
              By using JamWithJamie's services, you agree to the following terms and conditions:
            </p>

            <h6>Service Agreement:</h6>
            <ul>
              <li>All bookings are subject to availability and confirmation</li>
              <li>Prices are subject to change without prior notice</li>
              <li>Cancellation policies apply as specified in your booking confirmation</li>
              <li>You agree to provide accurate information for all reservations</li>
            </ul>

            <h6>User Responsibilities:</h6>
            <ul>
              <li>Provide a safe and appropriate venue for our services</li>
              <li>Ensure adequate parking and access for our team</li>
              <li>Supervise children during entertainment sessions</li>
              <li>Comply with all applicable laws and regulations</li>
            </ul>

            <h6>Liability:</h6>
            <p>
              JamWithJamie is not responsible for injuries or damages that occur during events,
              except where caused by our direct negligence. Appropriate insurance coverage is
              recommended for all events.
            </p>
          </div>
        </div>

        <!-- Cookies and Tracking Section -->
        <div class="policy-section">
          <h5><i class="bi bi-cookie me-2"></i>Cookies & Tracking</h5>
          <div class="policy-text">
            <p>
              We use cookies and similar technologies to enhance your browsing experience,
              analyze site traffic, and personalize content. By accepting this policy, you
              consent to our use of cookies.
            </p>
          </div>
        </div>
      </div>

      <div class="acceptance-notice">
        <i class="bi bi-info-circle me-2"></i>
        <span>
          By clicking "Accept," you acknowledge that you have read and agree to our
          Privacy Policy and Terms of Service.
        </span>
      </div>
    </div>

    <template #footer>
      <div class="dialog-footer">
        <el-button
          size="large"
          class="custom-btn decline-btn"
          @click="handleDecline"
        >
          <i class="bi bi-x-circle me-2"></i>
          Decline
        </el-button>
        <el-button
          size="large"
          class="custom-btn accept-btn"
          @click="handleAccept"
        >
          <i class="bi bi-check-circle me-2"></i>
          Accept
        </el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { ElMessage } from 'element-plus';

const dialogVisible = ref(false);
const STORAGE_KEY = 'jamwithjamie_privacy_accepted';

const emit = defineEmits(['accepted', 'declined']);

onMounted(() => {
  // Check if user has already accepted the privacy policy
  const hasAccepted = localStorage.getItem(STORAGE_KEY);

  if (!hasAccepted) {
    // Show dialog if not accepted yet
    dialogVisible.value = true;
  } else {
    // Auto emit accepted if already accepted before
    emit('accepted');
  }
});

function handleAccept() {
  // Save acceptance to localStorage
  localStorage.setItem(STORAGE_KEY, 'true');
  localStorage.setItem(`${STORAGE_KEY}_date`, new Date().toISOString());

  dialogVisible.value = false;
  emit('accepted');

  ElMessage.success({
    message: 'Thank you for accepting our Privacy Policy and Terms of Service',
    duration: 3000
  });
}

function handleDecline() {
  dialogVisible.value = false;
  emit('declined');

  ElMessage.warning({
    message: 'You must accept our Privacy Policy to use our services',
    duration: 5000
  });

  // Redirect to home page or show alternative message
  setTimeout(() => {
    window.location.href = '/';
  }, 2000);
}
</script>

<style scoped>
.privacy-modal {
  max-width: 900px;
}

:deep(.el-dialog) {
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

:deep(.el-dialog__header) {
  padding: 2rem 2rem 1rem;
  border-bottom: 2px solid #e5e7eb;
}

:deep(.el-dialog__title) {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1f2937;
}

:deep(.el-dialog__body) {
  padding: 2rem;
  max-height: 60vh;
  overflow-y: auto;
}

:deep(.el-dialog__footer) {
  padding: 1.5rem 2rem;
  border-top: 2px solid #e5e7eb;
}

.privacy-content {
  color: #374151;
}

.privacy-header {
  text-align: center;
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.privacy-header h3 {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1f2937;
  margin-bottom: 0.5rem;
}

.privacy-header p {
  font-size: 1rem;
  margin-bottom: 0;
}

.policy-sections {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.policy-section {
  background: #f9fafb;
  padding: 1.5rem;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
}

.policy-section h5 {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 1rem;
  display: flex;
  align-items: center;
}

.policy-text {
  font-size: 0.9rem;
  line-height: 1.6;
  color: #4b5563;
}

.policy-text h6 {
  font-size: 0.95rem;
  font-weight: 600;
  color: #1f2937;
  margin-top: 1rem;
  margin-bottom: 0.5rem;
}

.policy-text ul {
  margin: 0.5rem 0;
  padding-left: 1.5rem;
}

.policy-text li {
  margin-bottom: 0.25rem;
}

.policy-text p {
  margin-bottom: 0.75rem;
}

.acceptance-notice {
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  border-radius: 8px;
  padding: 1rem;
  margin-top: 1.5rem;
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
  font-size: 0.9rem;
  color: #1e40af;
}

.acceptance-notice i {
  flex-shrink: 0;
  margin-top: 0.125rem;
}

.dialog-footer {
  display: flex;
  justify-content: center;
  gap: 1rem;
}

.custom-btn {
  border-radius: 8px !important;
  font-weight: 600 !important;
  padding: 12px 32px !important;
  height: auto !important;
  transition: all 0.2s ease !important;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
  font-size: 1rem !important;
  display: flex;
  align-items: center;
  justify-content: center;
}

.decline-btn {
  border: 2px solid #d1d5db !important;
  background: white !important;
  color: #6b7280 !important;
}

.decline-btn:hover {
  border-color: #dc2626 !important;
  background: #fee2e2 !important;
  color: #dc2626 !important;
  transform: translateY(-1px) !important;
  box-shadow: 0 4px 6px rgba(220, 38, 38, 0.2) !important;
}

.accept-btn {
  border: 2px solid #FF74B7 !important;
  background: #FF74B7 !important;
  color: black !important;
}

.accept-btn:hover {
  border-color: #FF74B7 !important;
  background: #FF74B7 !important;
  color: black !important;
  transform: translateY(-1px) !important;
  box-shadow: 0 4px 6px rgba(255, 116, 183, 0.3) !important;
}

@media (max-width: 768px) {
  :deep(.el-dialog) {
    width: 95% !important;
    margin: 5vh auto !important;
  }

  :deep(.el-dialog__body) {
    max-height: 50vh;
  }

  .privacy-header h3 {
    font-size: 1.25rem;
  }

  .policy-section {
    padding: 1rem;
  }

  .dialog-footer {
    flex-direction: column;
  }

  .custom-btn {
    width: 100%;
  }
}
</style>
