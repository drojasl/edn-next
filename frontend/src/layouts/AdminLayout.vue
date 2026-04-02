<script setup lang="ts">
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const authStore = useAuthStore()
const route = useRoute()

const isUserMenuOpen = ref(false)
const isMobileMenuOpen = ref(false)

const navItems = [
    { name: 'admin.nav.dashboard', path: '/admin', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
    { name: 'admin.nav.prospects', path: '/admin/prospectos', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' },
    { name: 'admin.nav.courses', path: '/admin/cursos', icon: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253' },
    { name: 'admin.nav.codes', path: '/admin/codigos', icon: 'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z' },
    { name: 'admin.nav.profile', path: '/admin/perfil', icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' },
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
                    <!-- Left: Mobile Toggle & Logo -->
                    <div class="flex items-center gap-3">
                        <button 
                            @click="isMobileMenuOpen = true"
                            class="p-2 -ml-2 text-slate-500 hover:text-indigo-600 md:hidden transition-colors"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>

                        <router-link to="/admin" class="flex items-center gap-2 group">
                            <div class="w-9 h-9 sm:w-10 sm:h-10 bg-indigo-600 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-100 group-hover:scale-105 transition-transform">
                                <span class="text-white font-black text-lg sm:text-xl">E</span>
                            </div>
                            <span class="text-lg sm:text-xl font-bold bg-gradient-to-r from-slate-900 to-slate-700 bg-clip-text text-transparent hidden xs:block">
                                {{ $t('admin.nav.title') }}
                            </span>
                        </router-link>

                        <!-- Desk Nav -->
                        <nav class="hidden md:flex ml-4 space-x-1">
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
                                {{ $t(item.name) }}
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
                                <div class="w-9 h-9 bg-slate-100 border border-slate-200 rounded-full flex items-center justify-center text-slate-600 transition-transform" :class="{'ring-2 ring-indigo-500': isUserMenuOpen}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                            </button>

                            <!-- User Dropdown Overlay -->
                            <div 
                                v-if="isUserMenuOpen"
                                @click="isUserMenuOpen = false"
                                class="fixed inset-0 z-10"
                            ></div>
                            
                            <div 
                                v-if="isUserMenuOpen"
                                class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-2xl border border-slate-200 py-2 z-20 overflow-hidden fade-in"
                            >
                                <div class="px-4 py-3 border-b border-slate-100 sm:hidden">
                                    <p class="text-sm font-semibold text-slate-900 truncate">{{ authStore.user?.name }}</p>
                                    <p class="text-xs text-slate-500">{{ authStore.user?.codigo_amway }}</p>
                                </div>
                                <router-link 
                                    to="/admin/perfil" 
                                    class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-indigo-50 hover:text-indigo-700 transition-colors"
                                    @click="isUserMenuOpen = false"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                    {{ $t('admin.nav.viewProfile') }}
                                </router-link>
                                <router-link 
                                    to="/admin/config" 
                                    class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-indigo-50 hover:text-indigo-700 transition-colors"
                                    @click="isUserMenuOpen = false"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                    {{ $t('admin.nav.settings') }}
                                </router-link>
                                <button 
                                    @click="handleLogout"
                                    class="w-full text-left flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors border-t border-slate-100 mt-1"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                                    {{ $t('admin.nav.logout') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Mobile Sidebar Menu -->
        <transition 
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="-translate-x-full"
            enter-to-class="translate-x-0"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="translate-x-0"
            leave-to-class="-translate-x-full"
        >
            <div 
                v-if="isMobileMenuOpen" 
                class="fixed inset-0 z-[60] md:hidden"
            >
                <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm" @click="isMobileMenuOpen = false"></div>
                
                <nav class="relative w-72 max-w-[80vw] h-full bg-white shadow-2xl flex flex-col pt-5 pb-8 overflow-y-auto">
                    <div class="px-6 pb-6 border-b border-slate-100 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center">
                                <span class="text-white font-black">E</span>
                            </div>
                            <span class="font-bold text-slate-800">{{ $t('admin.nav.title') }}</span>
                        </div>
                        <button @click="isMobileMenuOpen = false" class="text-slate-400 hover:text-slate-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <div class="px-3 py-6 space-y-1">
                        <router-link 
                            v-for="item in navItems" 
                            :key="item.path"
                            :to="item.path"
                            @click="isMobileMenuOpen = false"
                            class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all"
                            :class="[
                                isActive(item.path) 
                                    ? 'bg-indigo-50 text-indigo-700' 
                                    : 'text-slate-600 hover:bg-slate-50 hover:text-indigo-600'
                            ]"
                        >
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                            </svg>
                            {{ $t(item.name) }}
                        </router-link>
                    </div>

                    <div class="mt-auto px-6 pt-6 border-t border-slate-100">
                        <p class="text-[10px] uppercase font-bold text-slate-400 tracking-widest mb-4">EDN ADMIN v1.0</p>
                        <button 
                            @click="handleLogout"
                            class="flex items-center gap-3 text-red-500 font-bold text-sm w-full py-2 hover:translate-x-1 transition-transform"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                            {{ $t('admin.nav.logout') }}
                        </button>
                    </div>
                </nav>
            </div>
        </transition>

        <!-- Main Content -->
        <main class="flex-1">
            <div class="max-w-7xl mx-auto py-6 sm:py-8 px-4 sm:px-6 lg:px-8">
                <router-view v-slot="{ Component }">
                    <transition 
                        name="fade" 
                        mode="out-in"
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
