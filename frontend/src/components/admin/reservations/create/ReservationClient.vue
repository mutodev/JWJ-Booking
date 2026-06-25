<template>
  <div class="row justify-content-center">
    <div class="col-5">
      <div class="form-group">
        <label for="client">Client</label>
        <div class="d-flex gap-2 align-items-start">
          <div class="flex-grow-1">
            <Multiselect
              id="client"
              v-model="client"
              :options="options"
              label="full_name"
              track-by="id"
              placeholder="Select a client"
            />
          </div>
          <button
            type="button"
            class="btn btn-sm btn-outline-primary"
            style="white-space: nowrap; height: 40px;"
            @click="showClientCreate = true"
            title="Create new client"
          >
            <i class="bi bi-person-plus"></i> New
          </button>
        </div>
      </div>
    </div>
  </div>

  <ClientCreate
    :show="showClientCreate"
    @close="showClientCreate = false"
    @saved="onClientCreated"
  />
</template>

<script setup>
import { ref, watch } from "vue";
import api from "@/services/axios";
import ClientCreate from "@/components/admin/client/ClientCreate.vue";

const emit = defineEmits(["setData"]);
const props = defineProps({
  customers: { type: Array, default: () => [] },
});

const client = ref(null);
const options = ref([]);
const showClientCreate = ref(false);

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
    emit("setData", { customer: null, areas: null, service: null, addons: null, form: null });
  }
});

const onClientCreated = async (customerId) => {
  showClientCreate.value = false;

  if (!customerId) return;

  try {
    const response = await api.get(`/customers/${customerId}`);
    const newCustomer = response.data;

    if (!newCustomer) return;

    // Add to the list if not already present
    if (!options.value.find((c) => c.id === newCustomer.id)) {
      options.value = [...options.value, newCustomer];
    }

    // Auto-select the new customer
    client.value = newCustomer;
  } catch {
    // If fetch fails, the modal closed cleanly — user can search manually
  }
};
</script>
