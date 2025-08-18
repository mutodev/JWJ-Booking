import { createApp } from "vue";
import App from "./App.vue";
import router from "./src/router";

import "@/assets/styles/custom-bootstrap.scss";
import "bootstrap-icons/font/bootstrap-icons.css";

import Toast from "vue-toastification";
import "vue-toastification/dist/index.css";
import "@/assets/styles/toast-overrides.css";

const app = createApp(App);

app.use(router);
app.use(Toast, {
  timeout: 5000,
});
app.mount("#app");