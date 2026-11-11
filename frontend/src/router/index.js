import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import HomeView from '@/views/public/HomeView.vue'

const routes = [
  { path: '/', name: 'home', component: HomeView },
  {
    path: '/category/:slug',
    name: 'category',
    component: () => import('@/views/public/CategoryView.vue'),
  },
  {
    path: '/article/:slug',
    name: 'article',
    component: () => import('@/views/public/ArticleView.vue'),
  },
  {
    path: '/tag/:slug',
    name: 'tag',
    component: () => import('@/views/public/TagView.vue'),
  },
  {
    path: '/author/:id',
    name: 'author',
    component: () => import('@/views/public/AuthorView.vue'),
  },
  {
    path: '/search',
    name: 'search',
    component: () => import('@/views/public/SearchView.vue'),
  },
  {
    path: '/page/:slug',
    name: 'page',
    component: () => import('@/views/public/PageView.vue'),
  },
  {
    path: '/newsletter/confirm/:token',
    name: 'newsletter-confirm',
    component: () => import('@/views/public/NewsletterConfirmView.vue'),
  },
  {
    path: '/newsletter/unsubscribe/:token',
    name: 'newsletter-unsubscribe',
    component: () => import('@/views/public/NewsletterUnsubscribeView.vue'),
  },

  /* ------------------------------ auth ------------------------------ */

  { path: '/login', name: 'login', component: () => import('@/views/auth/LoginView.vue'), meta: { guest: true } },
  { path: '/register', name: 'register', component: () => import('@/views/auth/RegisterView.vue'), meta: { guest: true } },
  {
    path: '/forgot-password',
    name: 'forgot-password',
    component: () => import('@/views/auth/ForgotPasswordView.vue'),
    meta: { guest: true },
  },
  {
    path: '/reset-password',
    name: 'reset-password',
    component: () => import('@/views/auth/ResetPasswordView.vue'),
    meta: { guest: true },
  },
  {
    path: '/verify-email',
    name: 'verify-email',
    component: () => import('@/views/auth/VerifyEmailView.vue'),
  },

  /* ---------------------------- account ----------------------------- */

  {
    path: '/bookmarks',
    name: 'bookmarks',
    component: () => import('@/views/account/BookmarksView.vue'),
    meta: { auth: true },
  },
  {
    path: '/profile',
    name: 'profile',
    component: () => import('@/views/account/ProfileView.vue'),
    meta: { auth: true },
  },
  {
    path: '/notifications',
    name: 'notifications',
    component: () => import('@/views/account/NotificationsView.vue'),
    meta: { auth: true },
  },

  /* ----------------------------- admin ------------------------------ */

  {
    path: '/admin',
    component: () => import('@/components/admin/AdminShell.vue'),
    meta: { staff: true },
    children: [
      { path: '', name: 'admin-dashboard', component: () => import('@/views/admin/DashboardView.vue') },
      { path: 'articles', name: 'admin-articles', component: () => import('@/views/admin/ArticlesView.vue') },
      { path: 'articles/new', name: 'admin-article-new', component: () => import('@/views/admin/ArticleEditorView.vue') },
      { path: 'articles/:id/edit', name: 'admin-article-edit', component: () => import('@/views/admin/ArticleEditorView.vue') },
      { path: 'categories', name: 'admin-categories', component: () => import('@/views/admin/CategoriesView.vue') },
      { path: 'tags', name: 'admin-tags', component: () => import('@/views/admin/TagsView.vue') },
      { path: 'media', name: 'admin-media', component: () => import('@/views/admin/MediaView.vue') },
      { path: 'comments', name: 'admin-comments', component: () => import('@/views/admin/CommentsView.vue') },
      { path: 'pages', name: 'admin-pages', component: () => import('@/views/admin/PagesView.vue') },
      { path: 'polls', name: 'admin-polls', component: () => import('@/views/admin/PollsView.vue') },
      { path: 'users', name: 'admin-users', component: () => import('@/views/admin/UsersView.vue'), meta: { adminOnly: true } },
      { path: 'subscribers', name: 'admin-subscribers', component: () => import('@/views/admin/SubscribersView.vue'), meta: { editorialOnly: true } },
      { path: 'settings', name: 'admin-settings', component: () => import('@/views/admin/SettingsView.vue'), meta: { adminOnly: true } },
      { path: 'activity', name: 'admin-activity', component: () => import('@/views/admin/ActivityView.vue'), meta: { editorialOnly: true } },
    ],
  },

  { path: '/:pathMatch(.*)*', name: 'not-found', component: () => import('@/views/public/NotFoundView.vue') },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to, from, savedPosition) {
    return savedPosition ?? { top: 0 }
  },
})

router.beforeEach(async (to) => {
  const authStore = useAuthStore()

  if (!authStore.ready) {
    await authStore.init()
  }

  if (to.meta.auth && !authStore.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.meta.guest && authStore.isAuthenticated) {
    return { name: 'home' }
  }

  if (to.meta.staff && !authStore.isStaff) {
    return { name: 'home' }
  }

  if (to.meta.adminOnly && !authStore.isAdmin) {
    return { name: 'admin-dashboard' }
  }

  if (to.meta.editorialOnly && !authStore.isEditorial) {
    return { name: 'admin-dashboard' }
  }

  return true
})

export default router
