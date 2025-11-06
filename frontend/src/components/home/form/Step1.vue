<template>
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-12 col-md-6">
        <!-- Título -->
        <h2 class="mb-4 text-center">Contact Information</h2>

        <!-- First name -->
        <div class="mb-3">
          <label for="firstName" class="form-label">
            First name <span class="text-danger">*</span>
          </label>
          <el-tooltip
            content="Enter the first name of the person making the reservation"
            placement="right"
            effect="dark"
            trigger="focus"
          >
            <input
              v-model="form.firstName"
              type="text"
              class="form-control"
              id="firstName"
              @blur="validateField('firstName')"
            />
          </el-tooltip>
          <div v-if="errors.firstName" class="text-danger small">
            {{ errors.firstName }}
          </div>
        </div>

        <!-- Last name -->
        <div class="mb-3">
          <label for="lastName" class="form-label">
            Last name <span class="text-danger">*</span>
          </label>
          <el-tooltip
            content="Enter the last name of the person making the reservation"
            placement="right"
            effect="dark"
            trigger="focus"
          >
            <input
              v-model="form.lastName"
              type="text"
              class="form-control"
              id="lastName"
              @blur="validateField('lastName')"
            />
          </el-tooltip>
          <div v-if="errors.lastName" class="text-danger small">
            {{ errors.lastName }}
          </div>
        </div>

        <!-- Email -->
        <div class="mb-3">
          <label for="email" class="form-label">
            Email <span class="text-danger">*</span>
          </label>
          <el-tooltip
            content="Enter a valid email address to receive booking confirmations"
            placement="right"
            effect="dark"
            trigger="focus"
          >
            <input
              v-model="form.email"
              type="email"
              class="form-control"
              id="email"
              @blur="validateField('email')"
            />
          </el-tooltip>
          <div v-if="errors.email" class="text-danger small">
            {{ errors.email }}
          </div>
        </div>

        <!-- Phone number -->
        <div class="mb-3">
          <label for="phone" class="form-label">
            Phone number <span class="text-danger">*</span>
          </label>
          <el-tooltip
            content="Enter your phone number (7-15 digits, can include + and spaces)"
            placement="right"
            effect="dark"
            trigger="focus"
          >
            <input
              v-model="form.phone"
              type="tel"
              class="form-control"
              id="phone"
              @blur="validateField('phone')"
            />
          </el-tooltip>
          <div v-if="errors.phone" class="text-danger small">
            {{ errors.phone }}
          </div>
        </div>

        <!-- Metropolitan Area -->
        <div class="mb-3">
          <div class="form-group">
            <label for="metropolitan-area" class="form-label">
              Location <span class="text-danger">*</span>
            </label>
            <el-tooltip
              content="Select the metropolitan area where the event will take place"
              placement="right"
              effect="dark"
              trigger="focus"
            >
              <div>
                <Multiselect
                  id="metropolitan-area"
                  v-model="selectedArea"
                  :options="listAreas"
                  label="name"
                  track-by="id"
                  @select="onSelectArea"
                />
              </div>
            </el-tooltip>
            <div v-if="errors.areaId" class="text-danger small">
              {{ errors.areaId }}
            </div>
          </div>
        </div>


        <!-- Zipcode -->
        <div class="mb-3">
          <label for="zipcode" class="form-label">
            Zip Code <span class="text-danger">*</span>
          </label>
          <el-tooltip
            content="Enter the zip code for the event location (4-10 digits)"
            placement="right"
            effect="dark"
            trigger="focus"
          >
            <input
              v-model="form.zipcode"
              type="text"
              class="form-control"
              id="zipcode"
              @blur="validateField('zipcode')"
              :disabled="!form.areaId"
            />
          </el-tooltip>
          <div v-if="errors.zipcode" class="text-danger small">
            {{ errors.zipcode }}
          </div>
          <div v-if="!form.areaId" class="text-muted small">
            Please select a metropolitan area first
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, watch, getCurrentInstance, ref, onMounted } from "vue";
import * as yup from "yup";
import api from "@/services/axios";
import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.css";

