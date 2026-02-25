<template>
  <div class="row justify-content-center">
    <!-- Date -->
    <div class="col-5">
      <label for="date" class="form-label">Date</label>
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

    <!-- Start Time -->
    <div class="col-5">
      <label for="start_time" class="form-label">Start Time</label>
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

    <!-- Extra Children -->
    <div class="col-5">
      <label for="extra_children" class="form-label">Extra Children</label>
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

    <!-- Children Age Range -->
    <div class="col-5">
      <label for="children_age_range" class="form-label"
        >Children Age Range</label
      >
      <input
        id="children_age_range"
        type="text"
        class="form-control"
        v-model="childrenAgeRange"
        placeholder="Optional"
      />
    </div>

    <!-- Event Address -->
    <div class="col-5">
      <label for="event_address" class="form-label">Event Address</label>
      <input
        id="event_address"
        type="text"
        class="form-control"
        v-model="eventAddress"
        minlength="6"
        placeholder="Enter event address"
      />
      <span v-if="errors.eventAddress" class="text-danger small">
        {{ errors.eventAddress }}
      </span>
    </div>

    <!-- Arrival Parking Instructions -->
    <div class="col-5">
      <label for="arrival_parking_instructions" class="form-label">
        Arrival / Parking Instructions
      </label>
      <input
        id="arrival_parking_instructions"
        type="text"
        class="form-control"
        v-model="arrivalParkingInstructions"
        placeholder="Optional"
      />
    </div>

    <!-- Entertainment Start Time -->
    <div class="col-5">
      <label for="entertainment_start_time" class="form-label">
        Entertainment Start Time
      </label>
      <input
        id="entertainment_start_time"
        type="time"
        class="form-control"
        v-model="entertainmentStartTime"
        placeholder="Optional"
      />
      <small class="text-muted">Recommended at least 30 minutes after party start</small>
    </div>

    <!-- Birthday Child's Name -->
    <div class="col-5">
      <label for="birthday_child_name" class="form-label">
        Birthday Child's Name
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

    <!-- Age They Are Turning -->
    <div class="col-5">
      <label for="child_age" class="form-label">
        Age They Are Turning
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

    <!-- Happy Birthday Song Request -->
    <div class="col-5">
      <label for="happy_birthday_request" class="form-label">
        Happy Birthday Song at End of Set?
      </label>
      <select
        id="happy_birthday_request"
        class="form-control"
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

    <!-- Song Requests -->
    <div class="col-5">
      <label for="song_requests" class="form-label">Song Requests</label>
      <textarea
        id="song_requests"
        class="form-control"
        v-model="songRequests"
        rows="2"
        placeholder="Optional (up to 3 songs)"
      ></textarea>
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
