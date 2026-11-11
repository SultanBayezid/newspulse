<script setup>
import { onMounted } from 'vue'
import { useUiStore } from '@/stores/ui'
import { useAuthStore } from '@/stores/auth'
import { useNotificationStore } from '@/stores/notifications'
import ToastHost from '@/components/ui/ToastHost.vue'

const ui = useUiStore()
const auth = useAuthStore()
const notificationStore = useNotificationStore()

onMounted(() => {
  ui.applyTheme()
  ui.loadSiteSettings()
  auth.init()
  notificationStore.refreshUnread()

  window.setInterval(() => notificationStore.refreshUnread(), 60000)

  window.addEventListener('newspulse:unauthenticated', () => {
    auth.user = null
  })
})
</script>

<template>
  <ToastHost />
  <RouterView />
</template>
