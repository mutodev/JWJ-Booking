<template>
  <div class="full-height-container">
    <!-- Privacy Policy Modal -->
    <PrivacyPolicyModal
      @accepted="handlePrivacyAccepted"
      @declined="handlePrivacyDeclined"
    />

    <!-- Logo flotante -->
    <div class="floating-logo">
      <img :src="logoUrl" alt="JamWithJamie Logo" class="logo-image" @error="handleLogoError" />
    </div>

    <!-- Progress Steps - HIDDEN per REQ-UI-001 -->
    <div class="steps-sidebar" style="display: none;" ref="wizardRoot">
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
            @reservationSuccess="handleReservationSuccess"
            @newReservation="handleNewReservation"
            v-bind="getCurrentStepProps()"
          />
        </transition>
      </div>

      <!-- Navigation Buttons -->
      <div class="wizard-actions" v-if="activeStep < 5">
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
          v-if="activeStep < 4"
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
          v-if="activeStep === 4"
          @click="submitReservation"
          size="large"
          :disabled="!canProceed || isSubmitting"
          :loading="isSubmitting"
          class="next-btn custom-btn custom-btn-primary"
        >
          {{ isSubmitting ? 'Processing...' : 'Submit Reservation' }}
          <el-icon v-if="!isSubmitting" class="btn-icon"><Check /></el-icon>
        </el-button>
      </div>
    </div>
  </div>
</template>

/**
 * Home.vue - Wizard de Reservación Principal
 *
 * Componente principal que maneja un formulario multi-paso para crear reservaciones
 * de servicios de entretenimiento infantil. Incluye validación en cada paso,
 * navegación entre pasos, y envío final al servidor.
 *
 * FUNCIONALIDADES PRINCIPALES:
 * - Navegación entre 7 pasos del formulario
 * - Validación de datos en tiempo real
 * - Almacenamiento temporal de datos del formulario
 * - Envío de reservación al endpoint API
 * - Transición automática al paso de confirmación
 *
 * ESTRUCTURA DEL FORMULARIO:
 * Step 1: Información del cliente (nombre, email, teléfono, ubicación, zipcode)
 * Step 2: Selección de servicio de entretenimiento y duración
 * Step 3: Selección de rango de edades de niños
 * Step 4: Selección de addons adicionales
 * Step 5: Resumen y subtotal de selección
 * Step 6: Información detallada del evento
 * Step 7: Confirmación de reserva creada exitosamente
 *
 * @author JamWithJamie Team
 * @version 2.3.0
 */

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from "vue";
import { ArrowLeft, ArrowRight, Check } from '@element-plus/icons-vue';
import api from "@/services/axios";
import { useToast } from "vue-toastification";

const toast = useToast();

// Importaciones de componentes de steps
import Step1 from "./form/Step1.vue"; // Contact Information + Event Details
import Step2 from "./form/Step2.vue"; // Choose Service
import Step3 from "./form/Step3.vue"; // Select Add-ons
import Step4 from "./form/Step4.vue"; // Subtotal
import Step6 from "./form/Step6.vue"; // Confirmation
import PrivacyPolicyModal from "./PrivacyPolicyModal.vue"; // Privacy Policy

/**
 * Mapeo de componentes por número de paso
 */
const stepComponents = {
  1: Step1, 2: Step2, 3: Step3, 4: Step4, 5: Step6
};

/**
 * Steps configuration
 */
const steps = ref([
  { title: "Step 1" }, { title: "Step 2" }, { title: "Step 3" },
  { title: "Step 4" }, { title: "Step 5" }
]);

/**
 * Wizard reactive state
 */
const totalSteps = 5;
const activeStep = ref(1);
const form = ref({});
const isProcessing = ref(false);
const isSubmitting = ref(false);
const isMobile = ref(false);
const wizardRoot = ref(null);
const reservationData = ref(null);
const sessionId = ref(null);

/**
 * Logo URL
 */
const logoUrl = '/img/logos/JWJ_logo-05.png';
const defaultLogoUrl = '/img/logos/JWJ_logo-05.png';

/**
 * Validation states per step
 */
const stepValidations = ref({
  1: false, 2: false, 3: false, 4: false, 5: true
});

/**
 * Propiedades computadas
 */
const currentStepComponent = computed(() => stepComponents[activeStep.value]);
const canProceed = computed(() => stepValidations.value[activeStep.value] === true);

/**
 * Funciones utilitarias
 */

/**
 * Detecta si la vista está en modo móvil
 */
function checkMobile() {
  isMobile.value = window.innerWidth <= 768;
}

