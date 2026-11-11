import client from './client'

/* ------------------------------- auth ------------------------------- */

export const auth = {
  register: (payload) => client.post('/auth/register', payload),
  login: (payload) => client.post('/auth/login', payload),
  logout: () => client.post('/auth/logout'),
  me: () => client.get('/auth/me'),
  updateProfile: (payload) => client.put('/auth/profile', payload),
  forgotPassword: (payload) => client.post('/auth/forgot-password', payload),
  resetPassword: (payload) => client.post('/auth/reset-password', payload),
  sendVerification: () => client.post('/auth/email/verification-notification'),
  verifyEmail: (payload) => client.post('/auth/email/verify', payload),
}

/* ----------------------------- content ------------------------------ */

export const articles = {
  list: (params) => client.get('/articles', { params }),
  trending: (limit = 6) => client.get('/articles/trending', { params: { limit } }),
  breaking: () => client.get('/articles/breaking'),
  show: (slug) => client.get(`/articles/${slug}`),
}

export const categories = {
  list: (params) => client.get('/categories', { params }),
  show: (slug) => client.get(`/categories/${slug}`),
}

export const tags = {
  list: (params) => client.get('/tags', { params }),
  show: (slug) => client.get(`/tags/${slug}`),
}

export const authors = {
  show: (id, params) => client.get(`/authors/${id}`, { params }),
}

export const pages = {
  list: () => client.get('/pages'),
  show: (slug) => client.get(`/pages/${slug}`),
}

export const polls = {
  active: () => client.get('/polls/active'),
  vote: (id, optionId) => client.post(`/polls/${id}/vote`, { poll_option_id: optionId }),
}

export const settings = {
  public: () => client.get('/settings'),
}

export const newsletter = {
  subscribe: (payload) => client.post('/newsletter/subscribe', payload),
  confirm: (token) => client.post(`/newsletter/confirm/${token}`),
  unsubscribe: (token) => client.post(`/newsletter/unsubscribe/${token}`),
}

/* ---------------------------- engagement ---------------------------- */

export const comments = {
  list: (articleId) => client.get(`/articles/${articleId}/comments`),
  store: (payload) => client.post('/comments', payload),
}

export const reactions = {
  toggle: (type, id) => client.post('/reactions/toggle', { type, id }),
}

export const bookmarks = {
  list: (params) => client.get('/bookmarks', { params }),
  toggle: (articleId) => client.post('/bookmarks/toggle', { article_id: articleId }),
}

export const notifications = {
  list: (params) => client.get('/notifications', { params }),
  unreadCount: () => client.get('/notifications/unread-count'),
  markRead: (id) => client.post(`/notifications/${id}/read`),
  markAllRead: () => client.post('/notifications/read-all'),
}

/* ------------------------------ admin ------------------------------- */

const admin = '/admin'

export const adminApi = {
  dashboard: () => client.get(`${admin}/dashboard`),

  articles: {
    list: (params) => client.get(`${admin}/articles`, { params }),
    show: (id) => client.get(`${admin}/articles/${id}`),
    store: (payload) => client.post(`${admin}/articles`, payload),
    update: (id, payload) => client.put(`${admin}/articles/${id}`, payload),
    destroy: (id) => client.delete(`${admin}/articles/${id}`),
    changeStatus: (id, payload) => client.post(`${admin}/articles/${id}/status`, payload),
  },

  categories: {
    list: () => client.get(`${admin}/categories`),
    store: (payload) => client.post(`${admin}/categories`, payload),
    update: (id, payload) => client.put(`${admin}/categories/${id}`, payload),
    destroy: (id) => client.delete(`${admin}/categories/${id}`),
  },

  tags: {
    list: (params) => client.get(`${admin}/tags`, { params }),
    store: (payload) => client.post(`${admin}/tags`, payload),
    update: (id, payload) => client.put(`${admin}/tags/${id}`, payload),
    destroy: (id) => client.delete(`${admin}/tags/${id}`),
  },

  media: {
    list: (params) => client.get(`${admin}/media`, { params }),
    store: (formData, onProgress) =>
      client.post(`${admin}/media`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
        onUploadProgress: onProgress,
      }),
    update: (id, payload) => client.put(`${admin}/media/${id}`, payload),
    destroy: (id) => client.delete(`${admin}/media/${id}`),
  },

  pages: {
    list: (params) => client.get(`${admin}/pages`, { params }),
    store: (payload) => client.post(`${admin}/pages`, payload),
    update: (id, payload) => client.put(`${admin}/pages/${id}`, payload),
    destroy: (id) => client.delete(`${admin}/pages/${id}`),
  },

  comments: {
    list: (params) => client.get(`${admin}/comments`, { params }),
    changeStatus: (id, status) => client.post(`${admin}/comments/${id}/status`, { status }),
    destroy: (id) => client.delete(`${admin}/comments/${id}`),
  },

  polls: {
    list: (params) => client.get(`${admin}/polls`, { params }),
    store: (payload) => client.post(`${admin}/polls`, payload),
    update: (id, payload) => client.put(`${admin}/polls/${id}`, payload),
    destroy: (id) => client.delete(`${admin}/polls/${id}`),
  },

  users: {
    list: (params) => client.get(`${admin}/users`, { params }),
    store: (payload) => client.post(`${admin}/users`, payload),
    update: (id, payload) => client.put(`${admin}/users/${id}`, payload),
    destroy: (id) => client.delete(`${admin}/users/${id}`),
    ban: (id) => client.post(`${admin}/users/${id}/ban`),
    unban: (id) => client.post(`${admin}/users/${id}/unban`),
  },

  subscribers: {
    list: (params) => client.get(`${admin}/newsletter/subscribers`, { params }),
    destroy: (id) => client.delete(`${admin}/newsletter/subscribers/${id}`),
    exportUrl: () => `/api/v1${admin}/newsletter/subscribers/export`,
  },

  activity: {
    list: (params) => client.get(`${admin}/activity`, { params }),
  },

  settings: {
    get: () => client.get(`${admin}/settings`),
    update: (payload) => client.put(`${admin}/settings`, payload),
  },
}
