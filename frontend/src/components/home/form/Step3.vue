<template>
  <div class="container py-4">
    <!-- Título -->
    <div class="d-flex align-items-center mb-4">
      <i class="bi bi-music-note-beamed fs-3 me-2 text-success"></i>
      <h2 class="mb-0">Choose your jam</h2>
    </div>

    <!-- Grid de tarjetas -->
    <div class="row g-4">
      <div
        v-for="(jam, index) in jams"
        :key="index"
        class="col-12 col-sm-6 col-lg-4"
      >
        <div class="card h-100 shadow-sm border-0">
          <img :src="jam.image" class="card-img-top" :alt="jam.title" />
          <div class="card-body">
            <h5 class="card-title">{{ jam.title }}</h5>
            <p class="card-text text-muted">{{ jam.subtitle }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const jams = [
  {
    title: "Classic Jam",
    subtitle: "For Ages 0-4",
    image: "https://via.placeholder.com/300x200?text=Classic+Jam",
  },
  {
    title: "Classic Jam / Junior Jammer Medley",
    subtitle: "Customized Option For Ages 0-6",
    image: "https://via.placeholder.com/300x200?text=Medley",
  },
  {
    title: "Junior Jammer Mashup",
    subtitle: "For Ages 5-6",
    image: "https://via.placeholder.com/300x200?text=Jammer+Mashup",
  },
  {
    title: "Eras Jam, Taylor Swift Inspired Set",
    subtitle: "For Ages 6-10",
    image: "https://via.placeholder.com/300x200?text=Eras+Jam",
  },
  {
    title: "Big Kids Party",
    subtitle: "For Ages 7-10",
    image: "https://via.placeholder.com/300x200?text=Big+Kids",
  },
];
</script>

<style scoped>
.card {
  border-radius: 12px;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.card:hover {
  transform: translateY(-5px);
  box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}
</style>
