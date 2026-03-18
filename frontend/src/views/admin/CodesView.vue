<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { apiRequest } from '../../api/apiClient'
import AppToast from '../../components/common/AppToast.vue'
import ConfirmationModal from '../../components/common/ConfirmationModal.vue'

interface Course {
    id: number;
    title: string;
}

interface AccessCode {
    id: number;
    code: string;
    course_id: number;
    user_id: number;
    expires_at: string | null;
    is_active: boolean;
    created_at: string;
    course?: Course;
    user?: { id: number; name: string; last_name: string };
}

const codes = ref<AccessCode[]>([])
const courses = ref<Course[]>([])
const isLoading = ref(true)

const toastRef = ref<InstanceType<typeof AppToast> | null>(null)
const modalRef = ref<InstanceType<typeof ConfirmationModal> | null>(null)

const showModal = ref(false)
const newCodeData = ref({
    course_id: '',
    code: '',
    expiration_days: ''
})

const codeAvailability = ref({
    checking: false,
    available: true,
    message: ''
})

const fetchCourses = async () => {
    try {
        const response = await apiRequest<{ data: Course[] }>({
            method: 'GET',
            url: '/v1/admin/courses',
        })
        if (response.success && response.data) {
            courses.value = response.data.data
        }
    } catch (e) {
        console.error('Error fetching courses:', e)
    }
}

const fetchCodes = async () => {
    isLoading.value = true
    try {
        const response = await apiRequest<{ data: AccessCode[] }>({
            method: 'GET',
            url: '/v1/admin/access-codes',
        })
        if (response.success && response.data) {
            codes.value = response.data.data
        }
    } catch (e) {
        console.error('Error fetching codes:', e)
    } finally {
        isLoading.value = false
    }
}

const generateRandomCode = () => {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'
    let result = ''
    for (let i = 0; i < 6; i++) {
        result += chars.charAt(Math.floor(Math.random() * chars.length))
    }
    return result
}

const openGenerateModal = () => {
    newCodeData.value = {
        course_id: '',
        code: generateRandomCode(),
        expiration_days: ''
    }
    codeAvailability.value = { checking: false, available: true, message: '' }
    showModal.value = true
}

const checkCode = async () => {
    if (!newCodeData.value.code) {
        codeAvailability.value = { checking: false, available: false, message: 'El código es requerido' }
        return
    }

    codeAvailability.value.checking = true
    try {
        const result = await apiRequest({
            method: 'POST',
            url: '/v1/admin/access-codes/validate-code',
            body: { code: newCodeData.value.code }
        })

        if (result.success) {
            codeAvailability.value.available = result.data.available
            codeAvailability.value.message = result.data.available 
                ? 'Código disponible'
                : 'Este código ya está en uso'
        }
    } catch (error) {
        console.error('Error checking code:', error)
    } finally {
        codeAvailability.value.checking = false
    }
}

let codeTimeout: ReturnType<typeof setTimeout> | null = null
watch(() => newCodeData.value.code, () => {
    if (codeTimeout) clearTimeout(codeTimeout)
    codeTimeout = setTimeout(checkCode, 500)
})

const handleGenerateCode = async () => {
    if (!newCodeData.value.course_id) {
        toastRef.value?.show('Debe seleccionar un curso', 'error')
        return
    }
    if (!newCodeData.value.code || !codeAvailability.value.available) {
        toastRef.value?.show('El código es inválido o ya existe', 'error')
        return
    }

    try {
        const response = await apiRequest<{ data: AccessCode }>({
            method: 'POST',
            url: '/v1/admin/access-codes',
            body: {
                course_id: newCodeData.value.course_id,
                code: newCodeData.value.code.toUpperCase(),
                expiration_days: newCodeData.value.expiration_days || null
            }
        })
        if (response.success && response.data) {
            toastRef.value?.show('El código se ha generado correctamente', 'success')
            showModal.value = false
            fetchCodes()
        } else {
            toastRef.value?.show(response.error?.message || 'Hubo un error al generar el código', 'error')
        }
    } catch (e: any) {
        toastRef.value?.show(e.message || 'Error al conectar con el servidor', 'error')
    }
}

const handleDeleteCode = (id: number) => {
    modalRef.value?.open({
        title: 'Eliminar Código',
        message: '¿Estás seguro de que deseas eliminar este código? ¡No podrás revertir esto!',
        isDestructive: true,
        confirmText: 'Sí, eliminar',
        onConfirm: async () => {
            try {
                const response = await apiRequest({
                    method: 'DELETE',
                    url: `/v1/admin/access-codes/${id}`,
                })
                if (response.success) {
                    toastRef.value?.show('El código ha sido eliminado.', 'success')
                    fetchCodes()
                } else {
                    toastRef.value?.show(response.error?.message || 'No se pudo eliminar el código', 'error')
                }
            } catch (e) {
                console.error('Error deleting code:', e)
                toastRef.value?.show('Error de conexión', 'error')
            }
        }
    })
}

