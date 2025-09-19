import { createApp } from "vue";
import App from "./App.vue";
import router from "./src/router";

import "@/assets/styles/custom-bootstrap.scss";
import "bootstrap-icons/font/bootstrap-icons.css";
import 'bootstrap/dist/js/bootstrap.bundle.min.js'

import Toast from "vue-toastification";
import "vue-toastification/dist/index.css";
import "@/assets/styles/toast-overrides.css";

import "vue3-easy-data-table/dist/style.css";

import { createLoader } from "@/assets/scripts/loader";
import EasyDataTable from "vue3-easy-data-table";

import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.css";

import ElementPlus from 'element-plus';
import 'element-plus/dist/index.css';

// Importar el helper de tablas
import { generateTableHeaders, generateSearchFields, commonLabels } from "@/assets/scripts/easy-table.js";

const app = createApp(App);

createLoader();

app.component("EasyDataTable", EasyDataTable);
app.component("Multiselect", Multiselect);

// Registrar el helper globalmente usando provide
app.provide('tableHelpers', {
  generateTableHeaders,
  generateSearchFields,
  commonLabels
});

app.use(router);
app.use(ElementPlus);
app.use(Toast, {
  timeout: 3500,
});

app.mount("#app");