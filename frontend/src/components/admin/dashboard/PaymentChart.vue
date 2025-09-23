<template>
  <div class="card shadow-sm h-100">
    <div class="card-header bg-white border-bottom">
      <h5 class="card-title mb-0">
        <i class="bi bi-credit-card me-2 text-success"></i>
        Payment Status
      </h5>
    </div>
    <div class="card-body">
      <!-- Loading State -->
      <div v-if="loading" class="loading-container">
        <div class="spinner-border text-success" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
      </div>
      <!-- Chart Display -->
      <div v-else class="chart-container">
        <Bar
          :data="chartData"
          :options="chartOptions"
          ref="chartRef"
        />
      </div>
      <!-- Payment Statistics -->
      <div class="dashboard-stats">
        <div class="row">
          <div class="col-6">
            <h4 class="fw-bold text-success">${{ formatCurrency(totalPaidAmount) }}</h4>
            <small class="text-muted">Total Collected</small>
          </div>
          <div class="col-6">
            <h4 class="fw-bold text-warning">${{ formatCurrency(totalPendingAmount) }}</h4>
            <small class="text-muted">Pending</small>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
/**
 * PaymentChart Component
 *
 * Displays payment status analytics in a bar chart format.
 * Shows breakdown of paid vs pending payments with revenue totals.
 *
 * @component
 * @example
 * <PaymentChart :data="paymentData" :loading="false" />
 */

import { computed } from 'vue';
import { Bar } from 'vue-chartjs';

// ========================
// COMPONENT PROPS
// ========================

const props = defineProps({
  /** @type {Object} Payment analytics data with paid/pending breakdown */
  data: {
    type: Object,
    default: () => ({}),
    validator: (data) => typeof data === 'object'
  },
  /** @type {boolean} Loading state indicator */
  loading: {
    type: Boolean,
    default: false
  }
});

// ========================
// COMPUTED PROPERTIES
// ========================

/**
 * Total amount collected from paid reservations
 * @returns {number} Sum of all paid amounts
 */
const totalPaidAmount = computed(() => props.data.data?.paid?.amount || 0);

/**
 * Total amount pending collection
 * @returns {number} Sum of all pending amounts
 */
const totalPendingAmount = computed(() => props.data.data?.pending?.amount || 0);

/**
 * Formats data for Chart.js bar chart
 * @returns {Object} Chart.js compatible data structure
 */
const chartData = computed(() => ({
  labels: ['Paid', 'Pending'],
  datasets: [{
    label: 'Count',
    data: [
      props.data.data?.paid?.count || 0,
      props.data.data?.pending?.count || 0
    ],
    backgroundColor: ['#28a745', '#ffc107'],
    borderWidth: 0,
    borderRadius: 4
  }]
}));

// ========================
// CHART CONFIGURATION
// ========================

/**
 * Chart.js configuration options optimized for payment display
 * @type {Object}
 */
const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false
    },
    tooltip: {
      callbacks: {
        label: function(context) {
          const isPaid = context.dataIndex === 0;
          const amount = isPaid ? totalPaidAmount.value : totalPendingAmount.value;
          return `${context.label}: ${context.parsed} reservations ($${formatCurrency(amount)})`;
        }
      }
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        stepSize: 1,
        precision: 0
      },
      grid: {
        color: '#f1f3f4'
      }
    },
    x: {
      grid: {
        display: false
      }
    }
  },
  // Performance optimizations
  animation: {
    duration: 750
  }
};

// ========================
// UTILITY FUNCTIONS
// ========================

/**
 * Formats currency values for display
 * @param {number} amount - Raw amount value
 * @returns {string} Formatted currency string
 */
const formatCurrency = (amount) => {
  if (typeof amount !== 'number' || isNaN(amount)) return '0.00';

  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(amount);
};
</script>