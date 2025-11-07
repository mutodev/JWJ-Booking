<template>
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-12 col-md-8">
        <!-- Welcome Intro Text -->
        <div class="welcome-section mb-4 text-center">
          <h2 class="mb-3">Welcome to Jam with Jamie!</h2>
          <p class="intro-text">
            We're thrilled to help you bring the party to life!
          </p>
          <p class="intro-text">
            Please fill out a few details to get started, and we'll guide you through selecting the perfect music experience for your event. Let's jam!
          </p>
        </div>

        <!-- Type of Event -->
        <div class="mb-4">
          <label class="form-label mb-3">
            Type of Event <span class="text-danger">*</span>
          </label>
          <el-radio-group v-model="form.eventType" class="event-radio-group">
            <el-radio label="Birthday Party" class="event-type-radio mb-3">Birthday Party</el-radio>
            <el-radio label="Event" class="event-type-radio mb-3">Event</el-radio>
            <el-radio label="One Time Jam Session" class="event-type-radio mb-3">One Time Jam Session</el-radio>
          </el-radio-group>
          <div v-if="errors.eventType" class="text-danger small mt-2">
            {{ errors.eventType }}
          </div>
        </div>

        <!-- Date and Time -->
        <div class="mb-3">
          <label for="eventDateTime" class="form-label">
            Date and Time <span class="text-danger">*</span>
          </label>
          <el-tooltip
            content="We recommend booking at least 4-6 weeks in advance to secure your preferred date and time!"
            placement="right"
            effect="dark"
            trigger="focus"
          >
            <el-date-picker
              v-model="form.eventDateTime"
              type="datetime"
              placeholder="Select date and time"
              format="MM/DD/YYYY hh:mm A"
              value-format="YYYY-MM-DD HH:mm:ss"
              class="w-100"
              :disabled-date="disabledDate"
              @change="validateField('eventDateTime')"
            />
          </el-tooltip>
          <div v-if="errors.eventDateTime" class="text-danger small">
            {{ errors.eventDateTime }}
          </div>
        </div>

        <!-- Number of Children -->
        <div class="mb-4">
          <label class="form-label mb-3">
            Number of Children <span class="text-danger">*</span>
          </label>
          <el-radio-group v-model="form.childrenRange" class="children-radio-group" @change="onChildrenRangeChange">
            <el-radio label="1-10 kids" class="children-range-radio mb-3">1-10 kids</el-radio>
            <el-radio label="11-24 kids" class="children-range-radio mb-3">11-24 kids</el-radio>
            <el-radio label="25+ kids" class="children-range-radio mb-3">25+ kids</el-radio>
          </el-radio-group>
          <div v-if="errors.childrenRange" class="text-danger small mt-2">
            {{ errors.childrenRange }}
          </div>

          <!-- Input para 25+ kids -->
          <div v-if="form.childrenRange === '25+ kids'" class="mt-3">
            <label class="form-label">Exact number of children:</label>
            <el-input-number
              v-model="exactKidsCount"
              :min="25"
              :max="200"
              class="w-100"
              @change="confirmKidsCount"
            />
          </div>
        </div>

        <!-- Metropolitan Area -->
        <div class="mb-3">
          <div class="form-group">
            <label for="metropolitan-area" class="form-label">
              Metropolitan Area <span class="text-danger">*</span>
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
import { useToast } from "vue-toastification";

const toast = useToast();

const { emit } = getCurrentInstance();
const listAreas = ref([]);
const selectedArea = ref(null);
const exactKidsCount = ref(25);
const form = reactive({
  eventType: "",
  eventDateTime: "",
  childrenRange: "",
  exactChildrenCount: null,
  areaId: "",
  zipcode: "",
  firstName: "",
  lastName: "",
  email: "",
  phone: "",
});
const errors = reactive({});
const zipcodeData = ref(null);

const schema = yup.object({
  eventType: yup.string().required("Event type is required"),
  eventDateTime: yup.string().required("Date and time are required"),
  childrenRange: yup.string().required("Number of children is required"),
  areaId: yup.string().required("Metropolitan area is required"),
  zipcode: yup
    .string()
    .matches(/^[0-9]{4,10}$/, "Invalid zipcode (4-10 digits)")
    .required("Zipcode is required"),
  firstName: yup.string().required("First name is required"),
  lastName: yup.string().required("Last name is required"),
  email: yup.string().email("Invalid email").required("Email is required"),
  phone: yup
    .string()
    .matches(/^[0-9+\s-]{7,15}$/, "Invalid phone number")
    .required("Phone number is required"),
});

// Función para deshabilitar fechas pasadas
function disabledDate(time) {
  // Obtener la fecha de hoy a las 00:00:00
  const today = new Date();
  today.setHours(0, 0, 0, 0);

  // Deshabilitar si la fecha es anterior a hoy
  return time.getTime() < today.getTime();
}

