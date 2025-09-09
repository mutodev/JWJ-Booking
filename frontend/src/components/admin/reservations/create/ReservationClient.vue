<template>
  <div class="row justify-content-center">
    <div class="col-10">
      <div class="form-group">
        <label for="client">Client</label>
        <Multiselect
          id="client"
          v-model="client"
          :options="options"
          label="full_name"
          track-by="id"
          placeholder="Select a client"
          @select="onSelect"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from "vue";

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

// Evento al seleccionar
const onSelect = (option) => {
  console.log("Seleccionado:", option);
  // Aquí puedes disparar lo que necesites
};
</script>
