<script setup lang="ts">
import { ref } from 'vue'

const prospects = ref([
    { id: 1, name: 'Juan Pérez', email: 'juan@example.com', date: '2025-02-20', course: 'Introducción al Negocio', status: 'Nuevo' },
    { id: 2, name: 'Maria Garcia', email: 'maria.g@gmail.com', date: '2025-02-18', course: 'Plan de Ventas', status: 'Interesado' },
    { id: 3, name: 'Roberto Lima', email: 'rlima@outlook.com', date: '2025-02-15', course: 'Liderazgo 101', status: 'Contactado' },
])
</script>

<template>
    <div>
        <header class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Prospectos</h1>
                <p class="text-slate-500 mt-2">Gestiona las personas interesadas que han dejado sus datos.</p>
            </div>
            <button class="bg-indigo-600 text-white px-5 py-2.5 rounded-xl font-bold shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Exportar CSV
            </button>
        </header>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Nombre</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Curso de entrada</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Fecha</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Estado</th>
                        <th class="px-6 py-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="prospect in prospects" :key="prospect.id" class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4 font-semibold text-slate-900">{{ prospect.name }}</td>
                        <td class="px-6 py-4 text-slate-600">{{ prospect.email }}</td>
                        <td class="px-6 py-4 text-slate-600">{{ prospect.course }}</td>
                        <td class="px-6 py-4 text-slate-500 text-sm">{{ prospect.date }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold"
                                :class="[
                                    prospect.status === 'Nuevo' ? 'bg-indigo-50 text-indigo-700' :
                                    prospect.status === 'Interesado' ? 'bg-amber-50 text-amber-700' :
                                    'bg-emerald-50 text-emerald-700'
                                ]">
                                {{ prospect.status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button class="text-slate-400 hover:text-indigo-600 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                </svg>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
