import axios from "axios";
import { useToast } from "vue-toastification";
import { showLoader, hideLoader } from "@/assets/scripts/loader";
import { jwtDecode } from "jwt-decode";
import router from "@/router";

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

    console.log(`🔍 API Request to: ${config.url}`);
    console.log(`🔑 Token available: ${!!token}`);

    if (token) {
      config.headers["Authorization"] = `Bearer ${token}`;
      console.log(`✅ Token attached: ${token.substring(0, 20)}...`);
    } else {
      console.warn("❌ No token found in sessionStorage for request");
      console.log("📦 SessionStorage contents:", Object.keys(sessionStorage));
    }

    config.headers["X-Language"] = localStorage.getItem("language") ?? "es";
    return config;
  },
  (error) => {
    hideLoader();
    return Promise.reject(error);
  }
);

// Interceptor de RESPUESTA
api.interceptors.response.use(
  (response) => {
    try {
      // Verificar si hay token en la respuesta
      if (response.data?.token || response.data?.data) {
        const token = response.data?.token ?? response.data.data;

        // Validar que el token sea una string válida
        if (typeof token === 'string' && token.length > 0) {
          try {
            const decoded = jwtDecode(token);

            // Guardar el token
            sessionStorage.setItem("token", token);
            console.log("Token saved to sessionStorage:", token.substring(0, 20) + "...");

            // Decodificar y guardar datos del token
            for (const [key, value] of Object.entries(decoded)) {
              if (key !== 'exp' && key !== 'iat') { // Excluir campos de tiempo
                if (typeof value === "object") {
                  sessionStorage.setItem(key, JSON.stringify(value));
                } else {
                  sessionStorage.setItem(key, String(value));
                }
              }
            }
          } catch (jwtError) {
            console.error("Error decoding JWT:", jwtError);
          }
        }
      }

      // Solo mostrar toast para métodos que no sean GET
      if (response.config.method?.toUpperCase() !== "GET" && response.data?.message) {
        toast.success(response.data.message);
      }

      hideLoader();
      return response?.data ?? response;
    } catch (error) {
      console.error("Response interceptor error:", error);

      // Si hay error pero la respuesta fue exitosa, aún mostrar toast
      if ([200, 201].includes(response.status) &&
          response.config.method?.toUpperCase() !== "GET" &&
          response.data?.message) {
        toast.success(response.data.message);
      }

      hideLoader();
      return response?.data ?? response;
    }
  },
  (error) => {
    hideLoader();

    // Manejo más robusto de errores
    const errorMessage = error.response?.data?.message || error.message || "An error occurred";
    console.error("API Error:", errorMessage);

    // Solo mostrar toast si hay un mensaje específico del servidor
    if (error.response?.data?.message) {
      toast.error(error.response.data.message);
    }

    // Manejo de errores de autenticación
    if (error.response) {
      const status = error.response.status;
      if ([401, 403, 419].includes(status)) {
        console.log("Authentication error, clearing session and redirecting to login");
        sessionStorage.clear();
        router.replace("/login").catch(err => console.error("Redirect error:", err));
      }
    }

    return Promise.reject(error);
  }
);

export default api;
