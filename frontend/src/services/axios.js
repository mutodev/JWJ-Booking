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
    ? "https://bookingjamwithjamie.cloud/api"
    : `${window.location.origin}/api`;

const api = axios.create({
  baseURL,
  timeout: 30000,
});

// === Interceptor de solicitud ===
api.interceptors.request.use(
  (config) => {
    // Draft autosaves and the verification modal manage their own loading UI.
    const isSilentRequest = config.url?.includes('/home/draft') || config.url?.includes('/home/contact-verification');
    if (!isSilentRequest) {
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
      const isVerificationRequest = response.config.url?.includes('/home/contact-verification');

      // Guardar token si viene en la respuesta
      if (response.data?.token || response.data?.data) {
        const token = response.data?.token
          ?? response.data?.data?.token
          ?? response.data.data;
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
        !isDraftRequest && !isVerificationRequest &&
        response.config.method?.toUpperCase() !== "GET" &&
        response.data?.message
      ) {
        toast.success(response.data.message);
      }

      // Silent requests did not show the global loader.
      if (!isDraftRequest && !isVerificationRequest) {
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
    const isVerificationRequest = error.config?.url?.includes('/home/contact-verification');

    // Silent requests did not show the global loader.
    if (!isDraftRequest && !isVerificationRequest) {
      hideLoader();
    }

    const errorMessage =
      error.response?.data?.message || error.message || "An error occurred";
    console.error("API Error:", errorMessage);

    // Don't show error toast for draft requests (fail silently)
    if (!isDraftRequest && !isVerificationRequest && error.response?.data?.message) {
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
