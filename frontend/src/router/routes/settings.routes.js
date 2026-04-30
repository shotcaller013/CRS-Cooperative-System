// src/router/routes/settings.routes.js
export const settingsRoutes = [
  {
    path: '/settings',
    name: 'settings',
    component: () => import('@/pages/settings/SettingsPage.vue'),
    meta: { title: 'Settings', role: 'super-admin' },
  },
  {
    path: '/payments',
    name: 'payments',
    component: () => import('@/pages/payments/PaymentListPage.vue'),
    meta: { title: 'Payments' },
  },
]
