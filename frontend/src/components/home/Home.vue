<template>
  <div class="full-height-container">
    <!-- Progress Steps - Hidden on mobile -->
    <div class="steps-sidebar" :class="{ 'mobile-hidden': isMobile }" ref="wizardRoot">
      <el-steps
        :active="activeStep - 1"
        direction="vertical"
        :space="80"
        finish-status="success"
        process-status="process"
      >
        <el-step
          v-for="(step, index) in steps"
          :key="index"
          :title="step.title"
          :status="getStepStatus(index)"
        >
          <template #icon>
            <span class="step-number">{{ index + 1 }}</span>
          </template>
        </el-step>
      </el-steps>
    </div>

    <!-- Main Content Area -->
    <div class="content-area">
      <!-- Mobile Progress Bar -->
      <div class="mobile-progress" v-if="isMobile">
        <el-progress
          :percentage="(activeStep / totalSteps) * 100"
          :stroke-width="4"
          color="#3b82f6"
          :show-text="false"
        />
        <div class="step-info">
          <span class="current-step">Step {{ activeStep }}</span>
          <span class="total-steps">of {{ totalSteps }}</span>
        </div>
      </div>

      <!-- Step Content -->
      <div class="step-content">
        <transition name="slide-fade" mode="out-in">
          <component
            :is="currentStepComponent"
            :key="activeStep"
            @setData="setData"
            @next="nextStep"
            @previous="previousStep"
            v-bind="getCurrentStepProps()"
          />
        </transition>
      </div>

      <!-- Navigation Buttons -->
      <div class="wizard-actions">
        <el-button
          v-if="activeStep > 1"
          @click="previousStep"
          size="large"
          class="prev-btn custom-btn"
        >
          <el-icon class="btn-icon"><ArrowLeft /></el-icon>
          Previous
        </el-button>

        <el-button
          v-if="activeStep < totalSteps"
          @click="nextStep"
          size="large"
          :disabled="!canProceed"
          :loading="isProcessing"
          class="next-btn custom-btn custom-btn-primary"
        >
          Next
          <el-icon class="btn-icon"><ArrowRight /></el-icon>
        </el-button>

        <el-button
          v-if="activeStep === totalSteps"
          @click="submitForm"
          size="large"
          :disabled="!canSubmit"
          :loading="isSubmitting"
          class="submit-btn custom-btn custom-btn-success"
        >
          Submit
          <el-icon class="btn-icon"><Check /></el-icon>
        </el-button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from "vue";
import { ElMessage } from 'element-plus';
import { Check, ArrowLeft, ArrowRight } from '@element-plus/icons-vue';

// Import all step components
import Step1 from "./form/Step1.vue";
import Step2 from "./form/Step2.vue";
import Step3 from "./form/Step3.vue";
import Step4 from "./form/Step4.vue";
import Step5 from "./form/Step5.vue";
import Step6 from "./form/Step6.vue";
import Step7 from "./form/Step7.vue";
import Step8 from "./form/Step8.vue";
import Step9 from "./form/Step9.vue";
import Step10 from "./form/Step10.vue";

// Component mapping
const stepComponents = {
  1: Step1,
  2: Step2,
  3: Step3,
  4: Step4,
  5: Step5,
  6: Step6,
  7: Step7,
  8: Step8,
  9: Step9,
  10: Step10
};

// Step definitions
const steps = ref([
  { title: "Step 1" },
  { title: "Step 2" },
  { title: "Step 3" },
  { title: "Step 4" },
  { title: "Step 5" },
  { title: "Step 6" },
  { title: "Step 7" },
  { title: "Step 8" },
  { title: "Step 9" },
  { title: "Step 10" }
]);

// Reactive state
const totalSteps = 10;
const activeStep = ref(1);
const form = ref({});
const isProcessing = ref(false);
const isSubmitting = ref(false);
const isMobile = ref(false);
const wizardRoot = ref(null);

// Step validation states
const stepValidations = ref({
  1: false,
  2: false,
  3: false,
  4: false,
  5: false,
  6: false,
  7: false,
  8: false,
  9: false,
  10: false
});

// Computed properties
const currentStepComponent = computed(() => stepComponents[activeStep.value]);

const canProceed = computed(() => {
  return stepValidations.value[activeStep.value] === true;
});

