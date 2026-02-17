import { createRouter, createWebHistory } from 'vue-router';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/',
            name: 'home',
            component: () => import('../views/Home.vue'),
            meta: { requiresAuth: true }
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
                }
            ],
        },
        {
            path: '/admin/login',
            name: 'login',
            component: () => import('../views/admin/LoginForm.vue'),
        },
        {
            path: '/admin/cursos',
            name: 'admin-courses',
            component: () => import('../views/admin/CoursesAdmin.vue'),
            // meta: { requiresAuth: true } // Enable this when auth is fully integrated
        },
        {
            path: '/admin/cursos/:id/edit',
            name: 'admin-course-edit',
            component: () => import('../views/admin/course/CourseEditor.vue'),
        }
    ]
});

router.beforeEach(async (_to, _from, next) => {
    // const auth = useAuthStore(); // Can't use store outside of app install yet in some setups, but here it's fine if pinia is installed.
    // However, best practice is to check token in localStorage or similar if store isn't ready.
    // For simplicity, we'll assume basic check or skip for now until Views are created.
    next();
});

export default router;