/**
 * Determines the visual state of each step in the sidebar
 * @param {number} index - Índice del step (0-based)
 * @returns {string} State: 'success', 'process', or 'wait'
 */
function getStepStatus(index) {
  const stepNum = index + 1;
  if (stepNum < activeStep.value) return 'success';
  if (stepNum === activeStep.value) return 'process';
  return 'wait';
}


/**
 * Funciones de navegación y manejo de datos
 */

/**
 * Obtiene las props necesarias para el step actual
 * @returns {Object} Props específicas para el componente del step actual
 */
function getCurrentStepProps() {
  const props = {};

  switch (activeStep.value) {
    case 2:
      // Step 2: Choose Service
      console.log('Step 2 - form.value:', form.value);
      console.log('Step 2 - customer:', form.value?.customer);
      console.log('Step 2 - areaId:', form.value?.customer?.areaId);
      props.metropolitanArea = form.value?.customer?.areaId;
      props.zipcode = form.value?.zipcode;
      props.active = activeStep.value === 2;
      props.service = form.value?.service;
      break;
    case 3:
      // Step 3: Select Add-ons
      props.active = activeStep.value === 3;
      props.addons = form.value?.addons || [];
      props.service = form.value?.service;
      break;
    case 4:
      // Step 4: Subtotal
      props.active = activeStep.value === 4;
      props.service = form.value?.service;
      props.addons = form.value?.addons || [];
      props.zipcode = form.value?.zipcode;
      props.customer = form.value?.customer;
      break;
    case 5:
      // Step 5: Confirmation
      props.reservationData = reservationData.value;
      break;
  }

  return props;
}

/**
 * Avanza al siguiente step si es válido
 */
function nextStep() {
  if (!canProceed.value) {
    toast.warning(
      'Please complete the current step before proceeding',
      { timeout: 3000 }
    );
    return;
  }

  if (activeStep.value < totalSteps) {
    isProcessing.value = true;
    setTimeout(() => {
      activeStep.value++;
      isProcessing.value = false;
    }, 300);
  }
}

/**
 * Retrocede al step anterior
 */
function previousStep() {
  if (activeStep.value > 1) {
    activeStep.value--;
  }
}

/**
 * Updates form data and validates current step
 * @param {Object} data - Datos a mergear con el formulario
 */
function setData(data) {
  for (const key in data) {
    if (data[key] === null) {
      delete form.value[key];
    } else {
      form.value[key] = data[key];
    }
  }

  validateCurrentStep();

  // Auto-save draft
  saveDraft();
}

/**
 * Validation functions and event handling
 */

/**
 * Validates current step and updates its state
 */
function validateCurrentStep() {
  let isValid = false;

  switch (activeStep.value) {
    case 1:
      // Step 1: Contact Information + Event Details
      isValid = !!form.value.customer &&
                !!form.value.zipcode &&
                form.value.customer.isValid === true;
      break;
    case 2:
      // Step 2: Choose Service
      isValid = !!form.value.service;
      break;
    case 3:
      // Step 3: Select Add-ons (optional)
      isValid = true;
      break;
    case 4:
      // Step 4: Subtotal (requires confirmation)
      isValid = !!form.value.subtotal && form.value.subtotal.isConfirmed;
      break;
    case 5:
      // Step 5: Confirmation (always valid)
      isValid = true;
      break;
    default:
      isValid = true;
      break;
  }

  stepValidations.value[activeStep.value] = isValid;
}

/**
 * Maneja el éxito en el envío de la reserva
 * Almacena los datos de la reserva creada y navega al paso final
 *
 * @param {Object} data - Datos de respuesta de la reserva creada
 * @param {Object} data.reservation - Objeto con los datos de la reserva
 * @param {Object} data.calculation - Cálculos de precios y duración
 */
function handleReservationSuccess(data) {
  reservationData.value = data;
  activeStep.value = 5;
}

/**
 * Envía la reservación al servidor cuando se está en el Step 5
 *
 * Recopila todos los datos del formulario multi-step, los envía al endpoint
 * de creación de reservas y navega al paso final si es exitoso.
 *
 * @async
 * @throws {Error} Si falla la comunicación con el servidor o la validación
 */
