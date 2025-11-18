<template>
  <div v-if="show" class="admin-modal modal fade show d-block" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Create New Addon</h5>
          <button type="button" class="btn-close" @click="closeModal"></button>
        </div>

        <div class="modal-body">
          <form @submit.prevent="submitForm">
            <!-- Basic Information Section -->
            <div class="form-section mb-4">
              <h6 class="section-title">
                <i class="bi bi-info-circle me-2"></i>Basic Information
              </h6>

              <!-- Addon Name -->
              <div class="mb-3">
                <label for="name" class="form-label required">Name</label>
                <input
                  type="text"
                  class="form-control"
                  id="name"
                  v-model="name"
                  placeholder="Enter addon name"
                  required
                />
                <small class="text-danger small">{{ nameError }}</small>
              </div>

              <!-- Addon Description -->
              <div class="mb-3">
                <label for="description" class="form-label required">Description</label>
                <textarea
                  id="description"
                  class="form-control"
                  v-model="description"
                  placeholder="Enter addon description"
                  rows="3"
                  required
                ></textarea>
                <small class="text-danger small">{{ descriptionError }}</small>
              </div>
            </div>

            <!-- Pricing and Type Section -->
            <div class="form-section mb-4">
              <h6 class="section-title">
                <i class="bi bi-currency-dollar me-2"></i>Pricing & Type
              </h6>

              <div class="row">
                <!-- Price type -->
                <div class="col-md-6 mb-3">
                  <label for="price_type" class="form-label required">Price Type</label>
                  <select
                    class="form-select"
                    id="price_type"
                    v-model="price_type"
                    required
                  >
                    <option value="">Select price type</option>
                    <option value="standard">Standard</option>
                    <option value="jukebox">Jukebox</option>
                  </select>
                  <small class="text-danger small">{{ priceTypeError }}</small>
                </div>

                <!-- Base price -->
                <div class="col-md-6 mb-3">
                  <label for="base_price" class="form-label" :class="{ 'required': !is_referral_service }">Base Price (USD)</label>
                  <input
                    type="number"
                    step="0.01"
                    class="form-control"
                    id="base_price"
                    v-model="base_price"
                    placeholder="0.00"
                    :required="!is_referral_service"
                    :disabled="is_referral_service"
                  />
                  <small class="text-danger small">{{ basePriceError }}</small>
                </div>
              </div>

              <!-- Is Referral Service -->
              <div class="row">
                <div class="col-md-12 mb-3">
                  <div class="form-check form-switch">
                    <input
                      class="form-check-input"
                      type="checkbox"
                      id="is_referral_service"
                      v-model="is_referral_service"
                    />
                    <label class="form-check-label" for="is_referral_service">
                      <strong>{{ is_referral_service ? 'Referral Service' : 'Regular Addon' }}</strong>
                    </label>
                  </div>
                  <small class="form-text text-muted">
                    <i class="bi bi-info-circle me-1"></i>
                    Referral services are informational only and don't have a direct price. They show "Referral Pending" in the booking form.
                  </small>
                </div>
              </div>
            </div>

            <!-- Duration and Media Section -->
            <div class="form-section mb-4">
              <h6 class="section-title">
                <i class="bi bi-clock me-2"></i>Duration & Media
              </h6>

              <div class="row">
                <!-- Duración estimada -->
                <div class="col-md-6 mb-3">
                  <label for="estimated_duration_minutes" class="form-label required">
                    Estimated Duration (minutes)
                  </label>
                  <input
                    type="number"
                    class="form-control"
                    id="estimated_duration_minutes"
                    v-model="estimated_duration_minutes"
                    placeholder="Enter duration in minutes"
                    min="1"
                    required
                  />
                  <small class="text-danger small">{{ durationError }}</small>
                </div>

                <!-- Placeholder para mantener diseño de 2 columnas -->
                <div class="col-md-6 mb-3">
                </div>
              </div>

              <!-- Imagen -->
              <div class="mb-3">
                <label for="image" class="form-label">Image</label>
                <input
                  type="file"
                  class="form-control"
                  id="image"
                  @change="onImageSelected"
                  accept="image/jpeg,image/jpg,image/png,image/gif"
                />
                <small class="form-text text-muted">
                  Optional. Max 2MB. Formats: JPG, PNG, GIF
                </small>
                <small class="text-danger small">{{ imageError }}</small>

                <!-- Preview de imagen -->
                <div v-if="imagePreview" class="mt-2">
                  <img
                    :src="imagePreview"
                    alt="Preview"
                    class="img-thumbnail admin-image-preview"
                  />
                </div>
              </div>
            </div>
          </form>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" @click="closeModal">
            <i class="bi bi-arrow-90deg-down"></i>
            Back
          </button>
          <button
            type="button"
            class="btn btn-primary"
            @click="submitForm"
            :disabled="loading"
          >
            <i class="bi bi-save"></i>
            Save
          </button>
        </div>
      </div>
    </div>

    <div class="modal-backdrop fade show"></div>
  </div>
</template>

<script setup>
/**
 * Addon Create Modal Component
 *
 * Modal form for creating new addons with complete validation and image upload.
 * All fields are required and validated using Yup schema.
 *
 * Features:
 * - Complete form validation with Yup
 * - Image upload with preview and validation
 * - Organized form sections for better UX
 * - FormData submission for file handling
 * - Error handling and user feedback
 * - Auto-reset on modal open/close
 *
 * @emits close - When modal should be closed
 * @emits saved - When addon is successfully created
 */

