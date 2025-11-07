import axios from "axios";
import { useToast } from "vue-toastification";
import { showLoader, hideLoader } from "@/assets/scripts/loader";
import { jwtDecode } from "jwt-decode";
import router from "@/router";

const toast = useToast();

// 🔹 Detectar entorno desde variable global inyectada por CodeIgniter
const appEnv = window.APP_ENV || "production";

// 🔹 Definir baseURL según entorno
const baseURL =
  appEnv === "production"
    ? "http://72.60.169.233/api"
    : `${window.location.origin}/api`;

const api = axios.create({
  baseURL,
  timeout: 30000,
  headers: {
    "Content-Type": "application/json",
  },
});

// === Interceptor de solicitud ===
api.interceptors.request.use(
  (config) => {
    // Don't show loader for draft requests (silent background operation)
    const isDraftRequest = config.url?.includes('/home/draft');
    if (!isDraftRequest) {
      showLoader();
    }

    const token = localStorage.getItem("token");
    if (token) config.headers["Authorization"] = `Bearer ${token}`;
    config.headers["X-Language"] = localStorage.getItem("language") ?? "es";
    return config;
  },
  (error) => Promise.reject(error)
);

// === Interceptor de respuesta ===
api.interceptors.response.use(
  (response) => {
    try {
      const isDraftRequest = response.config.url?.includes('/home/draft');

      // Guardar token si viene en la respuesta
      if (response.data?.token || response.data?.data) {
        const token = response.data?.token ?? response.data.data;
        if (typeof token === "string" && token.length > 0) {
          try {
            const decoded = jwtDecode(token);
            localStorage.setItem("token", token);

            // Guardar claims del token (excepto tiempos)
            for (const [key, value] of Object.entries(decoded)) {
              if (key !== "exp" && key !== "iat") {
                localStorage.setItem(
                  key,
                  typeof value === "object" ? JSON.stringify(value) : String(value)
                );
              }
            }
          } catch (jwtError) {
            console.error("Error decoding JWT:", jwtError);
          }
        }
      }

      // Mostrar mensajes de éxito solo para métodos != GET y NO para drafts
      if (
        !isDraftRequest &&
        response.config.method?.toUpperCase() !== "GET" &&
        response.data?.message
      ) {
        toast.success(response.data.message);
      }

      // Don't hide loader for draft requests (it was never shown)
      if (!isDraftRequest) {
        hideLoader();
      }

      return response?.data ?? response;
    } catch (error) {
      console.error("Response interceptor error:", error);
      hideLoader();
      return response?.data ?? response;
    }
  },
  (error) => {
    const isDraftRequest = error.config?.url?.includes('/home/draft');

    // Don't hide loader for draft requests (it was never shown)
    if (!isDraftRequest) {
      hideLoader();
    }

    const errorMessage =
      error.response?.data?.message || error.message || "An error occurred";
    console.error("API Error:", errorMessage);

    // Don't show error toast for draft requests (fail silently)
    if (!isDraftRequest && error.response?.data?.message) {
      toast.error(error.response.data.message);
    }

    // Manejar errores de autenticación
    if (error.response) {
      const status = error.response.status;
      if ([401, 403, 419].includes(status)) {
        localStorage.clear();
        router.replace("/login").catch((err) =>
          console.error("Redirect error:", err)
        );
      }
    }

    return Promise.reject(error);
  }
);

export default api;
