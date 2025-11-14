<template>
  <div class="container py-4">
    <!-- Modal para Jukebox Live 3+ horas -->
    <el-dialog
      v-model="showJukeboxCustomQuoteDialog"
      title="Custom Quote Required"
      width="650px"
      :close-on-click-modal="false"
      center
      class="custom-quote-modal"
    >
      <div class="quote-content">
        <div class="quote-header">
          <i class="bi bi-music-note-list fs-1 text-primary mb-3"></i>
          <p class="quote-message">
            If you're interested in having live music for more than 3 hours, we'll provide a custom quote. Please schedule a call, and a team member will be in touch.
          </p>
        </div>
      </div>
      <template #footer>
        <div class="dialog-footer">
          <el-button size="large" class="custom-btn accept-btn" @click="showJukeboxCustomQuoteDialog = false">
            Got it
          </el-button>
        </div>
      </template>
    </el-dialog>

    <!-- Título y botón SKIP -->
    <div class="d-flex align-items-center justify-content-between mb-4">
      <div class="d-flex align-items-center">
        <i class="bi bi-plus-circle fs-3 me-2 text-success"></i>
        <h2 class="mb-0">Available add-ons and referral services</h2>
      </div>
      <el-button
        size="large"
        class="skip-btn"
        @click="skipAddons"
      >
        SKIP
      </el-button>
    </div>

    <!-- Mensaje informativo -->
    <div class="alert alert-info mb-4">
      <i class="bi bi-info-circle me-2"></i>
      <strong>Optional:</strong> Add-ons are completely optional. You can skip this step or select services to enhance your event.
    </div>

    <!-- SECCIÓN 1: ADDITIONAL TIME -->
    <div v-if="additionalTimeAddons.length" class="addons-section mb-5">
      <div class="section-header">
        <i class="bi bi-clock-history me-2"></i>
        <h3 class="section-title">ADDITIONAL TIME</h3>
      </div>
      <div class="addons-grid">
        <div
          v-for="addon in additionalTimeAddons"
          :key="addon.id"
          class="addon-card"
          :class="{ 'addon-card--selected': isAddonSelected(addon.id) }"
        >
        <!-- Checkbox (oculto visualmente) -->
        <input
          type="checkbox"
          class="addon-checkbox"
          :id="'addon-' + addon.id"
          :value="addon"
          v-model="selectedAddons"
        />

        <!-- Card clickeable -->
        <label class="addon-card__container" :for="'addon-' + addon.id">
          <!-- Imagen -->
          <div class="addon-card__image-wrapper">
            <img
              :src="addon.image || defaultImage"
              class="addon-card__image"
              :alt="addon.name"
              @error="handleImageError"
            />
          </div>

          <!-- Contenido -->
          <div class="addon-card__content">
            <!-- Título y precio -->
            <div class="addon-card__header">
              <h4 class="addon-card__title">{{ addon.name }}</h4>
              <div class="addon-card__price">
                <template v-if="addon.is_referral_service">
                  <span class="referral-badge">Referral Service</span>
                </template>
                <template v-else-if="addon.base_price">
                  <span class="fw-bold">${{ addon.base_price }}</span>
                </template>
              </div>
            </div>

            <!-- Descripción -->
            <p class="addon-card__description">{{ addon.description }}</p>

            <!-- Sub-opciones para Jukebox Live -->
            <div v-if="addon.price_type === 'jukebox' && isAddonSelected(addon.id)" class="addon-card__suboptions">
              <p class="addon-card__suboptions-label">Select duration:</p>
              <div class="addon-card__suboptions-grid">
                <div
                  v-for="option in jukeboxOptions"
                  :key="option.value"
                  class="addon-suboption"
                  :class="{ 'addon-suboption--selected': getAddonSuboption(addon.id) === option.value }"
                  @click.prevent="setAddonSuboption(addon.id, option.value, option.price)"
                >
                  <span class="addon-suboption__label">{{ option.label }}</span>
                  <span v-if="!option.isCustomQuote" class="addon-suboption__price">${{ option.price }}</span>
                  <span v-else class="addon-suboption__custom-quote">
                    <i class="bi bi-telephone-fill me-1"></i>
                  </span>
                </div>
              </div>
            </div>

            <!-- Botón de checkbox o referral -->
            <button
              v-if="addon.is_referral_service"
              type="button"
              class="addon-card__button addon-card__button--referral"
              @click.prevent="handleReferralService(addon)"
            >
              <i class="bi bi-telephone-fill me-2"></i>
              <span>Request Information</span>
            </button>
            <button
              v-else
              type="button"
              class="addon-card__button"
              :class="{ 'addon-card__button--selected': isAddonSelected(addon.id) }"
              @click.prevent="toggleAddon(addon)"
            >
              <i v-if="isAddonSelected(addon.id)" class="bi bi-check-square-fill me-2"></i>
              <i v-else class="bi bi-square me-2"></i>
              <span>{{ isAddonSelected(addon.id) ? 'Added to booking' : 'Add to booking' }}</span>
            </button>
          </div>
        </label>
        </div>
      </div>
    </div>

    <!-- SECCIÓN 2: ADD-ONS & REFERRAL SERVICES -->
    <div v-if="referralServicesAddons.length" class="addons-section">
      <div class="section-header">
        <i class="bi bi-star me-2"></i>
        <h3 class="section-title">ADD-ONS & REFERRAL SERVICES</h3>
      </div>
      <div class="addons-grid">
        <div
          v-for="addon in referralServicesAddons"
          :key="addon.id"
          class="addon-card"
          :class="{ 'addon-card--selected': isAddonSelected(addon.id) }"
        >
        <!-- Checkbox (oculto visualmente) -->
        <input
          type="checkbox"
          class="addon-checkbox"
          :id="'addon-' + addon.id"
          :value="addon"
          v-model="selectedAddons"
        />

        <!-- Card clickeable -->
        <label class="addon-card__container" :for="'addon-' + addon.id">
          <!-- Imagen -->
          <div class="addon-card__image-wrapper">
            <img
              :src="addon.image || defaultImage"
              class="addon-card__image"
              :alt="addon.name"
              @error="handleImageError"
            />
          </div>

          <!-- Contenido -->
          <div class="addon-card__content">
            <!-- Título y precio -->
            <div class="addon-card__header">
              <h4 class="addon-card__title">{{ addon.name }}</h4>
              <div class="addon-card__price">
                <template v-if="addon.is_referral_service">
                  <span class="referral-badge">Referral Service</span>
                </template>
                <template v-else-if="addon.base_price">
                  <span class="fw-bold">${{ addon.base_price }}</span>
                </template>
              </div>
            </div>

            <!-- Descripción -->
            <p class="addon-card__description">{{ addon.description }}</p>

            <!-- Botón de checkbox o referral -->
            <button
              v-if="addon.is_referral_service"
              type="button"
              class="addon-card__button addon-card__button--referral"
              @click.prevent="handleReferralService(addon)"
            >
              <i class="bi bi-telephone-fill me-2"></i>
              <span>Request Information</span>
            </button>
            <button
              v-else
              type="button"
              class="addon-card__button"
              :class="{ 'addon-card__button--selected': isAddonSelected(addon.id) }"
              @click.prevent="toggleAddon(addon)"
            >
              <i v-if="isAddonSelected(addon.id)" class="bi bi-check-square-fill me-2"></i>
              <i v-else class="bi bi-square me-2"></i>
              <span>{{ isAddonSelected(addon.id) ? 'Added to booking' : 'Add to booking' }}</span>
            </button>
          </div>
        </label>
        </div>
      </div>
    </div>

    <!-- Mensaje si no hay addons en ninguna sección -->
    <div v-if="!additionalTimeAddons.length && !referralServicesAddons.length" class="text-muted text-center">
      <i class="bi bi-info-circle fs-4 mb-2"></i>
      <p>No add-on services available</p>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from "vue";