import { useForm, useField } from "vee-validate";
import { watch, ref, onMounted } from "vue";
import api from "@/services/axios";
import * as yup from "yup";

// Component props and emits
const emit = defineEmits(["close", "saved"]);
const props = defineProps({
  show: Boolean,
});

// Loading state
const loading = ref(false);

/**
 * Yup validation schema for addon creation
 * All fields are required for creating new addons
 */
const schema = yup.object({
  name: yup
    .string()
    .required("Addon name is required")
    .max(100, "Maximum 100 characters"),
  description: yup
    .string()
    .required("Description is required")
    .max(255, "Maximum 255 characters"),
  price_type: yup
    .string()
    .required("Price type is required")
    .oneOf(["standard", "jukebox"], "Price type must be standard or jukebox"),
  base_price: yup
    .number()
    .typeError("Base price must be a number")
    .when('is_referral_service', {
      is: false,
      then: (schema) => schema.required("Base price is required").min(0, "Price must be >= 0"),
      otherwise: (schema) => schema.nullable().min(0, "Price must be >= 0"),
    }),
  estimated_duration_minutes: yup
    .number()
    .typeError("Duration must be a number")
    .required("Duration is required")
    .min(1, "Must be at least 1 minute"),
  is_referral_service: yup
    .boolean()
    .default(false),
});

// Form setup with validation
const { handleSubmit, resetForm } = useForm({
  validationSchema: schema,
});

// Form fields with validation
const { value: name, errorMessage: nameError } = useField("name");
const { value: description, errorMessage: descriptionError } = useField("description");
const { value: price_type, errorMessage: priceTypeError } = useField("price_type");
const { value: base_price, errorMessage: basePriceError } = useField("base_price");
const { value: estimated_duration_minutes, errorMessage: durationError } = useField("estimated_duration_minutes");
const { value: is_referral_service } = useField("is_referral_service");

// Image handling state
const selectedImage = ref(null);
const imagePreview = ref(null);
const imageError = ref("");

/**
 * Maneja la selección de imagen y crea preview
 * @param {Event} event - Evento del input file
 */
const onImageSelected = (event) => {
  const file = event.target.files[0];
  imageError.value = "";

  if (!file) {
    selectedImage.value = null;
    imagePreview.value = null;
    return;
  }

  // Validar tipo de archivo
  const allowedTypes = ["image/jpeg", "image/jpg", "image/png", "image/gif"];
  if (!allowedTypes.includes(file.type)) {
    imageError.value = "Invalid file type. Only JPEG, PNG and GIF are allowed.";
    selectedImage.value = null;
    imagePreview.value = null;
    event.target.value = "";
    return;
  }

  // Validar tamaño (2MB máximo)
  if (file.size > 2048 * 1024) {
    imageError.value = "Image too large. Maximum size is 2MB.";
    selectedImage.value = null;
    imagePreview.value = null;
    event.target.value = "";
    return;
  }

  selectedImage.value = file;

  // Create preview
  const reader = new FileReader();
  reader.onload = (e) => {
    imagePreview.value = e.target.result;
  };
  reader.readAsDataURL(file);
};

/**
 * Remueve la imagen seleccionada y el preview
 */
const removeImage = () => {
  selectedImage.value = null;
  imagePreview.value = null;
  imageError.value = "";

  // Limpiar el input file
  const fileInput = document.getElementById("image");
  if (fileInput) {
    fileInput.value = "";
  }
};

// Reinicia valores al abrir la modal
watch(
  () => props.show,
  (newVal) => {
    if (newVal) {
      resetForm({
        values: {
          name: "",
          description: "",
          price_type: "",
          base_price: null,
          estimated_duration_minutes: null,
          is_referral_service: false,
        },
      });

      // Resetear campos adicionales
      selectedImage.value = null;
      imagePreview.value = null;
      imageError.value = "";
    }
  }
);

/**
 * Cierra la modal emitiendo el evento close
 */
const closeModal = () => {
  emit("close");
};

/**
 * Envía el formulario para crear un nuevo addon
 * Maneja tanto datos JSON como FormData para imágenes
 */
const submitForm = handleSubmit(async (values) => {
  try {
    loading.value = true;

    // Preparar datos para envío
    const formData = new FormData();

    // Agregar campos del formulario
    Object.keys(values).forEach(key => {
      if (values[key] !== null && values[key] !== undefined && values[key] !== "") {
        formData.append(key, values[key]);
      }
    });

    // Agregar campos adicionales (siempre activo al crear)
    formData.append("is_active", true);

    // Agregar imagen si existe
    if (selectedImage.value) {
      formData.append("image", selectedImage.value);
    }

    // Enviar usando FormData para soporte de imágenes
    await api.post(`/addons`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });

    emit("saved", true);
    closeModal();
  } catch (error) {
    console.error('Error creating addon:', error);

    // Manejar errores de validación del backend
    if (error.response && error.response.data && error.response.data.message) {
      // Mostrar error específico del backend si está disponible
      console.error('Backend error:', error.response.data.message);
    }
  } finally {
    loading.value = false;
  }
});

/**
 * Inicialización del componente
 * Resetea el formulario si la modal está visible al montar
 */
onMounted(() => {
  if (props.show) {
    resetForm({
      values: {
        name: "",
        description: "",
        price_type: "",
        base_price: null,
        estimated_duration_minutes: null,
        is_referral_service: false,
      },
    });

    // Resetear campos adicionales
    selectedImage.value = null;
    imagePreview.value = null;
    imageError.value = "";
  }
});
</script>

