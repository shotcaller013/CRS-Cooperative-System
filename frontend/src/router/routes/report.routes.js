// src/router/routes/report.routes.js
export const reportRoutes = [
  {
    path: '/reports',
    name: 'reports.index',
    component: () => import('@/pages/reports/ReportsIndexPage.vue'),
    meta: { title: 'Reports' },
  },
  {
    path: '/reports/collection',
    name: 'reports.collection',
    component: () => import('@/pages/reports/CollectionSummaryPage.vue'),
    meta: { title: 'Collection Summary' },
  },
  {
    path: '/reports/aging',
    name: 'reports.aging',
    component: () => import('@/pages/reports/AgingReportPage.vue'),
    meta: { title: 'Aging Report' },
  },
  {
    path: '/reports/outstanding',
    name: 'reports.outstanding',
    component: () => import('@/pages/reports/OutstandingBalancePage.vue'),
    meta: { title: 'Outstanding Balance' },
  },
]
