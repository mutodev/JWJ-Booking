<template>
  <div class="admin-dashboard card shadow-sm h-100">
    <div class="card-header bg-white border-bottom">
      <h5 class="card-title mb-0">
        <i class="bi bi-pie-chart me-2 text-primary"></i>
        Status Distribution
      </h5>
    </div>
    <div class="card-body">
      <!-- Loading State -->
      <div v-if="loading" class="loading-container">
        <div class="spinner-border text-primary" role="status">
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
      <!-- Key Statistics -->
      <div class="dashboard-stats">
        <div class="row">
          <div class="col-6">
            <h4 class="fw-bold text-primary">{{ totalReservations }}</h4>
            <small class="text-muted">Total Reservations</small>
          </div>
          <div class="col-6">
            <h4 class="fw-bold text-success">{{ confirmedPercentage }}%</h4>
            <small class="text-muted">Confirmed</small>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
/**
 * StatusChart Component
 *
 * Displays reservation status distribution in a doughnut chart format.
 * Shows breakdown of reservations by status (new, under review, confirmed, cancelled)
 * with key performance indicators.
 *
 * @component
 * @example
 * <StatusChart :data="statusData" :loading="false" />
 */

import { computed } from 'vue';
import { Doughnut } from 'vue-chartjs';

// ========================
// COMPONENT PROPS
// ========================

const props = defineProps({
  /** @type {Array} Array of status objects with count, label, and color properties */
  data: {
    type: Array,
    default: () => [],
    validator: (data) => Array.isArray(data)
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
 * Calculates total number of reservations across all statuses
 * @returns {number} Sum of all reservation counts
 */
const totalReservations = computed(() => {
  return props.data.reduce((total, item) => total + (item.count || 0), 0);
});

/**
 * Calculates percentage of confirmed reservations
 * @returns {number} Percentage of confirmed reservations (0-100)
 */
const confirmedPercentage = computed(() => {
  const confirmed = props.data.find(item => item.status === 'confirmed');
  return confirmed?.percentage || 0;
});

/**
 * Formats data for Chart.js doughnut chart
 * @returns {Object} Chart.js compatible data structure
 */
const chartData = computed(() => ({
  labels: props.data.map(item => item.label || 'Unknown'),
  datasets: [{
    data: props.data.map(item => item.count || 0),
    backgroundColor: props.data.map(item => item.color || '#6c757d'),
    borderWidth: 2,
    borderColor: '#fff'
  }]
}));

// ========================
// CHART CONFIGURATION
// ========================

/**
 * Chart.js configuration options optimized for status display
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
          size: 12
        }
      }
    },
    tooltip: {
      callbacks: {
        label: function(context) {
          const dataIndex = context.dataIndex;
          const percentage = props.data[dataIndex]?.percentage || 0;
          return `${context.label}: ${context.parsed} (${percentage}%)`;
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
</script>