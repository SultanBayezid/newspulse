import axios from 'axios'

const TOKEN_KEY = 'newspulse_token'

export const client = axios.create({
  baseURL: '/api/v1',
  headers: {
    Accept: 'application/json',
  },
})

export function getToken() {
  return localStorage.getItem(TOKEN_KEY)
}

export function setToken(token) {
  if (token) {
    localStorage.setItem(TOKEN_KEY, token)
  } else {
    localStorage.removeItem(TOKEN_KEY)
  }
}

client.interceptors.request.use((config) => {
  const token = getToken()

  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }

  return config
})

// Auto sign-out on invalid or expired tokens.
client.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      setToken(null)

      if (!['/auth/login', '/auth/register'].includes(error.config?.url)) {
        window.dispatchEvent(new CustomEvent('newspulse:unauthenticated'))
      }
    }

    return Promise.reject(error)
  },
)

/**
 * Extract a human readable error message from an API error response.
 */
export function apiError(error, fallback = 'Something went wrong. Please try again.') {
  const response = error?.response

  if (response?.status === 422 || response?.status === 429) {
    const errors = response.data?.errors ?? {}
    const first = Object.values(errors)[0]

    if (Array.isArray(first) && first.length > 0) {
      return first[0]
    }

    if (typeof response.data?.message === 'string' && response.data.message.length > 0) {
      return response.data.message
    }
  }

  if (typeof response?.data?.message === 'string' && response.data.message.length > 0) {
    return response.data.message
  }

  if (error?.code === 'ERR_NETWORK') {
    return 'Cannot reach the server. Is the backend running?'
  }

  return fallback
}

export default client