const canSubmit = computed(() => {
  return activeStep.value === totalSteps &&
         Object.values(stepValidations.value).every(valid => valid);
});

// Responsive detection
function checkMobile() {
  isMobile.value = window.innerWidth <= 768;
}

// Step status for Element Plus Steps
function getStepStatus(index) {
  const stepNum = index + 1;
  if (stepNum < activeStep.value) return 'success';
  if (stepNum === activeStep.value) return 'process';
  return 'wait';
}


// Get props for current step
function getCurrentStepProps() {
  const props = {};

  switch (activeStep.value) {
    case 2:
      props.city = form.value?.customer?.cityId;
      break;
    case 3:
      props.county = form.value?.customer?.countyId;
      props.active = activeStep.value === 3;
      props.service = form.value?.service;
      break;
    case 4:
      props.service = form.value?.service?.id;
      props.active = activeStep.value === 4;
      props.kids = form.value?.kids;
      break;
    case 5:
      props.service = form.value?.service?.id;
      props.active = activeStep.value === 5;
      props.hours = form.value?.hours;
      break;
    case 6:
      props.active = activeStep.value === 6;
      props.addons = form.value?.addons || [];
      props.service = form.value?.service;
      break;
    case 7:
      props.active = activeStep.value === 7;
      props.service = form.value?.service;
      props.addons = form.value?.addons || [];
      props.hours = form.value?.hours;
      break;
    case 8:
      props.active = activeStep.value === 8;
      props.customer = form.value?.customer;
      props.information = form.value?.information;
      break;
  }

  return props;
}

// Navigation methods
function nextStep() {
  if (!canProceed.value) {
    ElMessage.warning('Please complete the current step before proceeding');
    return;
  }

  if (activeStep.value < totalSteps) {
    isProcessing.value = true;

    // Simulate async validation if needed
    setTimeout(() => {
      activeStep.value++;
      isProcessing.value = false;
    }, 300);
  }
}

function previousStep() {
  if (activeStep.value > 1) {
    activeStep.value--;
  }
}

// Form data management
function setData(data) {
  // Merge new data with existing form data
  for (const key in data) {
    if (data[key] === null) {
      delete form.value[key];
    } else {
      form.value[key] = data[key];
    }
  }

  // Update validation for current step
  validateCurrentStep();

  console.log('Form data updated:', form.value);
}

// Validation logic
function validateCurrentStep() {
  let isValid = false;

  switch (activeStep.value) {
    case 1:
      isValid = !!form.value.customer;
      break;
    case 2:
      isValid = !!form.value.zipcode;
      break;
    case 3:
      isValid = !!form.value.service;
      break;
    case 4:
      isValid = !!form.value.kids && form.value.kids.isValid;
      break;
    case 5:
      isValid = !!form.value.hours;
      break;
    case 6:
      isValid = true; // Addons are optional, always valid
      break;
    case 7:
      isValid = !!form.value.subtotal && form.value.subtotal.isConfirmed; // Subtotal calculated and confirmed
      break;
    case 8:
      isValid = !!form.value.information && form.value.information.isValid; // Information form completed
      break;
    default:
      isValid = true; // Assume valid for other steps
  }

  stepValidations.value[activeStep.value] = isValid;
}

// Form submission
async function submitForm() {
  if (!canSubmit.value) {
    ElMessage.error('Please complete all steps before submitting');
    return;
  }

  isSubmitting.value = true;

  try {
    // Add your form submission logic here
    console.log('Submitting form:', form.value);

    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 2000));

    ElMessage.success('Form submitted successfully!');

    // Reset form or redirect as needed
    // router.push('/success');

  } catch (error) {
    ElMessage.error('Failed to submit form. Please try again.');
    console.error('Form submission error:', error);
  } finally {
    isSubmitting.value = false;
  }
}

// Prevent direct tab navigation
function handleClick(e) {
  const stepElement = e.target.closest('.el-step');
  if (stepElement) {
    e.preventDefault();
    e.stopImmediatePropagation();
    e.stopPropagation();
  }
}

// Lifecycle hooks
onMounted(() => {
  checkMobile();
  window.addEventListener('resize', checkMobile);

  if (wizardRoot.value) {
    wizardRoot.value.addEventListener("click", handleClick, true);
  }
});

onBeforeUnmount(() => {
  window.removeEventListener('resize', checkMobile);

  if (wizardRoot.value) {
    wizardRoot.value.removeEventListener("click", handleClick, true);
  }
});

