<template>
  <div
    v-if="show"
    class="admin-modal modal fade show d-block"
    tabindex="-1"
    role="dialog"
  >
    <div class="modal-dialog modal-xl" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            {{ data.completed ? 'Completed Reservation' : 'Abandoned Cart' }} - Draft Details
          </h5>
          <button
            type="button"
            class="btn-close"
            @click="closeModal"
          ></button>
        </div>

        <div class="modal-body">
          <!-- Status Banner -->
          <div class="alert alert-secondary">
            <div class="row">
              <div class="col-md-8">
                <p class="mb-1">
                  <strong>Status:</strong>
                  <span v-if="data.completed">
                    Completed
                  </span>
                  <span v-else>
                    Abandoned at Step {{ data.current_step }}
                  </span>
                </p>
                <p class="mb-0">
                  <strong>Last Activity:</strong> {{ formatDateTime(data.last_activity_at) }}
                  ({{ getTimeAgo(data.last_activity_at) }})
                </p>
              </div>
              <div class="col-md-4 text-end">
                <button
                  v-if="!data.completed && data.email"
                  class="btn btn-primary btn-sm"
                  @click="contactCustomer"
                >
                  Send Follow-up Email
                </button>
              </div>
            </div>
          </div>

          <!-- Customer Info -->
          <div class="row mb-3">
            <div class="col-md-6">
              <div class="card">
                <div class="card-header">
                  <strong>Customer Information</strong>
                </div>
                <div class="card-body">
                  <p class="mb-2"><strong>Email:</strong> {{ data.email || 'Not provided' }}</p>
                  <p class="mb-2"><strong>Phone:</strong> {{ data.phone || 'Not provided' }}</p>
                  <p class="mb-2"><strong>Session ID:</strong> {{ data.session_id }}</p>
                  <p class="mb-0"><strong>IP Address:</strong> {{ data.ip_address || 'N/A' }}</p>
                </div>
              </div>
            </div>

            <div class="col-md-6">
              <div class="card">
                <div class="card-header">
                  <strong>Draft Information</strong>
                </div>
                <div class="card-body">
                  <p class="mb-2"><strong>Draft ID:</strong> {{ data.id }}</p>
                  <p class="mb-2"><strong>Created:</strong> {{ formatDateTime(data.created_at) }}</p>
                  <p class="mb-2"><strong>Last Step:</strong> Step {{ data.current_step }} of 5</p>
                  <p class="mb-0" v-if="data.reservation_id">
                    <strong>Reservation ID:</strong> {{ data.reservation_id }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Progress -->
          <div class="card mb-3">
            <div class="card-header">
              <strong>Booking Progress</strong>
            </div>
            <div class="card-body">
              <div class="progress" style="height: 30px;">
                <div
                  class="progress-bar"
                  role="progressbar"
                  :style="{ width: getProgressPercentage() + '%' }"
                >
                  {{ getProgressPercentage() }}% Complete (Step {{ data.current_step }} of 5)
                </div>
              </div>
              <div class="mt-2">
                <small>{{ getStepName(data.current_step) }}</small>
              </div>
            </div>
          </div>

          <!-- Form Data -->
          <div class="card">
            <div class="card-header">
              <strong>Form Data Collected</strong>
            </div>
            <div class="card-body">
              <div v-if="parsedFormData && Object.keys(parsedFormData).length > 0">
                <div class="row">
                  <!-- Zip Code -->
                  <div class="col-md-6 mb-3" v-if="parsedFormData.zipcode">
                    <strong>Zip Code:</strong>
                    <p>{{ parsedFormData.zipcode }}</p>
                  </div>

                  <!-- Service -->
                  <div class="col-md-6 mb-3" v-if="parsedFormData.service_price_id">
                    <strong>Service ID:</strong>
                    <p>{{ parsedFormData.service_price_id }}</p>
                  </div>

                  <!-- Children Count -->
                  <div class="col-md-6 mb-3" v-if="parsedFormData.children_count">
                    <strong>Children Count:</strong>
                    <p>{{ parsedFormData.children_count }} children</p>
                  </div>

                  <!-- Children Age Range -->
                  <div class="col-md-6 mb-3" v-if="parsedFormData.children_age_range">
                    <strong>Children Age Range:</strong>
                    <p>{{ parsedFormData.children_age_range }}</p>
                  </div>

                  <!-- Performers Count -->
                  <div class="col-md-6 mb-3" v-if="parsedFormData.performers_count">
                    <strong>Performers:</strong>
                    <p>{{ parsedFormData.performers_count }} performer(s)</p>
                  </div>

                  <!-- Duration -->
                  <div class="col-md-6 mb-3" v-if="parsedFormData.duration_hours">
                    <strong>Duration:</strong>
                    <p>{{ parsedFormData.duration_hours }} hour(s)</p>
                  </div>

                  <!-- Add-ons -->
                  <div class="col-md-12 mb-3" v-if="parsedFormData.addons && parsedFormData.addons.length > 0">
                    <strong>Add-ons Selected:</strong>
                    <ul>
                      <li v-for="addon in parsedFormData.addons" :key="addon.id">
                        {{ addon.name }} - ${{ addon.price }}
                      </li>
                    </ul>
                  </div>

                  <!-- Subtotal -->
                  <div class="col-md-6 mb-3" v-if="parsedFormData.subtotal">
                    <strong>Subtotal:</strong>
                    <p>${{ parsedFormData.subtotal }}</p>
                  </div>

                  <!-- Promo Code -->
                  <div class="col-md-6 mb-3" v-if="parsedFormData.promo_code">
                    <strong>Promo Code:</strong>
                    <p>
                      {{ parsedFormData.promo_code }}
                      <span v-if="parsedFormData.discount">
                        (-${{ parsedFormData.discount }})
                      </span>
                    </p>
                  </div>

                  <!-- Event Details -->
                  <div class="col-md-12 mb-3" v-if="parsedFormData.event_address">
                    <strong>Event Address:</strong>
                    <p>{{ parsedFormData.event_address }}</p>
                  </div>

                  <div class="col-md-6 mb-3" v-if="parsedFormData.event_date">
                    <strong>Event Date:</strong>
                    <p>{{ parsedFormData.event_date }}</p>
                  </div>

                  <div class="col-md-6 mb-3" v-if="parsedFormData.event_time">
                    <strong>Event Time:</strong>
                    <p>{{ parsedFormData.event_time }}</p>
                  </div>

                  <div class="col-md-6 mb-3" v-if="parsedFormData.entertainment_start_time">
                    <strong>Entertainment Start Time:</strong>
                    <p>{{ parsedFormData.entertainment_start_time }}</p>
                  </div>

                  <!-- Birthday Child Info -->
                  <div class="col-md-6 mb-3" v-if="parsedFormData.birthday_child_name">
                    <strong>Birthday Child Name:</strong>
                    <p>{{ parsedFormData.birthday_child_name }}</p>
                  </div>

                  <div class="col-md-6 mb-3" v-if="parsedFormData.birthday_child_age">
                    <strong>Birthday Child Age:</strong>
                    <p>{{ parsedFormData.birthday_child_age }}</p>
                  </div>

                  <div class="col-md-6 mb-3" v-if="parsedFormData.sing_happy_birthday">
                    <strong>Happy Birthday Song:</strong>
                    <p>{{ parsedFormData.sing_happy_birthday === 'yes' ? 'Yes' : 'No' }}</p>
                  </div>

                  <!-- Additional Info -->
                  <div class="col-md-12 mb-3" v-if="parsedFormData.arrival_parking_instructions">
                    <strong>Arrival & Parking Instructions:</strong>
                    <p>{{ parsedFormData.arrival_parking_instructions }}</p>
                  </div>

                  <div class="col-md-12 mb-3" v-if="parsedFormData.song_requests">
                    <strong>Song Requests:</strong>
                    <p>{{ parsedFormData.song_requests }}</p>
                  </div>

                  <div class="col-md-12 mb-3" v-if="parsedFormData.customer_notes">
                    <strong>Customer Notes:</strong>
                    <p>{{ parsedFormData.customer_notes }}</p>
                  </div>
                </div>
              </div>
              <div v-else>
                <p>No form data collected yet</p>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="closeModal">
            <i class="bi bi-x-circle"></i> Close
          </button>
        </div>
      </div>
    </div>
    <div class="modal-backdrop fade show"></div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const emit = defineEmits(["close"]);
const props = defineProps({
  show: Boolean,
  data: {
    type: Object,
    default: () => ({}),
  },
});

const closeModal = () => {
  emit("close");
};

const contactCustomer = () => {
  const subject = encodeURIComponent("Complete Your Reservation - Jam With Jamie");
  const body = encodeURIComponent(
    `Hi ${props.data.email || 'Customer'},\n\nWe noticed you started a reservation but didn't complete it. We'd love to help you finish your booking!\n\nBest regards,\nJam With Jamie Team`
  );
  window.open(`mailto:${props.data.email}?subject=${subject}&body=${body}`);
};

const parsedFormData = computed(() => {
  if (!props.data.form_data) return {};
  try {
    if (typeof props.data.form_data === 'string') {
      return JSON.parse(props.data.form_data);
    }
    return props.data.form_data;
  } catch (e) {
    console.error('Error parsing form data:', e);
    return {};
  }
});

const formatDateTime = (datetime) => {
  if (!datetime) return "N/A";
  const d = new Date(datetime);
  return d.toLocaleString("en-US", {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  });
};

const getTimeAgo = (datetime) => {
  if (!datetime) return "";
  const now = new Date();
  const past = new Date(datetime);
  const diffMs = now - past;
  const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
  const diffDays = Math.floor(diffHours / 24);

  if (diffDays > 0) {
    return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
  } else if (diffHours > 0) {
    return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
  } else {
    const diffMins = Math.floor(diffMs / (1000 * 60));
    return `${diffMins} min${diffMins > 1 ? 's' : ''} ago`;
  }
};

const getProgressPercentage = () => {
  return (props.data.current_step / 5) * 100;
};

const getStepName = (step) => {
  const steps = {
    1: 'Customer stopped at: Contact Information',
    2: 'Customer stopped at: Service Selection',
    3: 'Customer stopped at: Add-ons Selection',
    4: 'Customer stopped at: Review Subtotal',
    5: 'Customer stopped at: Event Details'
  };
  return steps[step] || 'Unknown step';
};
</script>

<style scoped>
</style>
