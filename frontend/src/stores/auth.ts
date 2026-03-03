import { defineStore } from 'pinia';
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { apiRequest } from '../api/apiClient';

export interface User {
    id: number;
    name: string;
    last_name: string;
    codigo_amway: string;
    is_account_holder: boolean;
    email?: string;
    slug?: string;
    is_active?: boolean;
    profile_picture?: string | null;
}

export const useAuthStore = defineStore('auth', () => {
    const user = ref<User | null>(null);
    const token = ref(localStorage.getItem('token') || '');
    const router = useRouter();

    async function login(credentials: any) {
        const result = await apiRequest({
            method: 'POST',
            url: '/auth/login',
            body: credentials
        });

        if (result.success && result.data) {
            token.value = result.data.token;
            user.value = result.data.user;
            localStorage.setItem('token', token.value);
            router.push('/admin');
        } else {
            console.error('Login failed', result.error);
            const error: any = new Error(result.error?.message || 'Login failed');
            error.code = result.error?.code;
            throw error;
        }
    }

    async function register(userData: any) {
        const result = await apiRequest({
            method: 'POST',
            url: '/register',
            body: userData
        });

        if (result.success && result.data) {
            token.value = result.data.token;
            user.value = result.data.user;
            localStorage.setItem('token', token.value);
            router.push('/admin');
        } else {
            console.error('Registration failed', result.error);
            throw new Error(result.error?.message || 'Registration failed');
        }
    }

    async function logout() {
        await apiRequest({
            method: 'POST',
            url: '/logout'
        });

        token.value = '';
        user.value = null;
        localStorage.removeItem('token');
        router.push('/admin/login');
    }

    async function fetchUser() {
        if (!token.value) return;

        const result = await apiRequest({
            method: 'GET',
            url: '/user'
        });

        if (result.success) {
            user.value = result.data;
        } else {
            token.value = '';
            user.value = null;
            localStorage.removeItem('token');
        }
    }

    return { user, token, login, register, logout, fetchUser };
});
