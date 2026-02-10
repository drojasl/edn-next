import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/',
            name: 'home',
            component: () => import('../App.vue'), // Temporary placeholder
            meta: { requiresAuth: true }
        },
        {
            path: '/login',
            name: 'login',
            component: () => import('../components/HelloWorld.vue'), // Temporary placeholder
        }
    ]
});

router.beforeEach(async (to, from, next) => {
    // const auth = useAuthStore(); // Can't use store outside of app install yet in some setups, but here it's fine if pinia is installed.
    // However, best practice is to check token in localStorage or similar if store isn't ready.
    // For simplicity, we'll assume basic check or skip for now until Views are created.
    next();
});

export default router;
