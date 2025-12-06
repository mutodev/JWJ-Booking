<template>
  <div v-if="show" class="admin-modal modal fade show d-block" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit Addon Type</h5>
          <button type="button" class="btn-close" @click="closeModal"></button>
        </div>

        <div class="modal-body">
          <form @submit.prevent="submitForm">
            <!-- Current Image -->
            <div class="mb-3" v-if="currentImage && !imagePreview">
              <label class="form-label">Current Image</label>
              <div>
                <img
                  :src="currentImage"
                  alt="Current image"
                  class="img-thumbnail"
                  style="max-width: 150px; max-height: 150px; object-fit: cover;"
                />
                <button type="button" class="btn btn-sm btn-danger ms-2" @click="markImageForRemoval">
                  <i class="bi bi-trash"></i>
                </button>
              </div>
            </div>

            <!-- New Image -->
            <div class="mb-3">
              <label for="typeImageEdit" class="form-label">{{ currentImage && !removeCurrentImage ? 'Change Image' : 'Image' }}</label>
              <input
                type="file"
                class="form-control"
                id="typeImageEdit"
                @change="handleImageChange"
                accept="image/*"
              />
              <div v-if="imagePreview" class="mt-2">
                <img
                  :src="imagePreview"
                  alt="Preview"
                  class="img-thumbnail"
                  style="max-width: 150px; max-height: 150px; object-fit: cover;"
                />
                <button type="button" class="btn btn-sm btn-danger ms-2" @click="removeNewImage">
                  <i class="bi bi-trash"></i>
                </button>
              </div>
            </div>

            <!-- Name -->
            <div class="mb-3">
              <label for="typeName" class="form-label required">Name</label>
              <input
                type="text"
                class="form-control"
                id="typeName"
                v-model="name"
                required
                placeholder="Enter type name"
              />
              <small class="text-danger small">{{ nameError }}</small>
            </div>

            <!-- Description -->
            <div class="mb-3">
              <label for="typeDescription" class="form-label">Description</label>
              <textarea
                id="typeDescription"
                class="form-control"
                v-model="description"
                placeholder="Enter type description"
                rows="3"
              ></textarea>
              <small class="text-danger small">{{ descriptionError }}</small>
            </div>

            <!-- Active -->
            <div class="mb-3 form-check">
              <input
                type="checkbox"
                class="form-check-input"
                id="typeActive"
                v-model="is_active"
              />
              <label class="form-check-label" for="typeActive">
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
import { watch, ref } from "vue";
import api from "@/services/axios";
import * as yup from "yup";

const emit = defineEmits(["close", "saved"]);
const props = defineProps({
  show: Boolean,
  data: Object,
});

const loading = ref(false);
const imageFile = ref(null);
const imagePreview = ref(null);
const currentImage = ref(null);
const removeCurrentImage = ref(false);

const schema = yup.object({
  name: yup
    .string()
    .required("Type name is required")
    .min(2, "Minimum 2 characters")
    .max(100, "Maximum 100 characters"),
  description: yup.string().nullable().max(500, "Maximum 500 characters"),
  is_active: yup.boolean(),
});

const { handleSubmit, setValues, resetForm } = useForm({
  validationSchema: schema,
});

const { value: name, errorMessage: nameError } = useField("name");
const { value: description, errorMessage: descriptionError } = useField("description");
const { value: is_active } = useField("is_active");

const handleImageChange = (event) => {
  const file = event.target.files[0];
  if (file) {
    imageFile.value = file;
    removeCurrentImage.value = false;
    const reader = new FileReader();
    reader.onload = (e) => {
      imagePreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
  }
};

const removeNewImage = () => {
  imageFile.value = null;
  imagePreview.value = null;
  const input = document.getElementById('typeImageEdit');
  if (input) input.value = '';
};

const markImageForRemoval = () => {
  removeCurrentImage.value = true;
  currentImage.value = null;
};

watch(
  () => props.data,
  (newData) => {
    if (newData) {
      setValues({
        name: newData.name,
        description: newData.description,
        is_active: newData.is_active,
      });
      currentImage.value = newData.image;
      imagePreview.value = null;
      imageFile.value = null;
      removeCurrentImage.value = false;
    } else {
      resetForm();
      currentImage.value = null;
      imagePreview.value = null;
      imageFile.value = null;
      removeCurrentImage.value = false;
    }
  },
  { immediate: true }
);

const closeModal = () => {
  emit("close");
};

const submitForm = handleSubmit(async (values) => {
  try {
    loading.value = true;

    const formData = new FormData();
    formData.append('name', values.name);
    if (values.description) {
      formData.append('description', values.description);
    }
    formData.append('is_active', values.is_active ? '1' : '0');

    if (imageFile.value) {
      formData.append('image', imageFile.value);
    } else if (removeCurrentImage.value) {
      formData.append('remove_image', '1');
    }

    await api.post(`/type-addons/${props.data.id}?_method=PUT`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });

    emit("saved");
    closeModal();
  } catch (error) {
    console.error('Error updating type addon:', error);
  } finally {
    loading.value = false;
  }
});
</script>
