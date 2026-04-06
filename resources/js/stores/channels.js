import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '../api/client';

export const useChannelsStore = defineStore('channels', () => {
    const items = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const saving = ref(false);

    async function fetchAll() {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/channel-connections');
            items.value = data;
        } catch (e) {
            error.value = e.response?.data?.message || 'Failed to load channel connections';
        } finally {
            loading.value = false;
        }
    }

    async function fetchDetail(id) {
        const { data } = await api.get(`/channel-connections/${id}`);
        return data;
    }

    async function createLine(payload) {
        saving.value = true;
        error.value = null;
        try {
            const { data } = await api.post('/channel-connections', {
                provider: 'line',
                label: payload.label || null,
                line_channel_secret: payload.line_channel_secret,
                line_channel_access_token: payload.line_channel_access_token,
            });
            await fetchAll();
            return data;
        } catch (e) {
            error.value = e.response?.data?.message || 'Failed to save LINE connection';
            throw e;
        } finally {
            saving.value = false;
        }
    }

    async function createTelegram(payload) {
        saving.value = true;
        error.value = null;
        try {
            const { data } = await api.post('/channel-connections', {
                provider: 'telegram',
                label: payload.label || null,
                telegram_bot_token: payload.telegram_bot_token,
            });
            await fetchAll();
            return data;
        } catch (e) {
            error.value = e.response?.data?.message || 'Failed to save Telegram connection';
            throw e;
        } finally {
            saving.value = false;
        }
    }

    async function createSlack(payload) {
        saving.value = true;
        error.value = null;
        try {
            const { data } = await api.post('/channel-connections', {
                provider: 'slack',
                label: payload.label || null,
                slack_signing_secret: payload.slack_signing_secret,
                slack_bot_token: payload.slack_bot_token,
            });
            await fetchAll();
            return data;
        } catch (e) {
            error.value = e.response?.data?.message || 'Failed to save Slack connection';
            throw e;
        } finally {
            saving.value = false;
        }
    }

    async function createDiscord(payload) {
        saving.value = true;
        error.value = null;
        try {
            const { data } = await api.post('/channel-connections', {
                provider: 'discord',
                label: payload.label || null,
                discord_public_key: payload.discord_public_key,
                discord_application_id: payload.discord_application_id,
                discord_bot_token: payload.discord_bot_token || null,
            });
            await fetchAll();
            return data;
        } catch (e) {
            error.value = e.response?.data?.message || 'Failed to save Discord connection';
            throw e;
        } finally {
            saving.value = false;
        }
    }

    async function updateConnection(id, payload) {
        saving.value = true;
        error.value = null;
        try {
            const { data } = await api.patch(`/channel-connections/${id}`, payload);
            await fetchAll();
            return data;
        } catch (e) {
            error.value = e.response?.data?.message || 'Failed to update connection';
            throw e;
        } finally {
            saving.value = false;
        }
    }

    async function remove(id) {
        saving.value = true;
        error.value = null;
        try {
            await api.delete(`/channel-connections/${id}`);
            await fetchAll();
        } catch (e) {
            error.value = e.response?.data?.message || 'Failed to delete connection';
            throw e;
        } finally {
            saving.value = false;
        }
    }

    async function registerTelegramWebhook(id) {
        saving.value = true;
        error.value = null;
        try {
            const { data } = await api.post(`/channel-connections/${id}/telegram/webhook`);
            return data;
        } catch (e) {
            error.value = e.response?.data?.message || 'Failed to register Telegram webhook';
            throw e;
        } finally {
            saving.value = false;
        }
    }

    return {
        items,
        loading,
        error,
        saving,
        fetchAll,
        fetchDetail,
        createLine,
        createTelegram,
        createSlack,
        createDiscord,
        updateConnection,
        remove,
        registerTelegramWebhook,
    };
});
