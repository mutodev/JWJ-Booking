<template>
  <div class="row justify-content-center">
    <div class="col-5">
      <div class="form-group">
        <label for="service">Service</label>
        <Multiselect
          id="service"
          v-model="service"
          :options="serviceList"
          label="name"
          track-by="id"
          placeholder="Select a service"
          @select="onSelect"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from "vue";

const props = defineProps({
  services: { type: Array, default: () => [] },
});

const service = ref(null);

const serviceList = ref([]);
watch(
  () => props.services,
  (newData) => {
    serviceList.value = [...newData];
  },
  { immediate: true }
);
</script>
