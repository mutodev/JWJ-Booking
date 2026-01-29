<template>
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-8 col-lg-6 text-center">
        <div class="cancel-card">
          <div class="cancel-icon mb-4">
            <i class="bi bi-x-circle-fill"></i>
          </div>
          <h1 class="mb-3">Payment Cancelled</h1>
          <p class="text-muted mb-4">
            Your payment was not completed. If you'd like to try again, please check your email for the payment link or contact us for assistance.
          </p>
          <div class="d-grid gap-2">
            <a href="/" class="btn btn-primary btn-lg">
              Back to Home
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.cancel-card {
  background: white;
  border-radius: 16px;
  padding: 3rem 2rem;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
}

.cancel-icon i {
  font-size: 5rem;
  color: #ef4444;
}

h1 {
  font-size: 1.8rem;
  font-weight: 700;
  color: #1f2937;
}

.btn-primary {
  background-color: #FF74B7;
  border-color: #FF74B7;
  border-radius: 50px;
  padding: 0.75rem 2rem;
  font-weight: 600;
}

.btn-primary:hover {
  background-color: #e662a5;
  border-color: #e662a5;
}
</style>
