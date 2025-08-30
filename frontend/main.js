import { createApp } from "vue";
import App from "./App.vue";
import router from "./src/router";

import "@/assets/styles/custom-bootstrap.scss";
import "bootstrap-icons/font/bootstrap-icons.css";
import 'bootstrap/dist/js/bootstrap.bundle.min.js'

import Toast from "vue-toastification";
import "vue-toastification/dist/index.css";
import "@/assets/styles/toast-overrides.css";

import { createLoader } from "@/assets/scripts/loader";

const app = createApp(App);

createLoader();

app.use(router);
app.use(Toast, {
  timeout: 3500,
});
app.mount("#app");