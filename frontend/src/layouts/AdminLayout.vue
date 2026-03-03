<script setup lang="ts">
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const authStore = useAuthStore()
const route = useRoute()

const isUserMenuOpen = ref(false)

const navItems = [
    { name: 'Dashboard', path: '/admin', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
    { name: 'Prospectos', path: '/admin/prospectos', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' },
    { name: 'Cursos', path: '/admin/cursos', icon: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253' },
    { name: 'Códigos', path: '/admin/codigos', icon: 'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z' },
    { name: 'Perfil', path: '/admin/perfil', icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' },
]

const handleLogout = async () => {
    try {
        await authStore.logout()
    } catch (error) {
        console.error('Error logging out:', error)
    }
}

const isActive = (path: string) => {
    if (path === '/admin') return route.path === '/admin'
    return route.path.startsWith(path)
}
</script>

<template>
    <div class="min-h-screen bg-slate-50 flex flex-col">
        <!-- Header -->
        <header class="bg-white border-b border-slate-200 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <!-- Logo & Nav -->
                    <div class="flex items-center gap-8">
                        <router-link to="/admin" class="flex items-center gap-2 group">
                            <div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-100 group-hover:scale-105 transition-transform">
                                <span class="text-white font-black text-xl">E</span>
                            </div>
                            <span class="text-xl font-bold bg-gradient-to-r from-slate-900 to-slate-700 bg-clip-text text-transparent hidden sm:block">
                                EDN Admin
                            </span>
                        </router-link>

                        <!-- Desk Nav -->
                        <nav class="hidden md:flex space-x-1">
                            <router-link 
                                v-for="item in navItems" 
                                :key="item.path"
                                :to="item.path"
                                class="px-3 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2"
                                :class="[
                                    isActive(item.path) 
                                        ? 'bg-indigo-50 text-indigo-700' 
                                        : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
                                ]"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                                </svg>
                                {{ item.name }}
                            </router-link>
                        </nav>
                    </div>

                    <!-- Right Profile -->
                    <div class="flex items-center gap-4">
                        <div class="relative">
                            <button 
                                @click="isUserMenuOpen = !isUserMenuOpen"
                                class="flex items-center gap-3 p-1.5 rounded-full hover:bg-slate-50 transition-colors focus:outline-none"
                            >
                                <div class="text-right hidden sm:block">
                                    <p class="text-sm font-semibold text-slate-900 leading-none">{{ authStore.user?.name }}</p>
                                    <p class="text-xs text-slate-500 mt-1 uppercase tracking-wider font-medium">{{ authStore.user?.codigo_amway }}</p>
                                </div>
                                <div class="w-9 h-9 bg-slate-100 border border-slate-200 rounded-full flex items-center justify-center text-slate-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                            </button>

                            <!-- Dropdown -->
                            <div 
                                v-if="isUserMenuOpen"
                                @click="isUserMenuOpen = false"
                                class="fixed inset-0 z-10"
                            ></div>
                            
                            <div 
                                v-if="isUserMenuOpen"
                                class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-xl border border-slate-200 py-2 z-20 overflow-hidden"
                            >
                                <div class="px-4 py-2 border-b border-slate-100 sm:hidden">
                                    <p class="text-sm font-semibold text-slate-900">{{ authStore.user?.name }}</p>
                                    <p class="text-xs text-slate-500">{{ authStore.user?.codigo_amway }}</p>
                                </div>
                                <router-link to="/admin/perfil" class="block px-4 py-2 text-sm text-slate-700 hover:bg-indigo-50 hover:text-indigo-700 transition-colors">
                                    Ver Perfil
                                </router-link>
                                <router-link to="/admin/config" class="block px-4 py-2 text-sm text-slate-700 hover:bg-indigo-50 hover:text-indigo-700 transition-colors">
                                    Configuración
                                </router-link>
                                <button 
                                    @click="handleLogout"
                                    class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors border-t border-slate-100 mt-1"
                                >
                                    Cerrar Sesión
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 overflow-auto">
            <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
                <router-view v-slot="{ Component }">
                    <transition 
                        name="fade" 
                        mode="out-in"
                        enter-active-class="transition duration-200 ease-out"
                        enter-from-class="opacity-0 translate-y-1"
                        enter-to-class="opacity-100 translate-y-0"
                        leave-active-class="transition duration-150 ease-in"
                        leave-from-class="opacity-100 translate-y-0"
                        leave-to-class="opacity-0 translate-y-1"
                    >
                        <component :is="Component" />
                    </transition>
                </router-view>
            </div>
        </main>
    </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
