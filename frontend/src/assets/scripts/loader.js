// src/assets/scripts/loader.js

function createLoader() {
  const loader = document.createElement("div");
  loader.id = "global-loader";
  loader.className =
    "position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center bg-white bg-opacity-75 d-none";
  loader.style.zIndex = "2000";
  loader.innerHTML = `
    <div class="spinner-border text-primary" role="status" style="width:3rem; height:3rem;">
      <span class="visually-hidden">Cargando...</span>
    </div>
  `;
  document.body.appendChild(loader);
}

function showLoader() {
  const loader = document.getElementById("global-loader");
  if (loader) loader.classList.remove("d-none");
}

function hideLoader() {
  const loader = document.getElementById("global-loader");
  if (loader) loader.classList.add("d-none");
}

export { createLoader, showLoader, hideLoader };
