import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '../api/client';

export const useAuthStore = defineStore('auth', () => {
    const user = ref(null);
    const token = ref(localStorage.getItem('auth_token'));
    const loading = ref(false);

    const isAuthenticated = computed(() => !!token.value);

    async function login(email, password) {
        loading.value = true;
        try {
            const { data } = await api.post('/login', { email, password });
            token.value = data.token;
            user.value = data.user;
            localStorage.setItem('auth_token', data.token);
            return data;
        } finally {
            loading.value = false;
        }
    }

    async function register(name, email, password, password_confirmation) {
        loading.value = true;
        try {
            const { data } = await api.post('/register', {
                name, email, password, password_confirmation,
            });
            token.value = data.token;
            user.value = data.user;
            localStorage.setItem('auth_token', data.token);
            return data;
        } finally {
            loading.value = false;
        }
    }

    async function logout() {
        try {
            await api.post('/logout');
        } catch {
            // ignore logout errors
        }
        token.value = null;
        user.value = null;
        localStorage.removeItem('auth_token');
    }

    async function fetchUser() {
        if (!token.value) return null;
        try {
            const { data } = await api.get('/user');
            user.value = data;
            return data;
        } catch {
            token.value = null;
            user.value = null;
            localStorage.removeItem('auth_token');
            return null;
        }
    }

    return { user, token, loading, isAuthenticated, login, register, logout, fetchUser };
});
