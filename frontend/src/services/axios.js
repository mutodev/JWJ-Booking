import axios from "axios";
import { useToast } from "vue-toastification";

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
    if (token) sessionStorage.setItem("token", response?.data.token);

    return response?.data ?? response;
  },
  (error) => {
    toast.error(error.response.data.message);
    return Promise.reject(error);
  }
);

export default api;
