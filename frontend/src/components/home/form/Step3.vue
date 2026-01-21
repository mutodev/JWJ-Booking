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

    <!-- Secciones dinámicas por tipo de addon -->
    <div
      v-for="(typeAddon, index) in typeAddons"
      :key="typeAddon.id"
      class="addons-section mb-5"
    >
      <hr
        v-if="isReferralTypeAddon(typeAddon) && index > 0 && !isReferralTypeAddon(typeAddons[index - 1])"
        class="addons-separator"
      />
      <div class="section-header">
        <i class="bi bi-plus-circle me-2"></i>
        <h3 class="section-title">{{ typeAddon.name }}</h3>
      </div>
      <div class="addons-grid">
        <div class="addon-card">
          <!-- Card -->
          <div class="addon-card__container">
            <!-- Imagen del tipo -->
            <div class="addon-card__image-wrapper">
              <img
                :src="getImageUrl(typeAddon.image)"
                class="addon-card__image"
                :alt="typeAddon.name"
                @error="handleImageError"
              />
            </div>

            <!-- Contenido -->
            <div class="addon-card__content">
              <!-- Descripción del tipo -->
              <p class="addon-card__description">{{ typeAddon.description }}</p>

              <!-- Radio buttons para addons con precio > 0 -->
              <div v-if="typeAddon.addons && typeAddon.addons.length && hasAddonsWithPrice(typeAddon)" class="addon-card__radio-options">
                <div
                  v-for="addon in typeAddon.addons"
                  :key="addon.id"
                  class="addon-radio-option"
                  :class="{ 'addon-radio-option--selected': selectedAddonByType[typeAddon.id] === addon.id }"
                  @click="handleAddonSelect(typeAddon, addon)"
                >
                  <i :class="selectedAddonByType[typeAddon.id] === addon.id ? 'bi bi-circle-fill' : 'bi bi-circle'" class="addon-radio-option__icon"></i>
                  <span class="addon-radio-option__label">{{ addon.name }}</span>
                  <span class="addon-radio-option__price">${{ addon.base_price }}</span>
                </div>
              </div>

              <!-- Checkbox para addons (con o sin precio) -->
              <div v-if="typeAddon.addons && typeAddon.addons.length && !hasAddonsWithPrice(typeAddon)" class="addon-card__radio-options">
                <div
                  v-for="addon in typeAddon.addons"
                  :key="addon.id"
                  class="addon-radio-option"
                  :class="{ 'addon-radio-option--selected': isAddonSelected(addon.id) }"
                  @click="toggleReferralAddon(addon)"
                >
                  <i :class="isAddonSelected(addon.id) ? 'bi bi-check-square-fill' : 'bi bi-square'" class="addon-radio-option__icon"></i>
                  <span class="addon-radio-option__label">{{ addon.name }}</span>
                  <span v-if="addon.base_price > 0" class="addon-radio-option__price">${{ addon.base_price }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Mensaje si no hay addons -->
    <div v-if="!typeAddons.length" class="text-muted text-center">
      <i class="bi bi-info-circle fs-4 mb-2"></i>
      <p>No add-on services available</p>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from "vue";
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

const typeAddons = ref([]);
const selectedAddons = ref([]);
const selectedAddonByType = ref({}); // Para guardar addon seleccionado por tipo
const defaultImage = "/img/default.jpg";

function getImageUrl(img) {
  if (!img) return defaultImage;
  // If already has leading slash, return as is
  if (img.startsWith('/')) return img;
  // Otherwise add leading slash for proper URL resolution
  return '/' + img;
}
const showJukeboxCustomQuoteDialog = ref(false);

async function loadAddons() {
  if (!props.active) return;

  try {
    const response = await api.get("/home/addons");
    const typeAddonsList = Array.isArray(response) ? response : (response?.data || []);

    // Ordenar: primero los que tienen algún addon con precio > 0, luego los referral (todos precio 0)
    typeAddons.value = typeAddonsList.sort((a, b) => {
      const aIsReferral = a.addons?.every(addon => addon.base_price == 0);
      const bIsReferral = b.addons?.every(addon => addon.base_price == 0);
      if (!aIsReferral && bIsReferral) return -1;
      if (aIsReferral && !bIsReferral) return 1;
      return 0;
    });

    // Restore selected addons if they exist
    if (props.addons && props.addons.length > 0) {
      props.addons.forEach(savedAddon => {
        // Encontrar el tipo de addon correspondiente
        const typeAddon = typeAddons.value.find(t =>
          t.addons?.some(a => a.id === savedAddon.id)
        );
        if (typeAddon) {
          selectedAddonByType.value[typeAddon.id] = savedAddon.id;
          selectedAddons.value.push(savedAddon);
        }
      });
    }
  } catch (error) {
    console.error("Error loading addons:", error);
    typeAddons.value = [];
  }
}

function handleImageError(event) {
  event.target.src = defaultImage;
}

function hasAddonsWithPrice(typeAddon) {
  return typeAddon.addons?.some(addon => addon.base_price > 0);
}

function isReferralTypeAddon(typeAddon) {
  // Es referral si TODOS los addons tienen precio = 0
  return typeAddon.addons?.every(addon => addon.base_price == 0);
}

function isAddonSelected(addonId) {
  return selectedAddons.value.some(addon => addon.id === addonId);
}

function toggleReferralAddon(addon) {
  const index = selectedAddons.value.findIndex(a => a.id === addon.id);
  if (index > -1) {
    selectedAddons.value.splice(index, 1);
  } else {
    selectedAddons.value.push({ ...addon });
  }
  emitAddonsData();
}

function handleAddonSelect(typeAddon, addon) {
  const currentSelected = selectedAddonByType.value[typeAddon.id];

  // Si ya está seleccionado el mismo, deseleccionar
  if (currentSelected === addon.id) {
    delete selectedAddonByType.value[typeAddon.id];
    const index = selectedAddons.value.findIndex(a => a.id === addon.id);
    if (index > -1) {
      selectedAddons.value.splice(index, 1);
    }
  } else {
    // Remover addon anterior de este tipo si existe
    if (currentSelected) {
      const prevIndex = selectedAddons.value.findIndex(a => a.id === currentSelected);
      if (prevIndex > -1) {
        selectedAddons.value.splice(prevIndex, 1);
      }
    }

    // Seleccionar nuevo addon
    selectedAddonByType.value[typeAddon.id] = addon.id;
    selectedAddons.value.push({ ...addon });
  }

  emitAddonsData();
}

function emitAddonsData() {
  const addonsData = {
    addons: selectedAddons.value,
    isValid: true // Los addons no son requeridos
  };

  emit("setData", addonsData);
}

function skipAddons() {
  // Clear all selected addons
  selectedAddons.value = [];
  selectedAddonByType.value = {};

  // Emit empty data and proceed to next step
  emitAddonsData();

  toast.info(
    'Add-ons skipped. Proceeding to summary...',
    { timeout: 2000 }
  );

  // Emit next event to move to next step
  emit("next");
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

/* Radio options para Additional Time */
.addon-card__radio-options {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-top: auto;
}

.addon-radio-option {
  display: flex;
  align-items: center;
  padding: 10px 12px;
  background: white;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.addon-radio-option:hover {
  border-color: #FF74B7;
  background: #fff5f9;
}

.addon-radio-option--selected {
  border-color: #FF74B7;
  background: #fff5f9;
}

.addon-radio-option__icon {
  font-size: 1rem;
  color: #d1d5db;
  margin-right: 10px;
}

.addon-radio-option--selected .addon-radio-option__icon {
  color: #FF74B7;
}

.addon-radio-option__label {
  flex: 1;
  font-size: 0.9rem;
  color: #374151;
  font-weight: 500;
}

.addon-radio-option__price {
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

/* Separador entre addons con radio y checkbox */
.addons-separator {
  border: none;
  height: 3px;
  background-color: #FF74B7;
  margin: 2rem 0;
  opacity: 1;
}

</style>
