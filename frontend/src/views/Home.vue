<script setup lang="ts">
import { useAuthStore } from '../stores/auth'
import { useRouter } from 'vue-router'

const authStore = useAuthStore()
const router = useRouter()

const handleLogout = async () => {
  try {
    await authStore.logout()
  } catch (error) {
    console.error('Logout error:', error)
  }
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 p-8">
    <div class="max-w-7xl mx-auto">
      <!-- Top Bar -->
      <div class="flex justify-between items-center mb-12">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">
            Panel de Control
          </h1>
          <p class="text-gray-600 mt-2">
            Bienvenido a la plataforma de la Escuela de Negocios Digital
          </p>
        </div>
        
        <div class="flex items-center gap-4">
          <div class="text-right mr-4 hidden md:block">
            <p class="text-sm font-semibold text-gray-900">{{ authStore.user?.name }} {{ authStore.user?.last_name }}</p>
            <p class="text-xs text-gray-500">{{ authStore.user?.email }}</p>
          </div>
          <button 
            @click="handleLogout"
            class="px-4 py-2 border border-red-200 text-red-600 rounded-lg text-sm font-medium hover:bg-red-50 transition-colors flex items-center gap-2"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            Cerrar Sesión
          </button>
        </div>
      </div>

      <!-- Quick Actions / Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <!-- Courses Card -->
        <button 
          @click="router.push('/admin/cursos')"
          class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 text-left hover:shadow-md hover:border-indigo-200 transition-all group"
        >
          <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center mb-6 group-hover:bg-indigo-600 group-hover:text-white transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
          </div>
          <h3 class="text-xl font-bold text-gray-900 mb-2">Mis Cursos</h3>
          <p class="text-gray-500 text-sm">Crea y diseña mapas educativos interactivos para tus prospectos.</p>
        </button>

        <!-- Users Card -->
        <button 
          @click="router.push('/admin/users')"
          class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 text-left hover:shadow-md hover:border-indigo-200 transition-all group"
        >
          <div class="w-12 h-12 bg-green-50 text-green-600 rounded-xl flex items-center justify-center mb-6 group-hover:bg-green-600 group-hover:text-white transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
          </div>
          <h3 class="text-xl font-bold text-gray-900 mb-2">Empresarios</h3>
          <p class="text-gray-500 text-sm">Gestiona otros usuarios y colaboradores dentro de tu organización.</p>
        </button>

        <!-- Profile Settings (Placeholder) -->
        <div 
          class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 text-left opacity-60 cursor-not-allowed"
        >
          <div class="w-12 h-12 bg-gray-50 text-gray-600 rounded-xl flex items-center justify-center mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
          </div>
          <h3 class="text-xl font-bold text-gray-900 mb-2">Mi Perfil</h3>
          <p class="text-gray-500 text-sm">Próximamente: Personaliza tu marca y enlaces de contacto.</p>
        </div>
      </div>
    </div>
  </div>
</template>
