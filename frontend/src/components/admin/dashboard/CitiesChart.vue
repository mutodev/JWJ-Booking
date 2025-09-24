<template>
  <div class="admin-dashboard card shadow-sm h-100">
    <div class="card-header bg-white border-bottom">
      <h5 class="card-title mb-0">
        <i class="bi bi-geo-alt me-2 text-danger"></i>
        Cities with Most Events
      </h5>
    </div>
    <div class="card-body">
      <!-- Loading State -->
      <div v-if="loading" class="loading-container">
        <div class="spinner-border text-danger" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
      </div>
      <!-- Chart Display -->
      <div v-else class="chart-container">
        <Doughnut
          :data="chartData"
          :options="chartOptions"
          ref="chartRef"
        />
      </div>
      <!-- Location Statistics -->
      <div class="dashboard-stats">
        <div class="row">
          <div class="col-6">
            <h4 class="fw-bold text-danger">{{ totalEvents }}</h4>
            <small class="text-muted">Total Events</small>
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
 * CitiesChart Component
 *
 * Displays geographic distribution of events in a doughnut chart format.
 * Shows cities with the most events along with revenue data.
 *
 * @component
 * @example
 * <CitiesChart :data="citiesData" :loading="false" />
 */

import { computed } from 'vue';
import { Doughnut } from 'vue-chartjs';

// ========================
// COMPONENT PROPS
// ========================

const props = defineProps({
  /** @type {Object} Cities analytics data with totals and city breakdown */
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
 * Total events across all cities
 * @returns {number} Sum of all events
 */
const totalEvents = computed(() => props.data.total_events || 0);

/**
 * Total revenue across all cities
 * @returns {number} Sum of all revenue
 */
const totalRevenue = computed(() => props.data.total_revenue || 0);

/**
 * Formats data for Chart.js doughnut chart
 * @returns {Object} Chart.js compatible data structure
 */
const chartData = computed(() => ({
  labels: props.data.data?.map(item => item.full_name || 'Unknown') || [],
  datasets: [{
    data: props.data.data?.map(item => item.total_events || 0) || [],
    backgroundColor: props.data.data?.map(item => item.color || '#6c757d') || [],
    borderWidth: 2,
    borderColor: '#fff'
  }]
}));

// ========================
// CHART CONFIGURATION
// ========================

/**
 * Chart.js configuration options optimized for geographic display
 * @type {Object}
 */
const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom',
      labels: {
        padding: 20,
        usePointStyle: true,
        font: {
          size: 11
        },
        /**
         * Custom legend label generator with event counts
         * @param {Object} chart - Chart.js chart instance
         * @returns {Array} Custom legend labels
         */
        generateLabels: function(chart) {
          const data = chart.data;
          if (data.labels?.length && data.datasets?.length) {
            return data.labels.map((label, i) => {
              const value = data.datasets[0].data[i] || 0;
              return {
                text: `${label}: ${value}`,
                fillStyle: data.datasets[0].backgroundColor[i] || '#6c757d',
                hidden: false,
                index: i
              };
            });
          }
          return [];
        }
      }
    },
    tooltip: {
      callbacks: {
        label: function(context) {
          const dataIndex = context.dataIndex;
          const item = props.data.data?.[dataIndex];
          const revenue = item?.total_revenue ? formatCurrency(item.total_revenue) : '0.00';
          return `${context.label}: ${context.parsed} events ($${revenue})`;
        }
      }
    }
  },
  // Performance optimizations
  animation: {
    duration: 750
  },
  interaction: {
    intersect: false
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