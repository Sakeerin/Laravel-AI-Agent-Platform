import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '../api/client';

export const usePersonalizationStore = defineStore('personalization', () => {
    const settings = ref(null);
    const memories = ref({ data: [], current_page: 1, last_page: 1 });
    const reminders = ref([]);
    const loading = ref(false);
    const saving = ref(false);
    const error = ref(null);

    async function fetchSettings() {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/agent-settings');
            settings.value = data;
        } catch (e) {
            error.value = e.response?.data?.message || 'Failed to load settings';
        } finally {
            loading.value = false;
        }
    }

    async function saveSettings(payload) {
        saving.value = true;
        error.value = null;
        try {
            const { data } = await api.patch('/agent-settings', payload);
            settings.value = data;
        } catch (e) {
            error.value = e.response?.data?.message || 'Failed to save settings';
            throw e;
        } finally {
            saving.value = false;
        }
    }

    async function fetchMemories(page = 1) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/memories', { params: { page } });
            memories.value = data;
        } catch (e) {
            error.value = e.response?.data?.message || 'Failed to load memories';
        } finally {
            loading.value = false;
        }
    }

    async function addMemory(content) {
        saving.value = true;
        error.value = null;
        try {
            await api.post('/memories', { content });
            await fetchMemories(memories.value.current_page || 1);
        } catch (e) {
            error.value = e.response?.data?.message || 'Failed to add memory';
            throw e;
        } finally {
            saving.value = false;
        }
    }

    async function removeMemory(id) {
        saving.value = true;
        error.value = null;
        try {
            await api.delete(`/memories/${id}`);
            await fetchMemories(memories.value.current_page || 1);
        } catch (e) {
            error.value = e.response?.data?.message || 'Failed to delete memory';
            throw e;
        } finally {
            saving.value = false;
        }
    }

    async function fetchReminders() {
        try {
            const { data } = await api.get('/reminders');
            reminders.value = data;
        } catch {
            /* non-fatal */
        }
    }

    async function ackReminder(id) {
        try {
            await api.post(`/reminders/${id}/ack`);
            await fetchReminders();
        } catch {
            /* */
        }
    }

    return {
        settings,
        memories,
        reminders,
        loading,
        saving,
        error,
        fetchSettings,
        saveSettings,
        fetchMemories,
        addMemory,
        removeMemory,
        fetchReminders,
        ackReminder,
    };
});