async function submitReservation() {
  if (isSubmitting.value || !canProceed.value) {
    return;
  }

  isSubmitting.value = true;

  try {
    // Construir objeto information desde los datos del customer y zipcode
    // ya que el Step5 (Event Information) fue removido del flujo
    const customer = form.value?.customer;
    const zipcode = form.value?.zipcode;

    // Extraer fecha y hora del eventDateTime
    let eventDate = '';
    let startTime = '';
    if (customer?.eventDateTime) {
      const dateTime = new Date(customer.eventDateTime);
      eventDate = dateTime.toISOString().split('T')[0]; // YYYY-MM-DD
      startTime = dateTime.toTimeString().slice(0, 5); // HH:MM
    }

    // Construir fullAddress desde los datos del zipcode
    const fullAddress = zipcode ?
      `${zipcode.city_name || ''}, ${zipcode.county_name || ''} ${zipcode.zipcode || ''}`.trim() :
      '';

    const information = {
      eventDate: eventDate,
      startTime: startTime,
      fullAddress: fullAddress || 'To be confirmed',
      entertainmentStartTime: startTime,
      birthdayChildName: null, // No se recolecta sin Step5
      childAge: null, // No se recolecta sin Step5
      ageRange: customer?.childrenRange || null,
      songRequests: null, // No se recolecta sin Step5
      happyBirthdayRequest: 'no',
      instructions: null, // No se recolecta sin Step5
      isValid: true
    };

    // Preparar datos para enviar al endpoint
    const reservationData = {
      session_id: sessionId.value, // Include session ID
      customer: form.value?.customer,
      zipcode: form.value?.zipcode,
      service: form.value?.service,
      addons: form.value?.addons || [],
      subtotal: form.value?.subtotal,
      information: information
    };

    // Enviar al endpoint de creación de reservas
    const response = await api.post('/home/reservation', reservationData);

    // Verificar si la respuesta contiene datos válidos
    if (response.data && (response.data.reservation || response.data.data)) {
      // Procesar respuesta del servidor
      const responseData = response.data?.data || response.data;

      const dataToPass = {
        reservation: responseData.reservation || responseData,
        calculation: responseData.calculation || null,
        // Incluir datos del formulario para mostrar en Step6
        service: form.value?.service || null,
        addons: form.value?.addons || [],
        customer: form.value?.customer || null
      };

      // Navegar al paso final con los datos de la reserva
      handleReservationSuccess(dataToPass);

    } else {
      throw new Error('Invalid response format from server');
    }

  } catch (error) {
    // Manejar errores de red o validación del servidor
    let errorMessage = 'Failed to submit reservation. Please try again.';

    if (error.response?.data?.message) {
      errorMessage = error.response.data.message;
    } else if (error.message) {
      errorMessage = error.message;
    }

    console.error('Reservation submission error:', errorMessage);
  } finally {
    isSubmitting.value = false;
  }
}

/**
 * Starts a new reservation by resetting all state
 */
function handleNewReservation() {
  form.value = {};
  reservationData.value = null;
  activeStep.value = 1;

  Object.keys(stepValidations.value).forEach(key => {
    stepValidations.value[key] = key === '5';
  });

  // Generate new session ID for new reservation
  sessionId.value = generateNewSessionId();

  toast.info(
    'Starting new reservation...',
    { timeout: 2000 }
  );
}

/**
 * Privacy Policy handlers
 */

/**
 * Handles when user accepts the privacy policy
 */
function handlePrivacyAccepted() {
  console.log('Privacy policy accepted');
  // User can now use the application normally
}

/**
 * Handles when user declines the privacy policy
 */
function handlePrivacyDeclined() {
  console.log('Privacy policy declined');
  // The modal component will handle redirecting the user
}

/**
 * Handles logo image load error
 */
function handleLogoError(event) {
  event.target.src = defaultLogoUrl;
}

/**
 * Funciones de ciclo de vida y eventos
 */

/**
 * Previene la navegación directa por clics en los steps
 * @param {Event} e - Evento de click
 */
function handleClick(e) {
  const stepElement = e.target.closest('.el-step');
  if (stepElement) {
    e.preventDefault();
    e.stopImmediatePropagation();
    e.stopPropagation();
  }
}

/**
 * Generate new session ID (always new, never reuse)
 */
function generateNewSessionId() {
  // Generate UUID v4
  const newSessionId = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
    const r = Math.random() * 16 | 0;
    const v = c === 'x' ? r : (r & 0x3 | 0x8);
    return v.toString(16);
  });

  return newSessionId;
}

/**
 * Save draft to server (debounced)
 */
let saveDraftTimer = null;
async function saveDraft() {
  clearTimeout(saveDraftTimer);

  saveDraftTimer = setTimeout(async () => {
    try {
      // Don't save if on confirmation step or no data
      if (activeStep.value === 5 || Object.keys(form.value).length === 0) {
        return;
      }

      const draftData = {
        session_id: sessionId.value,
        email: form.value?.customer?.email || null,
        phone: form.value?.customer?.phone || null,
        current_step: activeStep.value,
        form_data: form.value
      };

      await api.post('/home/draft', draftData);
      // Saved silently in background
    } catch (error) {
      // Fail silently - don't interrupt user experience
    }
  }, 2000); // Save 2 seconds after last change
}

