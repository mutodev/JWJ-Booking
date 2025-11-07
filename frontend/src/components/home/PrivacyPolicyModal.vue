<template>
  <!-- Short Privacy Notice Dialog -->
  <el-dialog
    v-model="dialogVisible"
    title="Privacy Notice"
    width="650px"
    :close-on-click-modal="false"
    :close-on-press-escape="false"
    :show-close="false"
    center
    class="privacy-modal"
  >
    <div class="privacy-content">
      <div class="privacy-header">
        <i class="bi bi-shield-check fs-1 text-primary mb-3"></i>
        <h3>We Respect Your Privacy</h3>
        <p class="privacy-notice">
          We respect your privacy. Any information you provide when submitting a booking request - such as name, contact details, and event information - will be used solely to process your reservation, coordinate our performers, and communicate with you about your booking interest. This website uses cookies to enhance your experience and to analyze performance and traffic. By submitting this form or using our website, you consent to the collection and use of your data as described. For more details, please see our <a href="#" @click.prevent="showFullPolicy" class="privacy-link">Privacy Policy</a>.
        </p>
      </div>
    </div>

    <template #footer>
      <div class="dialog-footer">
        <el-button
          size="large"
          class="custom-btn necessary-btn"
          @click="handleAcceptNecessary"
        >
          ACCEPT ONLY NECESSARY
        </el-button>
        <el-button
          size="large"
          class="custom-btn accept-btn"
          @click="handleAcceptAll"
        >
          ACCEPT ALL
        </el-button>
      </div>
    </template>
  </el-dialog>

  <!-- Full Privacy Policy Dialog -->
  <el-dialog
    v-model="fullPolicyVisible"
    title="Privacy Policy for Jam with Jamie LLC"
    width="900px"
    center
    class="full-policy-modal"
  >
    <div class="full-policy-content">
      <p class="text-muted mb-3">
        <strong>Effective Date:</strong> November, 2025<br>
        <strong>Last Updated:</strong> November, 2025
      </p>

      <div class="policy-section">
        <h4>1. Introduction</h4>
        <p>Welcome to Jam with Jamie. We operate the website www.jamwithjamie.com and provide children's music and entertainment booking services. We value your trust and are committed to protecting your privacy and the personal information you share with us. This Privacy Policy outlines how we collect, use, disclose, and protect your information.</p>
      </div>

      <div class="policy-section">
        <h4>2. Scope & Applicability</h4>
        <p>This policy applies to all users of the Site, specifically parents or legal guardians submitting booking requests. We do not collect personal information from children, and all bookings must be submitted by a parent or guardian.</p>
      </div>

      <div class="policy-section">
        <h4>3. Information We Collect</h4>
        <h5>A. Information from You (Parent/Guardian):</h5>
        <p>When submitting a booking request, we may collect information such as:</p>
        <ul>
          <li>Parent/guardian name, email, phone number</li>
          <li>Event details: date, time, location, number of children/guests, age ranges (for planning purposes only)</li>
          <li>Billing/payment information (for processing payments)</li>
          <li>Other optional details you provide (e.g., special requests, accessibility requirements)</li>
        </ul>

        <h5>B. Automatically Collected Information:</h5>
        <p>When you visit our Site, we may collect non-personal or aggregated information, such as:</p>
        <ul>
          <li>Device type, browser type, operating system</li>
          <li>IP address (in some cases)</li>
          <li>Pages visited, time spent on the Site</li>
          <li>Cookies or similar tracking technologies to enhance user experience and analyze site traffic</li>
        </ul>
      </div>

      <div class="policy-section">
        <h4>4. Use of Information</h4>
        <p>We use the information we collect to:</p>
        <ul>
          <li>Process and confirm your booking request</li>
          <li>Coordinate services and assign performers/staff</li>
          <li>Send confirmations, reminders, and updates about your booking</li>
          <li>Process payments and issue invoices</li>
          <li>Improve our services and user experience</li>
          <li>Communicate promotional offers or related services only with your consent</li>
          <li>Comply with legal and regulatory obligations</li>
        </ul>
      </div>

      <div class="policy-section">
        <h4>5. Cookies and Tracking Technologies</h4>
        <p>We use cookies, web beacons, analytics tools, and similar technologies to enhance your experience, analyze performance and traffic, and remember preferences. We may share information about your use of our site with social media, advertising, and analytics partners. You may manage or disable cookies via your browser settings, though this may impact site functionality.</p>
      </div>

      <div class="policy-section">
        <h4>6. Data Security and Retention</h4>
        <p>We implement reasonable safeguards to protect your personal information. Access is restricted to authorized staff and service providers. We retain personal information only as long as necessary to fulfill bookings, process payments, comply with legal obligations, and maintain records.</p>
      </div>

      <div class="policy-section">
        <h4>7. Your Rights</h4>
        <p>You can:</p>
        <ul>
          <li>Review, correct, or update your personal information</li>
          <li>Opt out of promotional communications</li>
        </ul>
      </div>

      <div class="policy-section">
        <h4>8. Contact Us</h4>
        <p>For questions, concerns, or to exercise your rights:</p>
        <p><strong>Jam with Jamie</strong><br>Email: booking@jamwithjamie.com</p>
      </div>

      <div class="policy-section">
        <h4>9. Changes to Our Privacy Policy</h4>
        <p>We may update this Privacy Policy from time to time. Material changes will be posted on our Site. The "Last Updated" date at the top will reflect when changes were made. Please review periodically.</p>
      </div>
    </div>

    <template #footer>
      <el-button size="large" @click="fullPolicyVisible = false">Close</el-button>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useToast } from "vue-toastification";