import api from "@/services/axios";
import { useToast } from "vue-toastification";

const toast = useToast();

const props = defineProps({
  active: {
    type: Boolean,
    default: false,
  },
  addons: {
    type: Array,
    default: () => [],
  },
  service: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(["setData", "next"]);

const addons = ref([]);
const selectedAddons = ref([]);
const addonSuboptions = ref({}); // Para guardar sub-opciones de Jukebox
const defaultImage = "/img/default.jpg";
const showJukeboxCustomQuoteDialog = ref(false);

// Opciones de Jukebox Live
const jukeboxOptions = [
  { value: '1h-1p', label: '1 hour, 1 performer', price: 375 },
  { value: '1h-2p', label: '1 hour, 2 performers', price: 500 },
  { value: '2h-1p', label: '2 hours, 1 performer', price: 650 },
  { value: '2h-2p', label: '2 hours, 2 performers', price: 850 },
  { value: '3h+', label: '3+ hours - Custom Quote', price: 0, isCustomQuote: true },
];

// Computed properties para dividir addons en secciones
const additionalTimeAddons = computed(() => {
  return addons.value.filter(addon => {
    // Incluir addons de tipo jukebox o que contengan "15 minutes" o "minute" en el nombre
    return addon.price_type === 'jukebox' ||
           addon.name?.toLowerCase().includes('minute') ||
           addon.name?.toLowerCase().includes('15 min');
  });
});

const referralServicesAddons = computed(() => {
  return addons.value.filter(addon => {
    // Incluir todos los demás addons (referral services y otros)
    // Excluir los que ya están en additionalTimeAddons
    return addon.price_type !== 'jukebox' &&
           !addon.name?.toLowerCase().includes('minute') &&
           !addon.name?.toLowerCase().includes('15 min');
  });
});

async function loadAddons() {
  if (!props.active) return;

  try {
    const response = await api.get("/home/addons");
    const addonsList = Array.isArray(response) ? response : (response?.data || []);
    addons.value = addonsList;

    // Restore selected addons if they exist
    if (props.addons && props.addons.length > 0) {
      selectedAddons.value = props.addons.filter(savedAddon =>
        addons.value.some(addon => addon.id === savedAddon.id)
      );
    }
  } catch (error) {
    console.error("Error loading addons:", error);
    addons.value = [];
  }
}

function handleImageError(event) {
  event.target.src = defaultImage;
}

function isAddonSelected(addonId) {
  return selectedAddons.value.some(addon => addon.id === addonId);
}

function toggleAddon(addon) {
  const index = selectedAddons.value.findIndex(a => a.id === addon.id);
  if (index > -1) {
    selectedAddons.value.splice(index, 1);
    // Limpiar sub-opción si existe
    delete addonSuboptions.value[addon.id];
  } else {
    selectedAddons.value.push({ ...addon });
  }
}

function getAddonSuboption(addonId) {
  return addonSuboptions.value[addonId];
}

function setAddonSuboption(addonId, value, price) {
  // Check if this is the custom quote option (3+ hours)
  const selectedOption = jukeboxOptions.find(opt => opt.value === value);
  if (selectedOption?.isCustomQuote) {
    showJukeboxCustomQuoteDialog.value = true;
    return;
  }

  addonSuboptions.value[addonId] = value;

  // Actualizar el precio del addon en selectedAddons
  const addon = selectedAddons.value.find(a => a.id === addonId);
  if (addon) {
    addon.selectedOption = value;
    addon.selectedPrice = price;
  }

  emitAddonsData();
}

function emitAddonsData() {
  const addonsData = {
    addons: selectedAddons.value.map(addon => ({
      ...addon,
      suboption: addonSuboptions.value[addon.id] || null
    })),
    isValid: true // Los addons no son requeridos
  };

  emit("setData", addonsData);
}

function skipAddons() {
  // Clear all selected addons
  selectedAddons.value = [];
  addonSuboptions.value = {};

  // Emit empty data and proceed to next step
  emitAddonsData();

  toast.info(
    'Add-ons skipped. Proceeding to summary...',
    { timeout: 2000 }
  );

  // Emit next event to move to next step
  emit("next");
}

function handleReferralService(addon) {
  toast.info(
    `Please contact us for information about ${addon.name}. We'll be happy to help you arrange this service!`,
    { timeout: 4000 }
  );
}

watch(
  () => props.active,
  (active) => {
    if (active) {
      loadAddons();
    }
  },
  { immediate: true }
);

watch(selectedAddons, () => {
  emitAddonsData();
}, { deep: true });
</script>

<style scoped>
/* Sección de addons */
.addons-section {
  margin-bottom: 3rem;
}

.section-header {
  display: flex;
  align-items: center;
  margin-bottom: 1.5rem;
  padding-bottom: 0.75rem;
  border-bottom: 2px solid #e5e7eb;
}

.section-header i {
  font-size: 1.75rem;
  color: #FF74B7;
}

.section-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* Grid de add-ons */
.addons-grid {
  display: grid;
  gap: 20px;
  grid-template-columns: 1fr;
}

@media (min-width: 769px) {
  .addons-grid {
    gap: 24px;
  }
}

/* Ocultar checkbox nativo */
.addon-checkbox {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

/* Card de addon */
.addon-card {
  position: relative;
}

.addon-card__container {
  display: flex;
  flex-direction: column;
  background: white;
  border-radius: 10px;
  border: 2px solid #e5e7eb;
  overflow: hidden;
  transition: all 0.3s ease;
  cursor: pointer;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
}

/* Desktop: Layout horizontal */
@media (min-width: 769px) {
  .addon-card__container {
    flex-direction: row;
  }
}

/* Hover estado */
.addon-card__container:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 12px rgba(0, 0, 0, 0.12);
}

/* Estado seleccionado */
.addon-card--selected .addon-card__container {
  border-color: #FF74B7;
  background: #fff5f9;
  box-shadow: 0 4px 12px rgba(255, 116, 183, 0.25);
}

/* Imagen wrapper */
.addon-card__image-wrapper {
  width: 100%;
  height: 150px;
  flex-shrink: 0;
  background: #f3f4f6;
  overflow: hidden;
}

@media (min-width: 769px) {
  .addon-card__image-wrapper {
    width: 200px;
    height: auto;
  }
}

.addon-card__image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s ease;
}

.addon-card__container:hover .addon-card__image {
  transform: scale(1.05);
}

/* Contenido */
.addon-card__content {
  padding: 20px;
  display: flex;
  flex-direction: column;
  flex-grow: 1;
}

/* Header (título + precio) */
.addon-card__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 12px;
  gap: 12px;
}

