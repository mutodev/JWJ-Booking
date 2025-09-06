<template>
  <div v-if="show" class="modal fade show d-block" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit User</h5>
          <button type="button" class="btn-close" @click="closeModal"></button>
        </div>

        <div class="modal-body">
          <form @submit.prevent="submitForm">
            <div class="mb-3">
              <label for="first_name" class="form-label">First Name</label>
              <input
                type="text"
                class="form-control"
                id="first_name"
                v-model="first_name"
                required
                placeholder="Enter first name"
              />
              <small class="text-danger">{{ first_name_error }}</small>
            </div>

            <div class="mb-3">
              <label for="last_name" class="form-label">Last Name</label>
              <input
                type="text"
                class="form-control"
                id="last_name"
                v-model="last_name"
                required
                placeholder="Enter last name"
              />
              <small class="text-danger">{{ last_name_error }}</small>
            </div>

            <!-- <div class="mb-3">
              <label for="email" class="form-label">Email</label>
              <input
                type="email"
                class="form-control"
                id="email"
                v-model="email"
                required
                placeholder="Enter email"
              />
              <small class="text-danger">{{ email_error }}</small>
            </div> -->
            <div class="mb-3">
              <label for="role" class="form-label">Role</label>
              <select class="form-select" id="role" v-model="role_id" required>
                <option value="" disabled>Select a role</option>
                <option
                  v-for="role in dataRoles"
                  :key="role.id"
                  :value="role.id"
                >
                  {{ role.name }}
                </option>
              </select>
              <small class="text-danger">{{ role_id_error }}</small>
            </div>

            <div class="mb-3 form-check">
              <input
                type="checkbox"
                class="form-check-input"
                id="userActive"
                v-model="is_active"
              />
              <label class="form-check-label" for="userActive"> Active </label>
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
  roles: {
    type: Array,
    default: () => [],
  },
});

const schema = yup.object({
  first_name: yup
    .string()
    .required("First name is required")
    .min(2, "Minimum 2 characters")
    .max(30, "Maximum 30 characters"),
  last_name: yup
    .string()
    .required("Last name is required")
    .min(2, "Minimum 2 characters")
    .max(30, "Maximum 30 characters"),
  // email: yup.string().email("Invalid email").required("Email is required"),
  is_active: yup.boolean(),
  role_id: yup.string().required("Role is required"),
});

const { handleSubmit, setValues, resetForm } = useForm({
  validationSchema: schema,
});

const { value: first_name, errorMessage: first_name_error } =
  useField("first_name");
const { value: last_name, errorMessage: last_name_error } =
  useField("last_name");
// const { value: email, errorMessage: email_error } = useField("email");
const { value: role_id, errorMessage: role_id_error } = useField("role_id");
const { value: is_active } = useField("is_active");

watch(
  () => props.data,
  (newData) => {
    if (newData) {
      setValues({
        first_name: newData.first_name,
        last_name: newData.last_name,
        email: newData.email,
        is_active: newData.is_active,
        role_id: newData.role_id,
      });
    } else {
      resetForm();
    }
  },
  { immediate: true }
);

const dataRoles = ref([]);
watch(
  () => props.roles,
  (newRoles) => {
    dataRoles.value = [...newRoles];
  },
  { deep: true, immediate: true }
);

const closeModal = () => {
  emit("close");
};

const submitForm = handleSubmit(async (values) => {
  await api.put(`/users/${props.data.id}`, values);
  emit("saved");
  closeModal();
});
</script>
<style scoped>
.modal {
  background-color: rgba(0, 0, 0, 0.5);
}
.modal-backdrop {
  z-index: 1040;
}
.modal-dialog {
  z-index: 1050;
}
</style>
