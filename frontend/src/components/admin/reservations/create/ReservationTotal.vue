<template>
  <div class="row justify-content-center">
    <div class="admin-form col-10">
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

          <!-- Surcharge -->
          <tr v-if="surcharge.amount > 0">
            <td class="text-start text-danger fw-medium">
              Surcharge ({{ surcharge.percent }}%)
            </td>
            <td>-</td>
            <td>-</td>
            <td class="text-danger">{{ formatCurrency(surcharge.amount) }}</td>
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

// Calcula recargo basado en fecha
const surcharge = computed(() => {
  if (!data.value?.form?.date) return { amount: 0, percent: 0 };

  const today = new Date();
  const bookingDate = new Date(data.value.form.date);
  const diffTime = bookingDate - today;
  const diffDays = diffTime / (1000 * 60 * 60 * 24);

  if (diffDays < 2) return { amount: totalBase.value * 0.2, percent: 20 };
  if (diffDays <= 7) return { amount: totalBase.value * 0.1, percent: 10 };
  return { amount: 0, percent: 0 };
});

// Total base sin recargo
const totalBase = computed(() => {
  let sum = data.value.price?.amount || 0;

  if (data.value.addons?.length) {
    sum += data.value.addons.reduce((acc, a) => acc + (a.base_price || 0), 0);
  }

  const extraChildrenQty = data.value.form?.extraChildren || 0;
  const extraChildFee = data.value.price?.extra_child_fee || 0;
  sum += extraChildrenQty * extraChildFee;

  return sum;
});

// Total final incluyendo recargo
const total = computed(() => {
  return formatCurrency(totalBase.value + surcharge.value.amount);
});
</script>

