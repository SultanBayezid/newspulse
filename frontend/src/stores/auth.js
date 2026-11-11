import { defineStore } from 'pinia'
import { auth } from '@/api'
import { setToken, getToken, apiError } from '@/api/client'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    ready: false,
  }),

  getters: {
    isAuthenticated: (state) => state.user !== null,
    isStaff: (state) => ['super_admin', 'editor', 'author'].includes(state.user?.role),
    isEditorial: (state) => ['super_admin', 'editor'].includes(state.user?.role),
    isAdmin: (state) => state.user?.role === 'super_admin',
    can: (state) => (ability) => {
      const abilities = state.user?.abilities ?? []

      return abilities.includes('*') || abilities.includes(ability)
    },
  },

  actions: {
    async init() {
      if (!getToken()) {
        this.user = null
        this.ready = true

        return
      }

      try {
        const { data } = await auth.me()
        this.user = data.data
      } catch {
        this.user = null
        setToken(null)
      } finally {
        this.ready = true
      }
    },

    async login(payload) {
      const { data } = await auth.login(payload)

      setToken(data.token)
      this.user = data.user

      return data.user
    },

    async register(payload) {
      const { data } = await auth.register(payload)

      setToken(data.token)
      this.user = data.user

      return data.user
    },

    async logout() {
      try {
        await auth.logout()
      } catch {
        // The token is dropped locally regardless.
      } finally {
        setToken(null)
        this.user = null
      }
    },

    async refresh() {
      const { data } = await auth.me()
      this.user = data.data
    },

    async updateProfile(payload) {
      const { data } = await auth.updateProfile(payload)
      this.user = data.data
    },
  },
})

export { apiError }
