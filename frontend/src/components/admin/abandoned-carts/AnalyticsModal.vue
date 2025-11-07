<template>
  <div
    v-if="show"
    class="admin-modal modal fade show d-block"
    tabindex="-1"
    role="dialog"
  >
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header bg-info text-white">
          <h5 class="modal-title">
            <i class="bi bi-graph-up"></i>
            Abandoned Cart Analytics
          </h5>
          <button
            type="button"
            class="btn-close btn-close-white"
            @click="closeModal"
          ></button>
        </div>

        <div class="modal-body">
          <!-- Overview Stats -->
          <div class="row mb-4">
            <div class="col-12">
              <h6 class="text-muted">OVERVIEW STATISTICS</h6>
              <hr>
            </div>
            <div class="col-md-6">
              <div class="stat-item">
                <div class="stat-label">Total Drafts Created</div>
                <div class="stat-value text-primary">{{ stats.total_drafts || 0 }}</div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="stat-item">
                <div class="stat-label">Completed Reservations</div>
                <div class="stat-value text-success">{{ stats.completed || 0 }}</div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="stat-item">
                <div class="stat-label">Abandoned Carts</div>
                <div class="stat-value text-danger">{{ stats.abandoned || 0 }}</div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="stat-item">
                <div class="stat-label">Last 24 Hours</div>
                <div class="stat-value text-warning">{{ stats.last_24h || 0 }}</div>
              </div>
            </div>
          </div>

          <!-- Conversion Rates -->
          <div class="row mb-4">
            <div class="col-12">
              <h6 class="text-muted">CONVERSION RATES</h6>
              <hr>
            </div>
            <div class="col-md-6">
              <label class="small text-muted">Completion Rate</label>
              <div class="progress" style="height: 25px;">
                <div
                  class="progress-bar bg-success"
                  role="progressbar"
                  :style="{ width: (stats.completion_rate || 0) + '%' }"
                >
                  {{ stats.completion_rate || 0 }}%
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <label class="small text-muted">Abandon Rate</label>
              <div class="progress" style="height: 25px;">
                <div
                  class="progress-bar bg-danger"
                  role="progressbar"
                  :style="{ width: (stats.abandon_rate || 0) + '%' }"
                >
                  {{ stats.abandon_rate || 0 }}%
                </div>
              </div>
            </div>
          </div>

          <!-- Recommendations -->
          <div class="card bg-light">
            <div class="card-body">
              <h6 class="card-title">
                <i class="bi bi-lightbulb text-warning"></i>
                Recommendations
              </h6>
              <ul class="mb-0">
                <li v-if="stats.abandon_rate > 50">
                  <strong>High abandon rate detected!</strong> Consider sending follow-up emails to customers who abandoned their carts.
                </li>
                <li v-if="stats.last_24h > 10">
                  <strong>{{ stats.last_24h }} carts abandoned in the last 24 hours.</strong> Act quickly to recover these potential sales.
                </li>
                <li v-if="stats.completion_rate < 30">
                  <strong>Low completion rate.</strong> Review the booking process to identify and remove friction points.
                </li>
                <li v-if="stats.completion_rate >= 70">
                  <strong>Great job!</strong> Your completion rate is healthy. Keep monitoring to maintain this performance.
                </li>
              </ul>
            </div>
          </div>

          <!-- Tips -->
          <div class="alert alert-info mt-3">
            <h6><i class="bi bi-info-circle"></i> Recovery Tips:</h6>
            <ol class="mb-0">
              <li>Send personalized follow-up emails within 24 hours</li>
              <li>Offer a limited-time discount to encourage completion</li>
              <li>Use phone follow-ups for high-value abandoned carts</li>
              <li>Analyze common drop-off points to improve the process</li>
            </ol>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-primary" @click="closeModal">
            <i class="bi bi-check-circle"></i> Close
          </button>
        </div>
      </div>
    </div>
    <div class="modal-backdrop fade show"></div>
  </div>
</template>

<script setup>
const emit = defineEmits(["close"]);
const props = defineProps({
  show: Boolean,
  stats: {
    type: Object,
    default: () => ({}),
  },
});

const closeModal = () => {
  emit("close");
};
</script>

<style scoped>
.stat-item {
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 8px;
  margin-bottom: 1rem;
}

.stat-label {
  font-size: 0.85rem;
  color: #6c757d;
  margin-bottom: 0.5rem;
}

.stat-value {
  font-size: 2rem;
  font-weight: bold;
}
</style>
