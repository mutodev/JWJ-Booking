<template>
  <nav
    class="navbar navbar-expand-lg bg-body-tertiary border-bottom sticky-top w-100"
  >
    <div class="container-fluid">
      <RouterLink to="/" class="navbar-brand d-flex align-items-center gap-2">
        <span class="badge text-bg-primary">JWJ</span>
        <!-- <span>JWJ</span> -->
      </RouterLink>
      <button
        class="navbar-toggler"
        type="button"
        data-bs-toggle="collapse"
        data-bs-target="#mainNav"
        aria-controls="mainNav"
        aria-expanded="false"
        aria-label="Toggle navigation"
      >
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="mainNav">
        <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
          <!-- <li class="nav-item">
            <a class="nav-link active" aria-current="page" href="#inicio"
              >Home</a
            >
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#features">Features</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#contacto">Contact</a>
          </li> -->
        </ul>
        <div class="d-flex ms-lg-3">
          <RouterLink to="/login" class="btn btn-primary">
            Sign In
          </RouterLink>
        </div>
      </div>
    </div>
  </nav>
</template>

<script setup></script>
