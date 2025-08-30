import axios from "axios";
import { useToast } from "vue-toastification";
import { showLoader, hideLoader } from "@/assets/scripts/loader";
import { jwtDecode } from "jwt-decode";

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
    if (response.data?.token || response.data?.data) {
      const token = response.data?.token ?? response.data.data;
      sessionStorage.setItem("token", token);
      const decoded = jwtDecode(token);

      // Decodificar el token y guardar los datos en sessionStorage
      for (const [key, value] of Object.entries(decoded)) {
        if (typeof value === "object") {
          sessionStorage.setItem(key, JSON.stringify(value));
        } else {
          sessionStorage.setItem(key, String(value));
        }
      }
    }

    if (response.config.method?.toLocaleUpperCase() !== "GET")
      toast.success(response.data.message ?? "Success");

    setTimeout(() => {
      hideLoader();
    }, 300);
    return response?.data ?? response;
  },
  (error) => {
    hideLoader();
    toast.error(error.response.data.message);
    if (error.response) {
      const status = error.response.status;
      if ([401, 403, 419].includes(status)) {
        sessionStorage.clear();
        router.replace("/login");
      }
    }
    return Promise.reject(error);
  }
);

export default api;
