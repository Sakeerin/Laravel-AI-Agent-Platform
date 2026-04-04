import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '../api/client';

export const useTasksStore = defineStore('tasks', () => {
    const tasks = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const pagination = ref({ current_page: 1, last_page: 1, total: 0 });
    const statusFilter = ref('');

    const activeTasks = computed(() =>
        tasks.value.filter(t => t.status === 'running' || t.status === 'queued')
    );

    async function fetchTasks(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const params = { page };
            if (statusFilter.value) params.status = statusFilter.value;

            const { data } = await api.get('/tasks', { params });
            tasks.value = data.data || [];
            pagination.value = {
                current_page: data.current_page || 1,
                last_page: data.last_page || 1,
                total: data.total || 0,
            };
        } catch (e) {
            error.value = e.response?.data?.message || 'Failed to load tasks';
        } finally {
            loading.value = false;
        }
    }

    async function cancelTask(taskId) {
        try {
            await api.post(`/tasks/${taskId}/cancel`);
            const found = tasks.value.find(t => t.id === taskId);
            if (found) {
                found.status = 'cancelled';
                found.completed_at = new Date().toISOString();
            }
        } catch (e) {
            error.value = e.response?.data?.message || 'Failed to cancel task';
        }
    }

    function setFilter(status) {
        statusFilter.value = status;
        fetchTasks(1);
    }

    return {
        tasks, loading, error, pagination, statusFilter, activeTasks,
        fetchTasks, cancelTask, setFilter,
    };
});
