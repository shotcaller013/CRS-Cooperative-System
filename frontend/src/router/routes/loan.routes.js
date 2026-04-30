// src/router/routes/loan.routes.js

export const loanRoutes = [
  {
    path: '/loans/create',
    name: 'loans.create',
    component: () => import('@/pages/loan/LoanCreatePage.vue'),
    meta: { title: 'New Loan Application', permission: 'create-loan' },
  },
  {
    path: '/loans/:id',
    name: 'loans.show',
    component: () => import('@/pages/loan/LoanDetailPage.vue'),
    meta: { title: 'Loan Detail', permission: 'view-loan' },
  },
  {
    path: '/loans/:id/edit',
    name: 'loans.edit',
    component: () => import('@/pages/loan/LoanEditPage.vue'),
    meta: { title: 'Edit Loan', permission: 'edit-loan' },
  },
  {
    path: '/loans/pipeline',
    name: 'loans.pipeline',
    component: () => import('@/pages/loan/LoanPipelinePage.vue'),
    meta: { title: 'Loan Pipeline', permission: 'view-loan' },
  },
]
