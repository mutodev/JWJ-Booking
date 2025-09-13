<template>
  <div class="row justify-content-center">
    <div class="col-5">
      <div class="form-group">
        <label for="client">Client</label>
        <Multiselect
          id="client"
          v-model="client"
          :options="options"
          label="full_name"
          track-by="id"
          placeholder="Select a client"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from "vue";

const emit = defineEmits(["setData"]);
const props = defineProps({
  customers: { type: Array, default: () => [] },
});

const client = ref(null);
const options = ref([]);

watch(
  () => props.customers,
  (newData) => {
    options.value = [...newData];
  },
  { immediate: true }
);

watch(client, (newVal) => {
  if (newVal) {
    emit("setData", { customer: newVal });
  } else {
    emit("setData", { customer: null, areas: null, service: null, addons: null,  form: null});
  }
});
</script>
