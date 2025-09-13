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

    <!-- Song Requests -->
    <div class="col-10">
      <label for="song_requests" class="form-label">Song Requests</label>
      <textarea
        id="song_requests"
        class="form-control"
        v-model="songRequests"
        rows="2"
        placeholder="Optional"
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
const extraChildren = ref(0);
const eventAddress = ref("");
const arrivalParkingInstructions = ref("");
const childrenAgeRange = ref("");
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
});

const errors = ref({});

// Función para validar y emitir
const validateAndEmit = async () => {
  try {
    const values = {
      date: date.value,
      startTime: startTime.value,
      extraChildren: extraChildren.value ?? 0,
      eventAddress: eventAddress.value,
      arrivalParkingInstructions: arrivalParkingInstructions.value,
      childrenAgeRange: childrenAgeRange.value,
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
    extraChildren,
    eventAddress,
    arrivalParkingInstructions,
    childrenAgeRange,
    songRequests,
  ],
  () => {
    validateAndEmit();
  }
);
</script>
