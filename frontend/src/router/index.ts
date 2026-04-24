import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    // Public Routes
    {
      path: '/',
      redirect: '/cursos',
    },
    {
      path: '/cursos',
      name: 'cursos-landing',
      component: () => import('../views/CoursesLanding.vue'),
    },
    {
      path: '/cursos/:entrepreneurSlug/:courseSlug/:nodeSlug?',
      component: () => import('../layouts/CourseLayout.vue'),
      children: [
        {
          path: '',
          name: 'course-view',
          component: () => import('../views/course/CourseView.vue'),
        },
      ],
    },

    // Auth Routes (Guests only)
    {
      path: '/admin/login',
      name: 'login',
      component: () => import('../views/admin/LoginForm.vue'),
      meta: { guestOnly: true },
    },
    {
      path: '/admin/register',
      name: 'register',
      component: () => import('../views/admin/RegisterView.vue'),
      meta: { guestOnly: true },
    },
    {
      path: '/admin/forgot-password',
      name: 'forgot-password',
      component: () => import('../views/admin/ForgotPassword.vue'),
      meta: { guestOnly: true },
    },
    {
      path: '/admin/reset-password',
      name: 'reset-password',
      component: () => import('../views/admin/ResetPassword.vue'),
      meta: { guestOnly: true },
    },

    // Admin Routes (Authenticated)
    {
      path: '/admin',
      component: () => import('../layouts/AdminLayout.vue'),
      meta: { requiresAuth: true },
      children: [
        {
          path: '',
          name: 'admin-dashboard',
          component: () => import('../views/admin/DashboardView.vue'),
        },
        {
          path: 'prospectos',
          name: 'admin-prospects',
          component: () => import('../views/admin/ProspectsView.vue'),
        },
        {
          path: 'cursos',
          name: 'admin-courses',
          component: () => import('../views/admin/CoursesAdmin.vue'),
        },
        {
          path: 'cursos/:id/edit',
          name: 'admin-course-edit',
          component: () => import('../views/admin/course/CourseEditor.vue'),
        },
        {
          path: 'codigos',
          name: 'admin-codes',
          component: () => import('../views/admin/CodesView.vue'),
        },
        {
          path: 'perfil',
          name: 'admin-profile',
          component: () => import('../views/admin/ProfileView.vue'),
        },
        {
          path: 'config',
          name: 'admin-config',
          component: () => import('../views/admin/ConfigView.vue'),
        },
        {
          path: 'users',
          name: 'admin-users',
          component: () => import('../views/admin/user/UserListView.vue'),
        },
        {
          path: 'users/create',
          name: 'admin-user-create',
          component: () => import('../views/admin/user/UserCreateView.vue'),
        },
        {
          path: 'users/:id/edit',
          name: 'admin-user-edit',
          component: () => import('../views/admin/user/UserEditView.vue'),
        },
      ],
    },
  ],
})

router.beforeEach(async (to, _from, next) => {
  const authStore = useAuthStore()
  const isAuthenticated = !!authStore.token

  if (to.meta.requiresAuth && !isAuthenticated) {
    return next('/admin/login')
  }

  if (to.meta.guestOnly && isAuthenticated) {
    return next('/admin')
  }

  next()
})

export default router
