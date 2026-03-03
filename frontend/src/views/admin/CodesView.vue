<script setup lang="ts">
import { ref } from 'vue'

const codes = ref([
    { id: 1, code: 'EDN-2025-AX1', type: 'Acceso Total', owner: 'Sin asignar', created_at: '2025-02-24' },
    { id: 2, code: 'EDN-2025-BX2', type: 'Curso Básico', owner: 'Juan Pérez', created_at: '2025-02-23' },
])
</script>

<template>
    <div>
        <header class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Códigos de Acceso</h1>
                <p class="text-slate-500 mt-2">Crea y gestiona códigos para invitar prospectos a tus cursos.</p>
            </div>
            <button class="bg-indigo-600 text-white px-6 py-3 rounded-xl font-bold shadow-lg shadow-indigo-200 hover:bg-indigo-700 hover:-translate-y-0.5 transition-all flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Generar Nuevo Código
            </button>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Table Section -->
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Código</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Tipo</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Propietario</th>
                            <th class="px-6 py-4"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="item in codes" :key="item.id" class="hover:bg-slate-50/30 transition-colors">
                            <td class="px-6 py-4 font-mono font-bold text-indigo-600 uppercase">{{ item.code }}</td>
                            <td class="px-6 py-4 text-slate-700">{{ item.type }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ item.owner }}</td>
                            <td class="px-6 py-4 text-right">
                                <button class="text-slate-400 hover:text-indigo-600 transition-colors mr-3">Asignar</button>
                                <button class="text-slate-400 hover:text-red-600 transition-colors">Eliminar</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Stats/Info Sidebar -->
            <div class="space-y-6">
                <div class="bg-indigo-900 rounded-2xl p-6 text-white shadow-xl">
                    <h3 class="font-bold text-lg mb-4">Información de Uso</h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center opacity-80">
                            <span>Total Generados</span>
                            <span class="font-bold">45</span>
                        </div>
                        <div class="flex justify-between items-center opacity-80">
                            <span>Disponibles</span>
                            <span class="font-bold">12</span>
                        </div>
                        <div class="w-full bg-indigo-800 h-2 rounded-full overflow-hidden">
                            <div class="bg-indigo-400 h-full w-2/3"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
