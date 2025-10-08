import { ref, watch } from 'vue'
import { useRouter } from 'vue-router'

const isAuthenticated = ref(false)
const token = ref(null)

export function useAuth() {
  const router = useRouter()

  // Función para verificar si hay token válido
  const checkAuth = () => {
    const storedToken = sessionStorage.getItem('token')
    if (storedToken) {
      try {
        // Verificar si el token no ha expirado
        const decoded = JSON.parse(atob(storedToken.split('.')[1]))
        const currentTime = Date.now() / 1000

        if (decoded.exp && decoded.exp < currentTime) {
          // Token expirado
          logout()
          return false
        }

        token.value = storedToken
        isAuthenticated.value = true
        return true
      } catch (error) {
        // Token inválido
        logout()
        return false
      }
    }
    return false
  }

  // Función para login
  const login = (authToken) => {
    sessionStorage.setItem('token', authToken)
    token.value = authToken
    isAuthenticated.value = true

    // Decodificar y guardar datos adicionales del token
    try {
      const decoded = JSON.parse(atob(authToken.split('.')[1]))
      for (const [key, value] of Object.entries(decoded)) {
        if (key !== 'exp' && key !== 'iat') {
          if (typeof value === "object") {
            sessionStorage.setItem(key, JSON.stringify(value))
          } else {
            sessionStorage.setItem(key, String(value))
          }
        }
      }
    } catch (error) {
      console.error('Error decoding token:', error)
    }
  }

  // Función para logout
  const logout = () => {
    sessionStorage.clear()
    token.value = null
    isAuthenticated.value = false
    router.push('/login')
  }

  // Función para esperar a que el token esté disponible
  const waitForToken = () => {
    return new Promise((resolve) => {
      if (checkAuth()) {
        resolve(token.value)
      } else {
        // Observar cambios en sessionStorage
        const interval = setInterval(() => {
          if (checkAuth()) {
            clearInterval(interval)
            resolve(token.value)
          }
        }, 50)

        // Timeout después de 2 segundos
        setTimeout(() => {
          clearInterval(interval)
          resolve(null)
        }, 2000)
      }
    })
  }

  // Inicializar auth al cargar
  checkAuth()

  return {
    isAuthenticated,
    token,
    login,
    logout,
    checkAuth,
    waitForToken
  }
}