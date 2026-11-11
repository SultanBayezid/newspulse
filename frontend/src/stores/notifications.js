import { defineStore } from 'pinia'
import { notifications } from '@/api'

export const useNotificationStore = defineStore('notifications', {
  state: () => ({
    unreadCount: 0,
    items: [],
    loaded: false,
    loading: false,
  }),

  actions: {
    async refreshUnread() {
      if (!localStorage.getItem('newspulse_token')) {
        return
      }

      try {
        const { data } = await notifications.unreadCount()
        this.unreadCount = data.count
      } catch {
        // Ignore polling errors.
      }
    },

    async load(force = false) {
      if (this.loaded && !force) {
        return
      }

      this.loading = true

      try {
        const { data } = await notifications.list({ per_page: 20 })
        this.items = data.data
        this.loaded = true
        await this.refreshUnread()
      } finally {
        this.loading = false
      }
    },

    async markRead(id) {
      await notifications.markRead(id)

      const item = this.items.find((notification) => notification.id === id)

      if (item && !item.read_at) {
        item.read_at = new Date().toISOString()
        this.unreadCount = Math.max(0, this.unreadCount - 1)
      }
    },

    async markAllRead() {
      await notifications.markAllRead()

      this.items.forEach((item) => {
        item.read_at = item.read_at ?? new Date().toISOString()
      })

      this.unreadCount = 0
    },
  },
})
