<template>
  <div v-if="show" class="admin-modal modal fade show d-block" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit Jam Types</h5>
          <button type="button" class="btn-close" @click="closeModal"></button>
        </div>

        <div class="modal-body">
          <form @submit.prevent="submitForm">
            <!-- Name -->
            <div class="mb-3">
              <label for="serviceName" class="form-label">Name</label>
              <input
                type="text"
                class="form-control"
                id="serviceName"
                v-model="name"
                required
                placeholder="Enter Service Name"
              />
              <small class="text-danger small">{{ nameError }}</small>
            </div>

            <!-- Description -->
            <div class="mb-3">
              <label for="serviceDescription" class="form-label"
                >Description</label
              >
              <textarea
                id="serviceDescription"
                class="form-control"
                v-model="description"
                placeholder="Enter Service Description"
                rows="3"
              ></textarea>
              <small class="text-danger small">{{ descriptionError }}</small>
            </div>

            <!-- Image -->
            <div class="mb-3">
              <label for="serviceImage" class="form-label">Image</label>
              <input
                type="file"
                class="form-control"
                id="serviceImage"
                accept="image/*"
                @change="onImageSelected"
              />
              <small class="text-muted">JPG, PNG, GIF (max 2MB)</small>
              <small v-if="imageError" class="text-danger small d-block">{{ imageError }}</small>

              <!-- Image Preview -->
              <div v-if="imagePreview || currentImage" class="mt-2">
                <div class="position-relative d-inline-block">
                  <img :src="imagePreview || currentImage" alt="Preview" class="img-thumbnail" style="max-height: 150px;" />
                  <button
                    type="button"
                    class="btn btn-sm btn-danger position-absolute top-0 end-0"
                    @click="removeImage"
                  >
                    <i class="bi bi-x"></i>
                  </button>
                </div>
              </div>
            </div>

            <!-- Active -->
            <div class="mb-3 form-check">
              <input
                type="checkbox"
                class="form-check-input"
                id="serviceActive"
                v-model="is_active"
              />
              <label class="form-check-label" for="serviceActive">
                Active
              </label>
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
import { useForm, useField } from "vee-validate";
import { ref, watch } from "vue";
import api from "@/services/axios";
import * as yup from "yup";

const emit = defineEmits(["close", "saved"]);
const props = defineProps({
  show: Boolean,
  data: Object,
});

const schema = yup.object({
  name: yup
    .string()
    .required("Service name is required")
    .min(3, "Minimum 3 characters")
    .max(100, "Maximum 100 characters"),
  description: yup.string().nullable().max(255, "Maximum 255 characters"),
  is_active: yup.boolean(),
});

const { handleSubmit, setValues, resetForm } = useForm({
  validationSchema: schema,
});

const { value: name, errorMessage: nameError } = useField("name");
const { value: description, errorMessage: descriptionError } =
  useField("description");
const { value: is_active } = useField("is_active");

// Image handling
const selectedImageFile = ref(null);
const imagePreview = ref(null);
const currentImage = ref(null);
const imageError = ref("");
const loading = ref(false);

const onImageSelected = (event) => {
  const file = event.target.files[0];
  if (!file) return;

  const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
  if (!allowedTypes.includes(file.type)) {
    imageError.value = 'Please select a valid image file (JPG, PNG, GIF)';
    return;
  }

  const maxSize = 2 * 1024 * 1024;
  if (file.size > maxSize) {
    imageError.value = 'Image size must be less than 2MB';
    return;
  }

  selectedImageFile.value = file;
  imageError.value = '';

  const reader = new FileReader();
  reader.onload = (e) => {
    imagePreview.value = e.target.result;
  };
  reader.readAsDataURL(file);
};

const removeImage = () => {
  selectedImageFile.value = null;
  imagePreview.value = null;
  currentImage.value = null;
  imageError.value = '';
  const fileInput = document.getElementById('serviceImage');
  if (fileInput) fileInput.value = '';
};

// Watch for changes in props.data
watch(
  () => props.data,
  (newData) => {
    if (newData) {
      setValues({
        name: newData.name,
        description: newData.description,
        is_active: newData.is_active,
      });
      if (newData.img) {
        currentImage.value = newData.img.startsWith('/') ? newData.img : '/' + newData.img;
      } else {
        currentImage.value = null;
      }
      selectedImageFile.value = null;
      imagePreview.value = null;
    } else {
      resetForm();
      removeImage();
    }
  },
  { immediate: true }
);

const closeModal = () => {
  emit("close");
};

const submitForm = handleSubmit(async (values) => {
  loading.value = true;
  try {
    const formData = new FormData();
    formData.append('name', values.name);
    formData.append('description', values.description || '');
    formData.append('is_active', values.is_active ? '1' : '0');

    if (selectedImageFile.value) {
      formData.append('image', selectedImageFile.value);
    }

    await api.post(`/services/update/${props.data.id}`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });
    emit("saved");
    closeModal();
  } finally {
    loading.value = false;
  }
});
</script>