.addon-card__title {
  font-size: 1.25rem;
  font-weight: 700;
  margin: 0;
  color: #1f2937;
}

.addon-card__price {
  font-size: 1.1rem;
  color: #FF74B7;
  font-weight: 600;
  white-space: nowrap;
}

/* Descripción */
.addon-card__description {
  font-size: 0.95rem;
  line-height: 1.5;
  color: #6b7280;
  margin: 0 0 16px 0;
  flex-grow: 1;
}

/* Sub-opciones de Jukebox */
.addon-card__suboptions {
  margin-bottom: 16px;
  padding: 12px;
  background: #f9fafb;
  border-radius: 8px;
}

.addon-card__suboptions-label {
  font-size: 0.9rem;
  font-weight: 600;
  color: #374151;
  margin: 0 0 10px 0;
}

.addon-card__suboptions-grid {
  display: grid;
  gap: 8px;
}

.addon-suboption {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 12px;
  background: white;
  border: 2px solid #e5e7eb;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.addon-suboption:hover {
  border-color: #FF74B7;
  background: #fff5f9;
}

.addon-suboption--selected {
  border-color: #FF74B7;
  background: #fff5f9;
  box-shadow: 0 0 0 3px rgba(255, 116, 183, 0.1);
}

.addon-suboption__label {
  font-size: 0.9rem;
  color: #374151;
  font-weight: 500;
}

