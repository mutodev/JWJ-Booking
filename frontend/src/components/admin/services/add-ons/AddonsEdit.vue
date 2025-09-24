<template>
  <div v-if="show" class="admin-modal modal fade show d-block" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit Addon</h5>
          <button type="button" class="btn-close" @click="closeModal"></button>
        </div>

        <div class="modal-body">
          <form @submit.prevent="submitForm">
            <!-- 📝 BASIC INFO -->
            <div class="form-section mb-4">
              <h6 class="section-title">
                <i class="bi bi-info-circle me-2"></i>Basic Information
              </h6>

              <!-- Name -->
              <div class="mb-3">
                <label for="edit_name" class="form-label required">Name</label>
                <input
                  type="text"
                  class="form-control"
                  id="edit_name"
                  v-model="name"
                  placeholder="Enter addon name"
                  required
                />
                <small class="text-danger small">{{ nameError }}</small>
              </div>

              <!-- Description -->
              <div class="mb-3">
                <label for="edit_description" class="form-label required">Description</label>
                <textarea
                  id="edit_description"
                  class="form-control"
                  v-model="description"
                  placeholder="Enter addon description"
                  rows="3"
                  required
                ></textarea>
                <small class="text-danger small">{{ descriptionError }}</small>
              </div>
            </div>

            <!-- 💰 PRICING & TYPE -->
            <div class="form-section mb-4">
              <h6 class="section-title">
                <i class="bi bi-currency-dollar me-2"></i>Pricing & Type
              </h6>

              <div class="row">
                <!-- Price type -->
                <div class="col-md-6 mb-3">
                  <label for="edit_price_type" class="form-label required">Price Type</label>
                  <select
                    class="form-select"
                    id="edit_price_type"
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
                  <label for="edit_base_price" class="form-label required">Base Price (USD)</label>
                  <input
                    type="number"
                    step="0.01"
                    class="form-control"
                    id="edit_base_price"
                    v-model="base_price"
                    placeholder="0.00"
                    required
                  />
                  <small class="text-danger small">{{ basePriceError }}</small>
                </div>
              </div>
            </div>

            <!-- ⏱️ DURATION & IMAGE -->
            <div class="form-section mb-4">
              <h6 class="section-title">
                <i class="bi bi-clock me-2"></i>Duration & Media
              </h6>

              <div class="row">
                <!-- Duración estimada -->
                <div class="col-md-6 mb-3">
                  <label for="edit_estimated_duration_minutes" class="form-label required">
                    Estimated Duration (minutes)
                  </label>
                  <input
                    type="number"
                    class="form-control"
                    id="edit_estimated_duration_minutes"
                    v-model="estimated_duration_minutes"
                    placeholder="Enter duration in minutes"
                    min="1"
                    required
                  />
                  <small class="text-danger small">{{ durationError }}</small>
                </div>

                <!-- Active state -->
                <div class="col-md-6 mb-3">
                  <label for="edit_is_active" class="form-label">Status</label>
                  <select
                    class="form-select"
                    id="edit_is_active"
                    v-model="is_active"
                  >
                    <option :value="true">Active</option>
                    <option :value="false">Inactive</option>
                  </select>
                  <small class="text-danger small">{{ isActiveError }}</small>
                </div>
              </div>

              <!-- Imagen actual y nueva -->
              <div class="mb-3">
                <label for="edit_image" class="form-label">Image</label>

                <!-- Imagen actual -->
                <div v-if="currentImage" class="mb-2">
                  <p class="text-muted small">Current image:</p>
                  <img
                    :src="currentImage"
                    alt="Current addon image"
                    class="img-thumbnail admin-image-preview"
                  />
                </div>

                <!-- Input para nueva imagen -->
                <input
                  type="file"
                  class="form-control"
                  id="edit_image"
                  @change="onImageSelected"
                  accept="image/jpeg,image/jpg,image/png,image/gif"
                />
                <small class="form-text text-muted">
                  Optional. Max 2MB. Formats: JPG, PNG, GIF. Leave empty to keep current image.
                </small>
                <small class="text-danger small">{{ imageError }}</small>

                <!-- Preview de nueva imagen -->
                <div v-if="imagePreview" class="mt-2">
                  <p class="text-muted small">New image preview:</p>
                  <img
                    :src="imagePreview"
                    alt="New image preview"
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
 * Addon Edit Modal Component
 *
 * Modal form for editing existing addons with complete validation and image upload.
 * All fields are required and validated using Yup schema.
 * Supports updating images while preserving existing ones.
 *
 * Features:
 * - Complete form validation with Yup
 * - Image upload with preview and validation
 * - Display current image with option to replace
 * - Organized form sections for better UX
 * - Smart FormData vs JSON submission
 * - Auto-population from existing data
 * - Error handling and user feedback
 *
 * @props show - Boolean to control modal visibility
 * @props data - Existing addon data to edit
 * @emits close - When modal should be closed
 * @emits saved - When addon is successfully updated
 */

