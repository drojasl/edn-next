import { defineStore } from 'pinia';
import axios from 'axios';
import { ref } from 'vue';
import { useRouter } from 'vue-router';

export const useAuthStore = defineStore('auth', () => {
    const user = ref(null);
    const token = ref(localStorage.getItem('token') || '');
    const router = useRouter();

    const api = axios.create({
        baseURL: 'http://localhost:8000/api',
        headers: {
            Authorization: `Bearer ${token.value}`
        }
    });

    async function login(credentials: any) {
        try {
            const response = await api.post('/login', credentials);
            token.value = response.data.token;
            user.value = response.data.user;
            localStorage.setItem('token', token.value);
            api.defaults.headers.Authorization = `Bearer ${token.value}`;
            router.push('/');
        } catch (error) {
            console.error('Login failed', error);
            throw error;
        }
    }

    async function register(userData: any) {
        try {
            const response = await api.post('/register', userData);
            token.value = response.data.token;
            user.value = response.data.user;
            localStorage.setItem('token', token.value);
            api.defaults.headers.Authorization = `Bearer ${token.value}`;
            router.push('/');
        } catch (error) {
            console.error('Registration failed', error);
            throw error;
        }
    }

    async function logout() {
        try {
            await api.post('/logout');
        } catch (error) {
            console.error('Logout failed', error);
        } finally {
            token.value = '';
            user.value = null;
            localStorage.removeItem('token');
            delete api.defaults.headers.Authorization;
            router.push('/login');
        }
    }

    async function fetchUser() {
        if (!token.value) return;
        try {
            const response = await api.get('/user');
            user.value = response.data;
        } catch (error) {
            token.value = '';
            user.value = null;
            localStorage.removeItem('token');
        }
    }

    return { user, token, login, register, logout, fetchUser };
});
