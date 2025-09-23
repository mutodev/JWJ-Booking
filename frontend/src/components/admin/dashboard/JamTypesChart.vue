<template>
  <div class="card shadow-sm h-100">
    <div class="card-header bg-white border-bottom">
      <h5 class="card-title mb-0">
        <i class="bi bi-star me-2 text-warning"></i>
        Most Popular JAM Types
      </h5>
    </div>
    <div class="card-body">
      <!-- Loading State -->
      <div v-if="loading" class="loading-container">
        <div class="spinner-border text-warning" role="status">
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
      <!-- Service Statistics -->
      <div class="dashboard-stats">
        <div class="row">
          <div class="col-6">
            <h4 class="fw-bold text-warning">{{ totalReservations }}</h4>
            <small class="text-muted">Total Reservations</small>
          </div>
          <div class="col-6">
            <h4 class="fw-bold text-success">${{ formatCurrency(totalRevenue) }}</h4>
            <small class="text-muted">Revenue</small>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
/**
 * JamTypesChart Component
 *
 * Displays JAM type popularity analytics in a bar chart format.
 * Shows most popular service types with reservation counts and revenue data.
 *
 * @component
 * @example
 * <JamTypesChart :data="jamTypesData" :loading="false" />
 */

import { computed } from 'vue';
import { Bar } from 'vue-chartjs';

// ========================
// COMPONENT PROPS
// ========================

const props = defineProps({
  /** @type {Object} JAM types analytics data with totals and service breakdown */
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
 * Total reservations across all JAM types
 * @returns {number} Sum of all reservations
 */
const totalReservations = computed(() => props.data.total_reservations || 0);

/**
 * Total revenue across all JAM types
 * @returns {number} Sum of all revenue
 */
const totalRevenue = computed(() => props.data.total_revenue || 0);

/**
 * Formats data for Chart.js bar chart
 * @returns {Object} Chart.js compatible data structure
 */
const chartData = computed(() => ({
  labels: props.data.data?.map(item => item.jam_type || 'Unknown') || [],
  datasets: [{
    label: 'Reservations',
    data: props.data.data?.map(item => item.total_reservations || 0) || [],
    backgroundColor: props.data.data?.map(item => item.color || '#6c757d') || [],
    borderWidth: 0,
    borderRadius: 4
  }]
}));

// ========================
// CHART CONFIGURATION
// ========================

/**
 * Chart.js configuration options optimized for service type display
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
          const dataIndex = context.dataIndex;
          const item = props.data.data?.[dataIndex];
          const revenue = item?.total_revenue ? formatCurrency(item.total_revenue) : '0.00';
          return `${context.label}: ${context.parsed} reservations ($${revenue})`;
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