onMounted(() => {
    fetchCourses()
    fetchCodes()
})

const formatDate = (dateString: string | null) => {
    if (!dateString) return 'Nunca';
    return new Date(dateString).toLocaleDateString('es-ES', {
        year: 'numeric', month: 'short', day: 'numeric'
    });
}
</script>

<template>
    <div>
        <header class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Códigos de Acceso</h1>
                <p class="text-slate-500 mt-2">Crea y gestiona códigos para invitar prospectos a tus cursos.</p>
            </div>
            <button @click="openGenerateModal" class="bg-indigo-600 text-white px-6 py-3 rounded-xl font-bold shadow-lg shadow-indigo-200 hover:bg-indigo-700 hover:-translate-y-0.5 transition-all flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Generar Nuevo Código
            </button>
        </header>

        <!-- Generate Code Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm">
            <div class="bg-white rounded-2xl w-full max-w-md p-6 shadow-2xl">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-slate-900">Generar Código</h2>
                    <button @click="showModal = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Código (Max 6 chars) *</label>
                        <div class="relative">
                            <input
                                v-model="newCodeData.code"
                                type="text"
                                maxlength="6"
                                class="w-full px-4 py-2 uppercase border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                :class="{ 
                                    'border-red-500': (!codeAvailability.available && newCodeData.code),
                                    'border-green-500': (codeAvailability.available && newCodeData.code && !codeAvailability.checking)
                                }"
                            />
                            <div v-if="codeAvailability.checking" class="absolute right-3 top-2.5">
                                <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-indigo-600"></div>
                            </div>
                        </div>
                        <p 
                            v-if="newCodeData.code && !codeAvailability.checking" 
                            class="mt-1 text-sm inline-block"
                            :class="codeAvailability.available ? 'text-green-600' : 'text-red-600'"
                        >
                            {{ codeAvailability.message }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Curso Asignado *</label>
                        <select v-model="newCodeData.course_id" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Selecciona un curso</option>
                            <option v-for="course in courses" :key="course.id" :value="course.id">{{ course.title }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Expiración</label>
                        <select v-model="newCodeData.expiration_days" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">No expira</option>
                            <option value="2">2 días</option>
                            <option value="7">7 días</option>
                            <option value="15">15 días</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-8">
                    <button @click="showModal = false" class="px-4 py-2 text-slate-600 font-medium hover:bg-slate-50 rounded-lg transition-colors">
                        Cancelar
                    </button>
                    <button @click="handleGenerateCode" class="px-6 py-2 bg-indigo-600 text-white font-bold rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed" :disabled="!codeAvailability.available || codeAvailability.checking || !newCodeData.code">
                        Generar
                    </button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Table Section -->
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div v-if="isLoading" class="p-8 text-center text-slate-500">
                    Cargando códigos...
                </div>
                <table v-else class="w-full text-left">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Código</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Curso</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Expira</th>
                            <th class="px-6 py-4"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="codes.length === 0">
                            <td colspan="4" class="px-6 py-8 text-center text-slate-500">
                                No se encontraron códigos de acceso.
                            </td>
                        </tr>
                        <tr v-for="item in codes" :key="item.id" class="hover:bg-slate-50/30 transition-colors">
                            <td class="px-6 py-4 font-mono font-bold text-indigo-600 uppercase">{{ item.code }}</td>
                            <td class="px-6 py-4 text-slate-700">{{ item.course?.title || 'Curso eliminado' }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ formatDate(item.expires_at) }}</td>
                            <td class="px-6 py-4 text-right">
                                <button @click="handleDeleteCode(item.id)" class="text-slate-400 hover:text-red-600 transition-colors">Eliminar</button>
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
                            <span class="font-bold">{{ codes.length }}</span>
                        </div>
                        <div class="flex justify-between items-center opacity-80">
                            <span>Disponibles</span>
                            <span class="font-bold">{{ codes.filter(c => c.is_active).length }}</span>
                        </div>
                        <div v-if="codes.length > 0" class="w-full bg-indigo-800 h-2 rounded-full overflow-hidden">
                            <div class="bg-indigo-400 h-full" :style="`width: ${(codes.filter(c => c.is_active).length / codes.length) * 100}%`"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <AppToast ref="toastRef" />
        <ConfirmationModal ref="modalRef" />
    </div>
</template>
