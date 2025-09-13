<template>
  <div class="row justify-content-center">
    <div class="col-10">
      <table class="table table-sm table-borderless align-middle text-center">
        <thead class="border-bottom">
          <tr>
            <th class="text-start">Name</th>
            <th>Unit Cost</th>
            <th>Qty</th>
            <th>Total</th>
          </tr>
        </thead>
        <tbody>
          <!-- Service -->
          <tr :key="data.price?.id || 'service'">
            <td class="text-start fw-medium small">
              {{ data.service?.name || "-" }}
              <br />
              <small class="text-muted">{{
                data.price?.price_type?.toUpperCase() || ""
              }}</small>
            </td>
            <td class="small">{{ formatCurrency(data.price?.amount) }}</td>
            <td class="small">1</td>
            <td class="small">{{ formatCurrency(data.price?.amount) }}</td>
          </tr>

          <!-- Addons -->
          <tr v-for="value in data.addons" :key="value.id" class="small">
            <td class="text-start">{{ value?.name || "-" }}</td>
            <td>{{ formatCurrency(value.base_price) }}</td>
            <td>1</td>
            <td>{{ formatCurrency(value.base_price) }}</td>
          </tr>

          <!-- Extra Children -->
          <tr v-if="data?.form">
            <td class="text-start">Extra Children</td>
            <td>{{ formatCurrency(data.price?.extra_child_fee) }}</td>
            <td>{{ data?.form?.extraChildren || 0 }}</td>
            <td>
              {{
                formatCurrency(
                  (data.form?.extraChildren || 0) *
                    (data.price?.extra_child_fee || 0)
                )
              }}
            </td>
          </tr>
        </tbody>

        <tfoot class="border-top">
          <tr class="fw-semibold small">
            <th colspan="3" class="text-end">TOTAL</th>
            <th>{{ total }}</th>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from "vue";

const props = defineProps({
  data: Object,
});

const data = ref({ service: {}, price: {}, addons: [] });

watch(
  () => props.data,
  (newData) => {
    data.value = newData || { service: {}, price: {}, addons: [] };
  },
  { immediate: true }
);

// Formatear moneda
const formatCurrency = (value) => {
  if (!value) return "$0.00";
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
  }).format(value);
};

// Total reactivo incluyendo addons y extra children
const total = computed(() => {
  let sum = data.value.price?.amount || 0;

  if (data.value.addons?.length) {
    sum += data.value.addons.reduce((acc, a) => acc + (a.base_price || 0), 0);
  }

  // Extra children correctamente sumados
  const extraChildrenQty = data.value.form?.extraChildren || 0;
  const extraChildFee = data.value.price?.extra_child_fee || 0;
  sum += extraChildrenQty * extraChildFee;

  return formatCurrency(sum);
});
</script>

<style scoped>
.table td,
.table th {
  vertical-align: middle;
  font-size: 0.8rem;
}

.table tbody tr:hover {
  background-color: var(--bs-light);
  transition: 0.2s;
}

.text-muted {
  font-size: 0.7rem;
}
</style>
