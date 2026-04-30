// src/router/routes/member.routes.js

export const memberRoutes = [
  {
    path: '/members/create',
    name: 'members.create',
    component: () => import('@/pages/member/MemberCreatePage.vue'),
    meta: { title: 'Add Member', permission: 'create-member' },
  },
  {
    path: '/members/:id',
    name: 'members.show',
    component: () => import('@/pages/member/MemberDetailPage.vue'),
    meta: { title: 'Member Detail', permission: 'view-member' },
  },
  {
    path: '/members/:id/edit',
    name: 'members.edit',
    component: () => import('@/pages/member/MemberEditPage.vue'),
    meta: { title: 'Edit Member', permission: 'edit-member' },
  },
]
