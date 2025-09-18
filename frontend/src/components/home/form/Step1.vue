<template>
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-12 col-md-6">
        <!-- Título -->
        <h2 class="mb-4 text-center">Formulario de registro</h2>

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
            <div v-if="errors.location" class="text-danger small">
              {{ errors.location }}
            </div>
          </div>
        </div>

        <!-- County -->
        <div class="mb-3">
          <div class="form-group">
            <label for="county" class="form-label">County</label>
            <Multiselect
              id="county"
              v-model="selectedCounty"
              :options="listCounties"
              label="name"
              track-by="id"
              placeholder="Select a county"
              @select="onSelectCounty"
            />
          </div>
        </div>

        <!-- City -->
        <div class="mb-3">
          <div class="form-group">
            <label for="city" class="form-label">City</label>
            <Multiselect
              id="city"
              v-model="selectedCity"
              :options="listCities"
              label="name"
              track-by="id"
              placeholder="Select a city"
              @select="onSelectCity"
            />
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

// listas
const listAreas = ref([]);
const listCounties = ref([]);
const listCities = ref([]);

// valores seleccionados
const selectedArea = ref(null);
const selectedCounty = ref(null);
const selectedCity = ref(null);

// Estado del formulario
const form = reactive({
  firstName: "",
  lastName: "",
  email: "",
  phone: "",
  location: "",
});

// Errores
const errors = reactive({});

// Esquema Yup
const schema = yup.object({
  firstName: yup.string().required("First name is required"),
  lastName: yup.string().required("Last name is required"),
  email: yup.string().email("Invalid email").required("Email is required"),
  phone: yup
    .string()
    .matches(/^[0-9+\s-]{7,15}$/, "Invalid phone number")
    .required("Phone number is required"),
  location: yup.string().required("Location is required"),
});

// Validar campo individual al salir de foco
async function validateField(field) {
  try {
    await schema.validateAt(field, form);
    errors[field] = "";
  } catch (e) {
    errors[field] = e.message;
  }
}

const getDataMtrolitan = async () => {
  const response = await api.get("/home/metropolitan-areas");
  listAreas.value = response.data;
};

function onSelectArea(area) {
  form.location = area?.id || "";
  listCounties.value = []; // limpiar antes de cargar
  selectedCounty.value = null;
  selectedCity.value = null;
  listCities.value = [];
  // petición counties
  if (area?.id) {
    api.get(`/home/counties/${area.id}`).then((res) => {
      listCounties.value = res.data;
    });
  }
}

function onSelectCounty(county) {
  selectedCity.value = null;
  listCities.value = [];
  if (county?.id) {
    api.get(`/home/cities/${county.id}`).then((res) => {
      listCities.value = res.data;
    });
  }
}

function onSelectCity(city) {
  // concatenamos la ubicación final
  form.location = city?.id || form.location;
}

// Observar cambios en el formulario
watch(
  form,
  async (newVal) => {
    try {
      await schema.validate(newVal, { abortEarly: false });
      emit("setData", { customer: newVal });
    } catch (validationErrors) {
      Object.keys(errors).forEach((key) => (errors[key] = ""));
      validationErrors.inner.forEach((err) => {
        errors[err.path] = err.message;
      });
      emit("setData", { customer: null });
    }
  },
  { deep: true }
);

onMounted(() => {
  getDataMtrolitan();
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
