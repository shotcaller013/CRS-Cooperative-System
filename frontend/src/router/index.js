import { createRouter, createWebHistory } from 'vue-router'
import { memberRoutes }   from './routes/member.routes'
import { loanRoutes }     from './routes/loan.routes'
import { settingsRoutes } from './routes/settings.routes'
import { reportRoutes }   from './routes/report.routes'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/login', name: 'login', component: () => import('@/views/LoginView.vue'), meta: { public: true } },

    { path: '/',          name: 'dashboard', component: () => import('@/views/DashboardView.vue') },
    { path: '/members',   name: 'members',   component: () => import('@/views/MembersView.vue') },
    { path: '/loans',     name: 'loans',     component: () => import('@/views/LoanOfficerView.vue') },
    { path: '/pipeline',  name: 'pipeline',  component: () => import('@/views/PipelineView.vue') },
    { path: '/releasing', name: 'releasing', component: () => import('@/views/ReleasingView.vue') },
    { path: '/monitoring',name: 'monitoring',component: () => import('@/views/MonitoringView.vue') },

    ...memberRoutes,
    ...loanRoutes,
    ...settingsRoutes,
    ...reportRoutes,
  ],
})

router.beforeEach((to) => {
  const token = localStorage.getItem('crs_token')
  if (!to.meta.public && !token) {
    return { name: 'login' }
  }
  if (to.name === 'login' && token) {
    return { name: 'dashboard' }
  }
})

export default router