.addon-suboption__price {
  font-size: 0.9rem;
  color: #FF74B7;
  font-weight: 600;
}

/* Botón de checkbox */
.addon-card__button {
  width: 100%;
  padding: 12px;
  border: 2px solid #FF74B7;
  background: white;
  color: #FF74B7;
  font-weight: 600;
  font-size: 0.95rem;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-top: auto;
}

.addon-card__button:hover {
  background: #FF74B7;
  color: white;
}

.addon-card__button--selected {
  background: #FF74B7;
  color: white;
}

.addon-card__button--selected:hover {
  background: #e662a5;
  border-color: #e662a5;
}

/* Referral service badge */
.referral-badge {
  display: inline-block;
  padding: 4px 12px;
  background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
  color: #92400e;
  font-size: 0.85rem;
  font-weight: 600;
  border-radius: 6px;
  border: 1px solid #f59e0b;
}

/* Referral service button */
.addon-card__button--referral {
  border-color: #f59e0b;
  background: white;
  color: #f59e0b;
}

.addon-card__button--referral:hover {
  background: #f59e0b;
  color: white;
}

/* SKIP button */
.skip-btn {
  background: #f3f4f6;
  border-color: #d1d5db;
  color: #6b7280;
  font-weight: 600;
  padding: 10px 24px;
  transition: all 0.2s ease;
}

.skip-btn:hover {
  background: #e5e7eb;
  border-color: #9ca3af;
  color: #374151;
  transform: translateY(-1px);
}

.skip-btn:active {
  transform: translateY(0);
}

/* Custom quote modal styles */
.custom-quote-modal {
  max-width: 650px;
}

.custom-quote-modal :deep(.el-dialog) {
  border-radius: 20px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
}

.custom-quote-modal :deep(.el-dialog__header) {
  padding: 24px 24px 0;
  border-bottom: none;
}

.custom-quote-modal :deep(.el-dialog__title) {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1f2937;
}

.custom-quote-modal :deep(.el-dialog__body) {
  padding: 0 24px 24px;
}

.custom-quote-modal :deep(.el-dialog__footer) {
  padding: 0 24px 24px;
  border-top: none;
}

.quote-content {
  text-align: center;
}

.quote-header {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 20px;
}

.quote-header i {
  color: #3b82f6;
}

.quote-message {
  font-size: 1rem;
  line-height: 1.6;
  color: #4b5563;
  margin: 0;
  max-width: 500px;
}

.dialog-footer {
  display: flex;
  justify-content: center;
  width: 100%;
}

.custom-btn {
  min-width: 150px;
  padding: 12px 32px;
  font-weight: 600;
  border-radius: 10px;
  transition: all 0.2s ease;
}

.accept-btn {
  border: 2px solid #FF74B7 !important;
  background: #FF74B7 !important;
  color: black !important;
}

.accept-btn:hover {
  background: #e662a5 !important;
  border-color: #e662a5 !important;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(255, 116, 183, 0.3);
}

.addon-suboption__custom-quote {
  display: flex;
  align-items: center;
  color: #f59e0b;
  font-size: 1rem;
}
</style>