// Watch for step changes to trigger validation
watch(activeStep, () => {
  validateCurrentStep();
});
</script>

<style scoped>
/* Main container */
.full-height-container {
  height: 100vh;
  display: flex;
  background: white;
  box-sizing: border-box;
}

/* Steps sidebar */
.steps-sidebar {
  width: 280px;
  background: white;
  padding: 2rem 1.5rem;
  border-right: 2px solid #e2e8f0;
  position: relative;
  height: 100vh;
}

.steps-sidebar.mobile-hidden {
  display: none;
}

/* Element Plus Steps customization */
:deep(.el-steps) {
  .el-step__head {
    .el-step__icon {
      width: 36px !important;
      height: 36px !important;
      border-radius: 50% !important;
      background: #f3f4f6;
      border: 2px solid #e5e7eb;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      box-sizing: border-box !important;

      &.is-process {
        background: #3b82f6;
        border-color: #3b82f6;
        color: white;
      }

      &.is-success {
        background: #10b981;
        border-color: #10b981;
        color: white;
      }
    }
  }

  .el-step__line {
    left: 18px !important;
    margin-left: 0 !important;
  }

  .el-step__title {
    font-weight: 500;
    font-size: 0.8rem;
    color: #6b7280;
    margin-left: 12px !important;
    line-height: 36px !important;

    &.is-process {
      color: #3b82f6;
      font-weight: 600;
    }

    &.is-success {
      color: #10b981;
      font-weight: 600;
    }
  }
}

.step-number {
  font-weight: 600;
  font-size: 0.875rem;
  color: #6b7280;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}


/* Content area */
.content-area {
  flex: 1;
  display: flex;
  flex-direction: column;
  padding: 2rem;
}

/* Mobile progress */
.mobile-progress {
  margin-bottom: 2rem;
}

.step-info {
  display: flex;
  justify-content: space-between;
  margin-top: 0.5rem;
  font-size: 0.9rem;
  color: #64748b;
}

.current-step {
  font-weight: 600;
  color: #3b82f6;
}

/* Step content */
.step-content {
  flex: 1;
  overflow-y: auto;
  margin-bottom: 2rem;
}

/* Transitions */
.slide-fade-enter-active,
.slide-fade-leave-active {
  transition: all 0.3s ease;
}

.slide-fade-enter-from {
  transform: translateX(30px);
  opacity: 0;
}

.slide-fade-leave-to {
  transform: translateX(-30px);
  opacity: 0;
}

/* Wizard actions */
.wizard-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #e2e8f0;
}

/* Custom button styles */
.custom-btn {
  border-radius: 8px !important;
  font-weight: 600 !important;
  padding: 12px 24px !important;
  height: auto !important;
  border: 2px solid #d1d5db !important;
  background: #f9fafb !important;
  color: #6b7280 !important;
  transition: all 0.2s ease !important;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
}

.custom-btn:hover {
  border-color: #9ca3af !important;
  background: #f3f4f6 !important;
  color: #4b5563 !important;
  transform: translateY(-1px) !important;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1) !important;
}

.custom-btn:active {
  transform: translateY(0) !important;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
}

.custom-btn-primary {
  border-color: #9ca3af !important;
  background: #6b7280 !important;
  color: white !important;
}

.custom-btn-primary:hover {
  border-color: #6b7280 !important;
  background: #4b5563 !important;
  color: white !important;
}

.custom-btn-success {
  border-color: #9ca3af !important;
  background: #6b7280 !important;
  color: white !important;
}

.custom-btn-success:hover {
  border-color: #6b7280 !important;
  background: #4b5563 !important;
  color: white !important;
}

.custom-btn:disabled {
  opacity: 0.5 !important;
  cursor: not-allowed !important;
  transform: none !important;
}

.custom-btn:disabled:hover {
  transform: none !important;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
}

.btn-icon {
  font-size: 16px !important;
}

/* Mobile responsive */
@media (max-width: 768px) {
  .content-area {
    padding: 1.5rem 1rem;
  }

  .wizard-actions {
    flex-direction: column;
    gap: 0.75rem;
  }

  .wizard-actions .el-button {
    width: 100%;
  }
}

/* Small mobile screens */
@media (max-width: 480px) {
  .content-area {
    padding: 1rem 0.75rem;
  }
}
</style>