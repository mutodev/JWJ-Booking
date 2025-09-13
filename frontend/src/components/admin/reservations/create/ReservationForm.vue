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
    .required("Extra children is required"),
});

const errors = ref({});

// Función para validar y emitir
const validateAndEmit = async () => {
  try {
    const values = {
      date: date.value,
      startTime: startTime.value,
      extraChildren: extraChildren.value,
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
watch([date, startTime, extraChildren], () => {
  validateAndEmit();
});
</script>
