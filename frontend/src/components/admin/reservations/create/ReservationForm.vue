<template>
  <div class="reservation-create-form">
    <div class="form-section">
      <div class="section-heading">
        <i class="bi bi-calendar-event"></i>
        <span>Event details</span>
      </div>

      <div class="form-grid">
        <div class="field">
          <label for="date" class="form-label">
            Date <span class="text-danger">*</span>
          </label>
          <input
            id="date"
            type="date"
            class="form-control"
            v-model="date"
            :min="today"
          />
          <span v-if="errors.date" class="text-danger small">
            {{ errors.date }}
          </span>
        </div>

        <div class="field">
          <label for="start_time" class="form-label">
            Event Start Time <span class="text-danger">*</span>
          </label>
          <input
            id="start_time"
            type="time"
            class="form-control"
            v-model="startTime"
          />
          <span v-if="errors.startTime" class="text-danger small">
            {{ errors.startTime }}
          </span>
        </div>

        <div class="field field-wide">
          <label for="event_address" class="form-label">
            Reconfirm Address <span class="text-danger">*</span>
          </label>
          <input
            id="event_address"
            type="text"
            class="form-control"
            v-model="eventAddress"
            minlength="6"
            placeholder="123 Main St, Apt #4"
            autocomplete="street-address"
          />
          <span v-if="errors.eventAddress" class="text-danger small">
            {{ errors.eventAddress }}
          </span>
        </div>

        <div class="field">
          <label for="entertainment_start_time" class="form-label">
            Entertainment Start Time
          </label>
          <input
            id="entertainment_start_time"
            type="time"
            class="form-control"
            v-model="entertainmentStartTime"
          />
          <small class="field-note">
            (recommended at least 30 minutes after the party start time)
          </small>
        </div>

        <div class="field">
          <label for="extra_children" class="form-label">
            Number of Children
          </label>
          <input
            id="extra_children"
            type="number"
            class="form-control"
            v-model="extraChildren"
            min="0"
          />
          <span v-if="errors.extraChildren" class="text-danger small">
            {{ errors.extraChildren }}
          </span>
        </div>
      </div>
    </div>

    <div class="form-section">
      <div class="section-heading">
        <i class="bi bi-info-circle"></i>
        <span>Event notes</span>
      </div>

      <div class="form-grid">
        <div class="field field-wide">
          <label for="arrival_parking_instructions" class="form-label">
            Provide detailed arrival and parking instructions
          </label>
          <textarea
            id="arrival_parking_instructions"
            class="form-control"
            v-model="arrivalParkingInstructions"
            rows="3"
          ></textarea>
        </div>

        <div class="field field-wide">
          <label for="children_age_range" class="form-label">
            Number of children and their age range <span class="text-danger">*</span>
          </label>
          <input
            id="children_age_range"
            type="text"
            class="form-control"
            v-model="childrenAgeRange"
            placeholder="12 children, ages 4-8"
          />
          <span v-if="errors.childrenAgeRange" class="text-danger small">
            {{ errors.childrenAgeRange }}
          </span>
        </div>
      </div>
    </div>

    <div class="form-section">
      <div class="section-heading">
        <i class="bi bi-music-note-beamed"></i>
        <span>Birthday and music</span>
      </div>

      <div class="form-grid">
        <div class="field">
          <label for="birthday_child_name" class="form-label">
            Birthday child’s name <span class="text-danger">*</span>
          </label>
          <input
            id="birthday_child_name"
            type="text"
            class="form-control"
            v-model="birthdayChildName"
            placeholder="Enter name or N/A"
          />
          <span v-if="errors.birthdayChildName" class="text-danger small">
            {{ errors.birthdayChildName }}
          </span>
        </div>

        <div class="field">
          <label for="child_age" class="form-label">
            Age they are turning <span class="text-danger">*</span>
          </label>
          <input
            id="child_age"
            type="text"
            class="form-control"
            v-model="childAge"
            placeholder="Enter age (1-18) or N/A"
          />
          <span v-if="errors.childAge" class="text-danger small">
            {{ errors.childAge }}
          </span>
        </div>

        <div class="field field-wide">
          <label for="song_requests" class="form-label">
            Song requests, up to 3 (provide links)
          </label>
          <textarea
            id="song_requests"
            class="form-control"
            v-model="songRequests"
            rows="3"
          ></textarea>
        </div>

        <div class="field field-wide">
          <label for="happy_birthday_request" class="form-label">
            Would you like Happy Birthday to be sung at the end of the set? <span class="text-danger">*</span>
          </label>
          <select
            id="happy_birthday_request"
            class="form-select"
            v-model="happyBirthdayRequest"
          >
            <option value="">Please select</option>
            <option value="yes">Yes</option>
            <option value="no">No</option>
          </select>
          <span v-if="errors.happyBirthdayRequest" class="text-danger small">
            {{ errors.happyBirthdayRequest }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from "vue";
import * as yup from "yup";

const emit = defineEmits(["setData"]);

// Inputs
const today = new Date().toISOString().split("T")[0];
const date = ref(today);
const startTime = ref("");
const entertainmentStartTime = ref("");
const extraChildren = ref(0);
const eventAddress = ref("");
const arrivalParkingInstructions = ref("");
const childrenAgeRange = ref("");
const birthdayChildName = ref("");
const childAge = ref("");
const happyBirthdayRequest = ref("");
const songRequests = ref("");

// Validation schema
const schema = yup.object({
  date: yup
    .date()
    .min(
      new Date(new Date().setHours(0, 0, 0, 0)),
      "Date cannot be in the past"
    )
    .required("Date is required"),
  startTime: yup.string().required("Start time is required"),
  extraChildren: yup
    .number()
    .min(0, "Extra children must be 0 or more")
    .required("Extra children is required or value 0"),
  eventAddress: yup
    .string()
    .min(6, "Event address must be at least 6 characters")
    .required("Event address is required"),
  childrenAgeRange: yup.string().required("Children age range is required"),
  birthdayChildName: yup.string().required("Birthday child's name is required (or N/A)"),
  childAge: yup.mixed().test('valid-age', 'Age must be between 1-18 or N/A', function(value) {
    if (!value || value === '' || value.toString().toUpperCase() === 'N/A') return true;
    const num = Number(value);
    return !isNaN(num) && num >= 1 && num <= 18;
  }).required("Child age is required (or N/A)"),
  happyBirthdayRequest: yup.string().oneOf(["yes", "no"], "Please select an option").required("Please select an option"),
});

const errors = ref({});

// Función para validar y emitir
const validateAndEmit = async () => {
  try {
    const values = {
      date: date.value,
      startTime: startTime.value,
      entertainmentStartTime: entertainmentStartTime.value,
      extraChildren: extraChildren.value ?? 0,
      eventAddress: eventAddress.value,
      arrivalParkingInstructions: arrivalParkingInstructions.value,
      childrenAgeRange: childrenAgeRange.value,
      birthdayChildName: birthdayChildName.value,
      childAge: childAge.value,
      happyBirthdayRequest: happyBirthdayRequest.value,
      songRequests: songRequests.value,
    };

    await schema.validate(values, { abortEarly: false });
    errors.value = {};

    emit("setData", { form: values });
  } catch (err) {
    const newErrors = {};
    err.inner.forEach((e) => {
      newErrors[e.path] = e.message;
    });
    errors.value = newErrors;
  }
};

// Dispara el evento cada vez que cambie un valor
watch(
  [
    date,
    startTime,
    entertainmentStartTime,
    extraChildren,
    eventAddress,
    arrivalParkingInstructions,
    childrenAgeRange,
    birthdayChildName,
    childAge,
    happyBirthdayRequest,
    songRequests,
  ],
  () => {
    validateAndEmit();
  }
);
</script>

<style scoped>
.reservation-create-form {
  max-width: 980px;
  margin: 18px auto 0;
}

.form-section {
  padding: 18px 0;
  border-top: 1px solid #e5e7eb;
}

.form-section:first-child {
  border-top: 0;
  padding-top: 0;
}

.section-heading {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 14px;
  color: #374151;
  font-size: 0.9rem;
  font-weight: 700;
}

.section-heading i {
  color: #0d6efd;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px 18px;
}

.field {
  min-width: 0;
}

.field-wide {
  grid-column: 1 / -1;
}

.form-label {
  display: block;
  margin-bottom: 6px;
  color: #4b5563;
  font-size: 0.84rem;
  font-weight: 700;
}

.form-control,
.form-select {
  min-height: 42px;
  border-color: #d1d5db;
}

textarea.form-control {
  min-height: 92px;
  resize: vertical;
}

.field-note {
  display: block;
  margin-top: 6px;
  color: #6b7280;
  font-size: 0.78rem;
  line-height: 1.35;
}

@media (max-width: 768px) {
  .reservation-create-form {
    margin-top: 14px;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }
}
</style>