async function validateField(field) {
  try {
    await schema.validateAt(field, form);
    errors[field] = "";
  } catch (e) {
    errors[field] = e.message;
  }
}

function onChildrenRangeChange(value) {
  if (value === "25+ kids") {
    exactKidsCount.value = 25;
    form.exactChildrenCount = null;
    toast.info(
      'Please enter the exact number of children below',
      { timeout: 3000 }
    );
  } else {
    form.exactChildrenCount = null;
    exactKidsCount.value = 25;
  }
  emitData();
}

function confirmKidsCount(value) {
  if (value >= 25) {
    form.exactChildrenCount = value;
    toast.success(
      `Confirmed: ${value} children`,
      { timeout: 2000 }
    );
    emitData();
  }
}

function areAllFieldsFilled() {
  const baseFieldsFilled =
    form.eventType.trim() !== "" &&
    form.eventDateTime !== "" &&
    form.childrenRange.trim() !== "" &&
    form.areaId !== "" &&
    form.zipcode.trim() !== "" &&
    form.firstName.trim() !== "" &&
    form.lastName.trim() !== "" &&
    form.email.trim() !== "" &&
    form.phone.trim() !== "" &&
    zipcodeData.value !== null;

  // If "25+ kids" is selected, also check exactChildrenCount
  if (form.childrenRange === "25+ kids") {
    return baseFieldsFilled && form.exactChildrenCount !== null;
  }

  return baseFieldsFilled;
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
  const isValid = areAllFieldsFilled();

  // Siempre emitimos los datos, pero marcamos si es válido
  emit("setData", {
    customer: { ...form, isValid },
    zipcode: zipcodeData.value
  });
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
          emitData();
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
      // Emitimos de todos modos, pero con isValid = false
      emitData();
    }
  },
  { deep: true }
);

onMounted(() => {
  getDataMetropolitan();
});
</script>

<style scoped>
.welcome-section {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  padding: 2rem;
  border-radius: 16px;
  border: 2px solid #FF74B7;
  box-shadow: 0 4px 6px rgba(255, 116, 183, 0.1);
}

.welcome-section h2 {
  font-size: 1.8rem;
  font-weight: 700;
  color: #1f2937;
  margin-bottom: 1rem;
}

.intro-text {
  font-size: 1.05rem;
  line-height: 1.6;
  color: #4b5563;
  margin-bottom: 0.5rem;
}

.form-control {
  border-radius: 8px;
  font-size: 0.95rem;
  padding: 0.6rem 0.75rem;
}

.form-label {
  font-size: 0.9rem;
  font-weight: 500;
  color: #444;
  margin-bottom: 0.5rem;
}

/* Radio groups styling */
.event-radio-group,
.children-radio-group {
  display: flex;
  flex-direction: column;
  width: 100%;
}

/* Radio buttons styling */
.event-type-radio,
.children-range-radio {
  display: flex !important;
  align-items: center;
  width: 100%;
  padding: 1rem 1.25rem !important;
  border: 2px solid #e5e7eb;
  border-radius: 10px;
  background: white;
  transition: all 0.2s ease;
  cursor: pointer;
  margin-right: 0 !important;
}

.event-type-radio:hover,
.children-range-radio:hover {
  border-color: #FF74B7;
  background: #fff5f9;
  transform: translateX(4px);
}

/* Radio checked state */
:deep(.el-radio__input.is-checked) + .el-radio__label {
  color: #FF74B7 !important;
  font-weight: 600;
}

:deep(.el-radio__input.is-checked) ~ .el-radio__label {
  color: #FF74B7 !important;
}

:deep(.event-type-radio.is-checked),
:deep(.children-range-radio.is-checked) {
  border-color: #FF74B7 !important;
  background: #fff5f9 !important;
  box-shadow: 0 2px 8px rgba(255, 116, 183, 0.2);
}

:deep(.el-radio__input.is-checked .el-radio__inner) {
  border-color: #FF74B7;
  background: #FF74B7;
}

:deep(.el-radio__inner) {
  width: 18px;
  height: 18px;
}

:deep(.el-radio__label) {
  font-size: 1rem;
  padding-left: 12px;
}

/* Multiselect styling */
.multiselect {
  border-radius: 8px;
  min-height: 42px;
}

/* Date picker styling */
:deep(.el-date-editor) {
  width: 100%;
  border-radius: 8px;
}

/* Input number styling */
:deep(.el-input-number) {
  width: 100%;
}

@media (max-width: 768px) {
  .welcome-section {
    padding: 1.5rem;
  }

  .welcome-section h2 {
    font-size: 1.5rem;
  }

  .intro-text {
    font-size: 0.95rem;
  }
}
</style>