import { useForm, useField } from "vee-validate";
import { watch, ref } from "vue";
import api from "@/services/axios";
import * as yup from "yup";

// Component props and emits
const emit = defineEmits(["close", "saved"]);
const props = defineProps({
  show: Boolean,
  data: Object,
});

// Loading state
const loading = ref(false);

/**
 * Yup validation schema for addon editing
 * All fields are required when editing addons
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
    .required("Base price is required")
    .min(0, "Price must be >= 0"),
  estimated_duration_minutes: yup
    .number()
    .typeError("Duration must be a number")
    .required("Duration is required")
    .min(1, "Must be at least 1 minute"),
});

// Form setup with validation
const { handleSubmit, setValues, resetForm } = useForm({
  validationSchema: schema,
});

// Form fields with validation
const { value: name, errorMessage: nameError } = useField("name");
const { value: description, errorMessage: descriptionError } = useField("description");
const { value: price_type, errorMessage: priceTypeError } = useField("price_type");
const { value: base_price, errorMessage: basePriceError } = useField("base_price");
const { value: estimated_duration_minutes, errorMessage: durationError } = useField("estimated_duration_minutes");

// Additional state management
const is_active = ref(true);
const selectedImage = ref(null);
const imagePreview = ref(null);
const currentImage = ref(null);

// Error handling
const isActiveError = ref("");
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
  const fileInput = document.getElementById("edit_image");
  if (fileInput) {
    fileInput.value = "";
  }
};

/**
 * Observa cambios en los datos del addon para cargar información existente
 */
watch(
  () => props.data,
  (newData) => {
    if (newData) {
      setValues({
        name: newData.name || "",
        description: newData.description || "",
        price_type: newData.price_type || "",
        base_price: newData.base_price || null,
        estimated_duration_minutes: newData.estimated_duration_minutes || null,
      });

      // Cargar campos adicionales
      is_active.value = newData.is_active !== undefined ? newData.is_active : true;
      currentImage.value = newData.image || null;

      // Reset new image states
      selectedImage.value = null;
      imagePreview.value = null;
      imageError.value = "";
      isActiveError.value = "";
    } else {
      resetForm();
      is_active.value = true;
      currentImage.value = null;
      selectedImage.value = null;
      imagePreview.value = null;
      imageError.value = "";
      isActiveError.value = "";
    }
  },
  { immediate: true }
);

/**
 * Cierra la modal emitiendo el evento close
 */
const closeModal = () => {
  emit("close");
};

/**
 * Envía el formulario para actualizar el addon existente
 * Maneja tanto datos JSON como FormData para imágenes
 */
const submitForm = handleSubmit(async (values) => {
  try {
    loading.value = true;

    // Si hay una nueva imagen, usar FormData
    if (selectedImage.value) {
      const formData = new FormData();

      // Agregar campos del formulario
      Object.keys(values).forEach(key => {
        if (values[key] !== null && values[key] !== undefined && values[key] !== "") {
          formData.append(key, values[key]);
        }
      });

      // Agregar campos adicionales
      formData.append("is_active", is_active.value);

      // Agregar nueva imagen
      formData.append("image", selectedImage.value);

      // Enviar usando FormData para soporte de imágenes
      await api.put(`/addons/${props.data.id}`, formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      });
    } else {
      // Sin imagen nueva, enviar como JSON
      const updateData = {
        ...values,
        is_active: is_active.value
      };

      await api.put(`/addons/${props.data.id}`, updateData);
    }

    emit("saved");
    closeModal();
  } catch (error) {
    console.error('Error updating addon:', error);

    // Manejar errores de validación del backend
    if (error.response && error.response.data && error.response.data.message) {
      console.error('Backend error:', error.response.data.message);
    }
  } finally {
    loading.value = false;
  }
});
</script>