const toast = useToast();

const dialogVisible = ref(false);
const fullPolicyVisible = ref(false);
const STORAGE_KEY = 'jamwithjamie_privacy_accepted';
const STORAGE_COOKIES_KEY = 'jamwithjamie_cookies_preference';

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

function showFullPolicy() {
  fullPolicyVisible.value = true;
}

function handleAcceptAll() {
  // Save acceptance to localStorage
  localStorage.setItem(STORAGE_KEY, 'true');
  localStorage.setItem(STORAGE_COOKIES_KEY, 'all');
  localStorage.setItem(`${STORAGE_KEY}_date`, new Date().toISOString());

  dialogVisible.value = false;
  emit('accepted');

  toast.success(
    'Thank you for accepting our Privacy Policy',
    { timeout: 3000 }
  );
}

function handleAcceptNecessary() {
  // Save acceptance to localStorage with only necessary cookies
  localStorage.setItem(STORAGE_KEY, 'true');
  localStorage.setItem(STORAGE_COOKIES_KEY, 'necessary');
  localStorage.setItem(`${STORAGE_KEY}_date`, new Date().toISOString());

  dialogVisible.value = false;
  emit('accepted');

  toast.success(
    'You have accepted necessary cookies only',
    { timeout: 3000 }
  );
}
</script>

<style scoped>
.privacy-modal {
  max-width: 650px;
}

.full-policy-modal {
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
  margin-bottom: 1rem;
}

.privacy-header h3 {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1f2937;
  margin-bottom: 1rem;
}

.privacy-notice {
  font-size: 0.95rem;
  line-height: 1.6;
  color: #4b5563;
  text-align: left;
}

.privacy-link {
  color: #FF74B7;
  text-decoration: none;
  font-weight: 600;
}

.privacy-link:hover {
  text-decoration: underline;
}

.dialog-footer {
  display: flex;
  justify-content: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.custom-btn {
  border-radius: 8px !important;
  font-weight: 600 !important;
  padding: 12px 24px !important;
  height: auto !important;
  transition: all 0.2s ease !important;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
  font-size: 0.9rem !important;
  white-space: nowrap;
}

.necessary-btn {
  border: 2px solid #d1d5db !important;
  background: white !important;
  color: #6b7280 !important;
}

.necessary-btn:hover {
  border-color: #9ca3af !important;
  background: #f3f4f6 !important;
  color: #4b5563 !important;
  transform: translateY(-1px) !important;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1) !important;
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

/* Full Policy Styles */
.full-policy-content {
  font-size: 0.95rem;
  line-height: 1.6;
  color: #374151;
}

.policy-section {
  margin-bottom: 1.5rem;
}

.policy-section h4 {
  font-size: 1.1rem;
  font-weight: 700;
  color: #1f2937;
  margin-bottom: 0.75rem;
}

.policy-section h5 {
  font-size: 1rem;
  font-weight: 600;
  color: #1f2937;
  margin-top: 1rem;
  margin-bottom: 0.5rem;
}

.policy-section ul {
  margin: 0.5rem 0;
  padding-left: 1.5rem;
}

.policy-section li {
  margin-bottom: 0.25rem;
}

.policy-section p {
  margin-bottom: 0.75rem;
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

  .dialog-footer {
    flex-direction: column;
  }

  .custom-btn {
    width: 100%;
  }
}
</style>