const { emit } = getCurrentInstance();
const listAreas = ref([]);
const selectedArea = ref(null);
const form = reactive({
  firstName: "",
  lastName: "",
  email: "",
  phone: "",
  areaId: "",
  zipcode: "",
});
const errors = reactive({});
const zipcodeData = ref(null);
const schema = yup.object({
  firstName: yup.string().required("First name is required"),
  lastName: yup.string().required("Last name is required"),
  email: yup.string().email("Invalid email").required("Email is required"),
  phone: yup
    .string()
    .matches(/^[0-9+\s-]{7,15}$/, "Invalid phone number")
    .required("Phone number is required"),
  areaId: yup.string().required("Metropolitan area is required"),
  zipcode: yup
    .string()
    .matches(/^[0-9]{4,10}$/, "Invalid zipcode (4-10 digits)")
    .required("Zipcode is required"),
});
async function validateField(field) {
  try {
    await schema.validateAt(field, form);
    errors[field] = "";
  } catch (e) {
    errors[field] = e.message;
  }
}
function areAllFieldsFilled() {
  return (
    form.firstName.trim() !== "" &&
    form.lastName.trim() !== "" &&
    form.email.trim() !== "" &&
    form.phone.trim() !== "" &&
    form.areaId !== "" &&
    form.zipcode.trim() !== "" &&
    zipcodeData.value !== null
  );
}

const getDataMetropolitan = async () => {
  const response = await api.get("/home/metropolitan-areas");
  listAreas.value = response.data;
};

function onSelectArea(area) {
  form.areaId = area?.id || "";
  form.zipcode = "";
  zipcodeData.value = null;
}

// Función para emitir datos al padre
function emitData() {
  if (areAllFieldsFilled()) {
    emit("setData", { customer: form, zipcode: zipcodeData.value });
  } else {
    emit("setData", { customer: null, zipcode: null });
  }
}

let debounceTimer = null;
watch(
  () => form.zipcode,
  async (newZip) => {
    clearTimeout(debounceTimer);

    if (!form.areaId) {
      zipcodeData.value = null;
      emitData();
      return;
    }

    try {
      await schema.validateAt("zipcode", form);
      errors.zipcode = "";
    } catch (e) {
      errors.zipcode = e.message;
      zipcodeData.value = null;
      emitData();
      return;
    }

    if (newZip && newZip.length >= 4 && newZip.length <= 10) {
      debounceTimer = setTimeout(async () => {
        try {
          const response = await api.get(`/home/zipcode/${form.areaId}/${newZip}`);
          zipcodeData.value = response.data;
          emitData(); // Emitir después de obtener los datos del zipcode
        } catch (error) {
          console.error('Error validating zipcode:', error);
          errors.zipcode = "Invalid zipcode";
          zipcodeData.value = null;
          emitData();
        }
      }, 1000);
    } else {
      zipcodeData.value = null;
      emitData();
    }
  }
);

watch(
  form,
  async (newVal) => {
    try {
      await schema.validate(newVal, { abortEarly: false });
      Object.keys(errors).forEach((key) => (errors[key] = ""));
      emitData();
    } catch (validationErrors) {
      Object.keys(errors).forEach((key) => (errors[key] = ""));
      if (validationErrors?.inner) {
        validationErrors.inner.forEach((err) => {
          errors[err.path] = err.message;
        });
      }
      emit("setData", { customer: null, zipcode: null });
    }
  },
  { deep: true }
);

onMounted(() => {
  getDataMetropolitan();
});
</script>

<style scoped>
.form-control {
  border-radius: 8px;
  font-size: 0.95rem;
  padding: 0.6rem 0.75rem;
}

.form-label {
  font-size: 0.9rem;
  font-weight: 500;
  color: #444;
}

/* Ajustes multiselect */
.multiselect {
  border-radius: 8px;
  min-height: 42px;
}
</style>
