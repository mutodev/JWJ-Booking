<template>
  <div class="admin-dashboard card shadow-sm">
    <div class="card-header bg-white border-bottom">
      <div class="chart-header">
        <h5 class="card-title mb-0">
          <i class="bi bi-graph-up me-2 text-info"></i>
          Status Evolution (Last 6 Months)
        </h5>
        <button
          @click="$emit('refresh')"
          class="btn btn-sm btn-outline-primary refresh-button"
          :disabled="loading"
          :aria-label="refreshButtonLabel"
        >
          <i class="bi bi-arrow-clockwise me-1"></i>
          Refresh
        </button>
      </div>
    </div>
    <div class="card-body">
      <!-- Loading State -->
      <div v-if="loading" class="loading-container">
        <div class="spinner-border text-info" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
      </div>
      <!-- Chart Display -->
      <div v-else class="chart-container">
        <Line
          :data="chartData"
          :options="chartOptions"
          ref="chartRef"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
/**
 * EvolutionChart Component
 *
 * Displays reservation status evolution over time in a line chart format.
 * Shows trends for different status types across the last 6 months with refresh capability.
 *
 * @component
 * @example
 * <EvolutionChart :data="evolutionData" :loading="false" @refresh="handleRefresh" />
 */

import { computed } from 'vue';
import { Line } from 'vue-chartjs';

// ========================
// COMPONENT PROPS & EMITS
// ========================

const props = defineProps({
  /** @type {Object} Evolution data with months and series arrays */
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

const emit = defineEmits([
  /** Emitted when user clicks refresh button */
  'refresh'
]);

// ========================
// COMPUTED PROPERTIES
// ========================

/**
 * Formats data for Chart.js line chart
 * @returns {Object} Chart.js compatible data structure
 */
const chartData = computed(() => ({
  labels: props.data.months || [],
  datasets: Array.isArray(props.data.series) ? props.data.series : []
}));

/**
 * Accessibility label for refresh button
 * @returns {string} ARIA label text
 */
const refreshButtonLabel = computed(() =>
  props.loading ? 'Refreshing data...' : 'Refresh evolution data'
);

// ========================
// CHART CONFIGURATION
// ========================

/**
 * Chart.js configuration options optimized for time series display
 * @type {Object}
 */
const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'top',
      labels: {
        usePointStyle: true,
        padding: 20
      }
    },
    tooltip: {
      mode: 'index',
      intersect: false,
      backgroundColor: 'rgba(0,0,0,0.8)',
      titleColor: '#fff',
      bodyColor: '#fff'
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
  interaction: {
    mode: 'nearest',
    axis: 'x',
    intersect: false
  },
  // Performance optimizations
  animation: {
    duration: 750
  },
  elements: {
    point: {
      radius: 4,
      hoverRadius: 6
    },
    line: {
      tension: 0.1
    }
  }
};
</script>