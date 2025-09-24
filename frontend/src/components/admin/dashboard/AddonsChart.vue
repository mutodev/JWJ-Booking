<template>
  <div class="card shadow-sm h-100">
    <div class="card-header bg-white border-bottom">
      <h5 class="card-title mb-0">
        <i class="bi bi-puzzle me-2 text-purple"></i>
        Most Popular Add-ons
      </h5>
    </div>
    <div class="card-body">
      <!-- Loading State -->
      <div v-if="loading" class="loading-container">
        <div class="spinner-border text-purple" role="status">
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
      <!-- Addon Statistics -->
      <div class="dashboard-stats">
        <div class="row">
          <div class="col-6">
            <h4 class="fw-bold text-purple">{{ totalAddonsSold }}</h4>
            <small class="text-muted">Total Sold</small>
          </div>
          <div class="col-6">
            <h4 class="fw-bold text-success">${{ formatCurrency(totalAddonRevenue) }}</h4>
            <small class="text-muted">Revenue</small>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
/**
 * AddonsChart Component
 *
 * Displays add-on popularity analytics in a doughnut chart format.
 * Shows most popular add-ons with sales counts and revenue data.
 *
 * @component
 * @example
 * <AddonsChart :data="addonsData" :loading="false" />
 */

import { computed } from 'vue';
import { Doughnut } from 'vue-chartjs';

// ========================
// COMPONENT PROPS
// ========================

const props = defineProps({
  /** @type {Object} Addons analytics data with totals and addon breakdown */
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
 * Total add-ons sold across all types
 * @returns {number} Sum of all add-ons sold
 */
const totalAddonsSold = computed(() => props.data.total_addons_sold || 0);

/**
 * Total revenue from add-ons
 * @returns {number} Sum of all add-on revenue
 */
const totalAddonRevenue = computed(() => props.data.total_addon_revenue || 0);

/**
 * Formats data for Chart.js doughnut chart
 * @returns {Object} Chart.js compatible data structure
 */
const chartData = computed(() => ({
  labels: props.data.data?.map(item => item.addon_name || 'Unknown') || [],
  datasets: [{
    data: props.data.data?.map(item => item.total_sold || 0) || [],
    backgroundColor: props.data.data?.map(item => item.color || '#6c757d') || [],
    borderWidth: 2,
    borderColor: '#fff'
  }]
}));

// ========================
// CHART CONFIGURATION
// ========================

/**
 * Chart.js configuration options optimized for add-on display
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
         * Custom legend label generator with sales counts
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
          const reservations = item?.reservations_count || 0;
          return [
            `${context.label}: ${context.parsed} sold`,
            `Revenue: $${revenue}`,
            `In ${reservations} reservations`
          ];
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

<style scoped>
.text-purple {
  color: #6f42c1 !important;
}

.loading-container {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 200px;
}

.chart-container {
  position: relative;
  height: 300px;
  margin-bottom: 1rem;
}

.dashboard-stats {
  padding-top: 1rem;
  border-top: 1px solid #e9ecef;
  margin-top: 1rem;
}

.dashboard-stats h4 {
  font-size: 1.5rem;
  margin-bottom: 0.25rem;
}

.dashboard-stats small {
  font-size: 0.875rem;
}
</style>