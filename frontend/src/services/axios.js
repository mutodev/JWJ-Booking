import axios from "axios";
import { useToast } from "vue-toastification";
import { showLoader, hideLoader } from "@/assets/scripts/loader";

const toast = useToast();
const baseURL = `${window.location.origin}/api`;

const api = axios.create({
  baseURL,
  timeout: 30000, // 10 segundos
  headers: {
    "Content-Type": "application/json",
  },
});

// Interceptor de solicitud
api.interceptors.request.use(
  (config) => {
    showLoader();
    const token = sessionStorage.getItem("token");

    if (token) config.headers["Authorization"] = `Bearer ${token}`;

    config.headers["X-Language"] = localStorage.getItem("language") ?? "es";

    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Interceptor de RESPUESTA
api.interceptors.response.use(
  (response) => {
    const token = sessionStorage.getItem("token");
    if (token && response?.data?.token) {
      sessionStorage.setItem("token", response.data.token);
    }
    hideLoader();
    return response?.data ?? response;
  },
  (error) => {
    hideLoader();
    toast.error(error.response.data.message);
    if (error.response) {
      const status = error.response.status;
       if ([401, 403, 419].includes(status)) {
        sessionStorage.removeItem("token");
        router.replace("/login"); 
      }
    }
    return Promise.reject(error);
  }
);

export default api;
