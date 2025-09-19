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
          <input
            v-model="form.firstName"
            type="text"
            class="form-control"
            id="firstName"
            placeholder="Enter your first name"
            @blur="validateField('firstName')"
          />
          <div v-if="errors.firstName" class="text-danger small">
            {{ errors.firstName }}
          </div>
        </div>

        <!-- Last name -->
        <div class="mb-3">
          <label for="lastName" class="form-label">
            Last name <span class="text-danger">*</span>
          </label>
          <input
            v-model="form.lastName"
            type="text"
            class="form-control"
            id="lastName"
            placeholder="Enter your last name"
            @blur="validateField('lastName')"
          />
          <div v-if="errors.lastName" class="text-danger small">
            {{ errors.lastName }}
          </div>
        </div>

        <!-- Email -->
        <div class="mb-3">
          <label for="email" class="form-label">
            Email <span class="text-danger">*</span>
          </label>
          <input
            v-model="form.email"
            type="email"
            class="form-control"
            id="email"
            placeholder="Enter your email (e.g. name@example.com)"
            @blur="validateField('email')"
          />
          <div v-if="errors.email" class="text-danger small">
            {{ errors.email }}
          </div>
        </div>

        <!-- Phone number -->
        <div class="mb-3">
          <label for="phone" class="form-label">
            Phone number <span class="text-danger">*</span>
          </label>
          <input
            v-model="form.phone"
            type="tel"
            class="form-control"
            id="phone"
            placeholder="Enter your phone number (e.g. +1 555 123 4567)"
            @blur="validateField('phone')"
          />
          <div v-if="errors.phone" class="text-danger small">
            {{ errors.phone }}
          </div>
        </div>

        <!-- Metropolitan Area -->
        <div class="mb-3">
          <div class="form-group">
            <label for="metropolitan-area" class="form-label">
              Metropolitan Area <span class="text-danger">*</span>
            </label>
            <Multiselect
              id="metropolitan-area"
              v-model="selectedArea"
              :options="listAreas"
              label="name"
              track-by="id"
              placeholder="Select a metropolitan area"
              @select="onSelectArea"
            />
            <div v-if="errors.areaId" class="text-danger small">
              {{ errors.areaId }}
            </div>
          </div>
        </div>

        <!-- County -->
        <div class="mb-3">
          <div class="form-group">
            <label for="county" class="form-label">
              County <span class="text-danger">*</span>
            </label>
            <Multiselect
              id="county"
              v-model="selectedCounty"
              :options="listCounties"
              label="name"
              track-by="id"
              placeholder="Select a county"
              @select="onSelectCounty"
            />
            <div v-if="errors.countyId" class="text-danger small">
              {{ errors.countyId }}
            </div>
          </div>
        </div>

        <!-- City -->
        <div class="mb-3">
          <div class="form-group">
            <label for="city" class="form-label">
              City <span class="text-danger">*</span>
            </label>
            <Multiselect
              id="city"
              v-model="selectedCity"
              :options="listCities"
              label="name"
              track-by="id"
              placeholder="Select a city"
              @select="onSelectCity"
            />
            <div v-if="errors.cityId" class="text-danger small">
              {{ errors.cityId }}
            </div>
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
const listCounties = ref([]);
const listCities = ref([]);
const selectedArea = ref(null);
const selectedCounty = ref(null);
const selectedCity = ref(null);
const form = reactive({
  firstName: "",
  lastName: "",
  email: "",
  phone: "",
  areaId: "",
  countyId: "",
  cityId: "",
});
const errors = reactive({});
const schema = yup.object({
  firstName: yup.string().required("First name is required"),
  lastName: yup.string().required("Last name is required"),
  email: yup.string().email("Invalid email").required("Email is required"),
  phone: yup
    .string()
    .matches(/^[0-9+\s-]{7,15}$/, "Invalid phone number")
    .required("Phone number is required"),
  areaId: yup.string().required("Metropolitan area is required"),
  countyId: yup.string().required("County is required"),
  cityId: yup.string().required("City is required"),
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
    form.countyId !== "" &&
    form.cityId !== ""
  );
}

const getDataMetropolitan = async () => {
  const response = await api.get("/home/metropolitan-areas");
  listAreas.value = response.data;
};

function onSelectArea(area) {
  form.areaId = area?.id || "";
  form.countyId = "";
  form.cityId = "";

  listCounties.value = [];
  listCities.value = [];
  selectedCounty.value = null;
  selectedCity.value = null;

  if (area?.id) {
    api.get(`/home/counties/${area.id}`).then((res) => {
      listCounties.value = res.data;
    });
  }
}

function onSelectCounty(county) {
  form.countyId = county?.id || "";
  form.cityId = "";

  listCities.value = [];
  selectedCity.value = null;

  if (county?.id) {
    api.get(`/home/cities/${county.id}`).then((res) => {
      listCities.value = res.data;
    });
  }
}

function onSelectCity(city) {
  form.cityId = city?.id || "";
}
watch(
  form,
  async (newVal) => {
    try {
      await schema.validate(newVal, { abortEarly: false });
      Object.keys(errors).forEach((key) => (errors[key] = ""));

      if (areAllFieldsFilled()) {
        emit("setData", { customer: newVal });
      } else {
        emit("setData", { customer: null });
      }
    } catch (validationErrors) {
      Object.keys(errors).forEach((key) => (errors[key] = ""));
      if (validationErrors?.inner) {
        validationErrors.inner.forEach((err) => {
          errors[err.path] = err.message;
        });
      }
      emit("setData", { customer: null });
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
