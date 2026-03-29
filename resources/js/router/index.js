import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const routes = [
    {
        path: '/login',
        name: 'login',
        component: () => import('../views/Login.vue'),
        meta: { guest: true },
    },
    {
        path: '/register',
        name: 'register',
        component: () => import('../views/Register.vue'),
        meta: { guest: true },
    },
    {
        path: '/',
        name: 'chat',
        component: () => import('../views/Chat.vue'),
        meta: { auth: true },
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach(async (to) => {
    const auth = useAuthStore();

    if (!auth.user && auth.token) {
        await auth.fetchUser();
    }

    if (to.meta.auth && !auth.isAuthenticated) {
        return { name: 'login' };
    }

    if (to.meta.guest && auth.isAuthenticated) {
        return { name: 'chat' };
    }
});

export default router;