/**
 * Component initial configuration
 */
onMounted(() => {
  checkMobile();
  window.addEventListener('resize', checkMobile);

  if (wizardRoot.value) {
    wizardRoot.value.addEventListener("click", handleClick, true);
  }

  // Generate new session ID (always start fresh)
  sessionId.value = generateNewSessionId();
});

/**
 * Limpieza al desmontar el componente
 */
onBeforeUnmount(() => {
  window.removeEventListener('resize', checkMobile);

  if (wizardRoot.value) {
    wizardRoot.value.removeEventListener("click", handleClick, true);
  }
});

/**
 * Watcher to validate when active step changes
 */
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
  display: none !important;
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
        background: #FFEF81 !important;
        border-color: #000000 !important;
        color: black !important;
      }

      &.is-success {
        background: #f3f4f6;
        border-color: #e5e7eb;
        color: #6b7280;
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
      color: #000000;
      font-weight: 600;
    }

    &.is-success {
      color: #6b7280;
      font-weight: 500;
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

/* Force the current step styling with maximum specificity */
:deep(.el-steps--vertical .el-step.is-process .el-step__head .el-step__icon) {
  background: #FFEF81 !important;
  border-color: #000000 !important;
  background-color: #FFEF81 !important;
}

:deep(.el-steps--vertical .el-step.is-process .el-step__head .el-step__icon.is-process) {
  background: #FFEF81 !important;
  border-color: #000000 !important;
  background-color: #FFEF81 !important;
}

:deep(.el-step.is-process .step-number) {
  color: black !important;
}

/* Alternative approach - target by step content */
:deep(.el-step__head.is-process .el-step__icon) {
  background: #FFEF81 !important;
  border: 2px solid #000000 !important;
  background-color: #FFEF81 !important;
}

/* Nuclear option - override everything */
.steps-sidebar :deep(.el-step__icon) {
  --el-color-primary: #FFEF81;
}

.steps-sidebar :deep(.el-steps .el-step.is-process .el-step__head .el-step__icon) {
  background: #FFEF81 !important;
  border-color: #000000 !important;
  background-color: #FFEF81 !important;
}

/* Direct style override */
.steps-sidebar :deep(.el-step__icon.is-process) {
  background: #FFEF81 !important;
  border: 2px solid #000000 !important;
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
  transition: all 0.2s ease !important;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
}

/* Previous button - White background */
.prev-btn {
  border: 2px solid #d1d5db !important;
  background: white !important;
  color: #6b7280 !important;
}

.prev-btn:hover {
  border-color: #9ca3af !important;
  background: #f9fafb !important;
  color: #4b5563 !important;
  transform: translateY(-1px) !important;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1) !important;
}

.prev-btn:active {
  transform: translateY(0) !important;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
}

/* Next button - Pink background with black text */
.custom-btn-primary {
  border: 2px solid #FF74B7 !important;
  background: #FF74B7 !important;
  color: black !important;
}

.custom-btn-primary:hover {
  border-color: #FF74B7 !important;
  background: #FF74B7 !important;
  color: black !important;
  transform: translateY(-1px) !important;
  box-shadow: 0 4px 6px rgba(255, 116, 183, 0.3) !important;
}

.custom-btn-primary:active {
  transform: translateY(0) !important;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
}

.custom-btn-success {
  border: 2px solid #FF74B7 !important;
  background: #FF74B7 !important;
  color: black !important;
}

.custom-btn-success:hover {
  border-color: #FF74B7 !important;
  background: #FF74B7 !important;
  color: black !important;
  transform: translateY(-1px) !important;
  box-shadow: 0 4px 6px rgba(255, 116, 183, 0.3) !important;
}

.custom-btn-success:active {
  transform: translateY(0) !important;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
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

/* Logo flotante */
.floating-logo {
  position: fixed !important;
  top: 20px !important;
  right: 20px !important;
  z-index: 1000 !important;
  background: white;
  border-radius: 50%;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.floating-logo:hover {
  transform: scale(1.05);
}

.logo-image {
  height: 100px;
  object-fit: contain;
}

@media (max-width: 768px) {
  .floating-logo {
    top: 10px !important;
    right: 10px !important;
  }

  .logo-image {
    height: 80px;
    object-fit: contain;
  }
}
</style>