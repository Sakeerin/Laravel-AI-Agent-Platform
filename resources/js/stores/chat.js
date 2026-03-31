import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '../api/client';

export const useChatStore = defineStore('chat', () => {
    const conversations = ref([]);
    const currentConversation = ref(null);
    const messages = ref([]);
    const isStreaming = ref(false);
    const streamingContent = ref('');
    const activeToolCalls = ref([]);
    const loadingConversations = ref(false);
    const selectedModel = ref(localStorage.getItem('selected_model') || 'claude-sonnet');

    function setModel(model) {
        selectedModel.value = model;
        localStorage.setItem('selected_model', model);
    }

    async function fetchConversations() {
        loadingConversations.value = true;
        try {
            const { data } = await api.get('/conversations');
            conversations.value = data.data || data;
        } finally {
            loadingConversations.value = false;
        }
    }

    async function createConversation(title = 'New Conversation') {
        const { data } = await api.post('/conversations', {
            title,
            model: selectedModel.value,
        });
        conversations.value.unshift(data);
        return data;
    }

    async function selectConversation(conversation) {
        currentConversation.value = conversation;
        const { data } = await api.get(`/conversations/${conversation.id}`);
        currentConversation.value = data;
        messages.value = data.messages || [];
    }

    async function deleteConversation(id) {
        await api.delete(`/conversations/${id}`);
        conversations.value = conversations.value.filter(c => c.id !== id);
        if (currentConversation.value?.id === id) {
            currentConversation.value = null;
            messages.value = [];
        }
    }

    async function sendMessage(content) {
        if (isStreaming.value) return;

        const userMessage = {
            id: Date.now(),
            role: 'user',
            content,
            created_at: new Date().toISOString(),
        };
        messages.value.push(userMessage);

        isStreaming.value = true;
        streamingContent.value = '';
        activeToolCalls.value = [];

        try {
            const response = await fetch('/api/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'text/event-stream',
                    'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
                },
                body: JSON.stringify({
                    message: content,
                    conversation_id: currentConversation.value?.id || null,
                    model: selectedModel.value,
                    stream: true,
                }),
            });

            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let buffer = '';

            while (true) {
                const { done, value } = await reader.read();
                if (done) break;

                buffer += decoder.decode(value, { stream: true });
                const lines = buffer.split('\n');
                buffer = lines.pop() || '';

                for (const line of lines) {
                    if (!line.startsWith('data: ')) continue;
                    const jsonStr = line.slice(6).trim();
                    if (!jsonStr) continue;

                    try {
                        const event = JSON.parse(jsonStr);
                        handleStreamEvent(event, content);
                    } catch {
                        // skip malformed JSON
                    }
                }
            }
        } catch (err) {
            messages.value.push({
                id: Date.now(),
                role: 'assistant',
                content: `Connection error: ${err.message}`,
                created_at: new Date().toISOString(),
                is_error: true,
            });
        } finally {
            isStreaming.value = false;
            streamingContent.value = '';
            activeToolCalls.value = [];
            fetchConversations();
        }
    }

    function handleStreamEvent(event, originalContent) {
        if (event.type === 'text') {
            streamingContent.value += event.content;
            if (!currentConversation.value && event.conversation_id) {
                const conv = { id: event.conversation_id, title: originalContent.slice(0, 80) };
                currentConversation.value = conv;
                const exists = conversations.value.find(c => c.id === conv.id);
                if (!exists) conversations.value.unshift(conv);
            }
        } else if (event.type === 'tool_start') {
            activeToolCalls.value.push({
                id: event.tool_call_id,
                name: event.tool_name,
                arguments: event.arguments,
                status: 'running',
                result: null,
            });
        } else if (event.type === 'tool_result') {
            const tc = activeToolCalls.value.find(t => t.id === event.tool_call_id);
            if (tc) {
                tc.status = event.success ? 'completed' : 'failed';
                tc.result = event.result;
                tc.duration_ms = event.duration_ms;
            }
            messages.value.push({
                id: `tool-${event.tool_call_id}`,
                role: 'tool',
                tool_name: event.tool_name,
                tool_call_id: event.tool_call_id,
                content: event.result,
                success: event.success,
                duration_ms: event.duration_ms,
                created_at: new Date().toISOString(),
            });
        } else if (event.type === 'done') {
            messages.value.push({
                id: event.message_id,
                role: 'assistant',
                content: streamingContent.value,
                model: selectedModel.value,
                input_tokens: event.input_tokens,
                output_tokens: event.output_tokens,
                tool_calls_count: event.tool_calls_count || 0,
                created_at: new Date().toISOString(),
            });
            streamingContent.value = '';
        } else if (event.type === 'error') {
            messages.value.push({
                id: Date.now(),
                role: 'assistant',
                content: event.content,
                created_at: new Date().toISOString(),
                is_error: true,
            });
        }
    }

    function startNewChat() {
        currentConversation.value = null;
        messages.value = [];
        streamingContent.value = '';
        activeToolCalls.value = [];
    }

    return {
        conversations, currentConversation, messages, isStreaming,
        streamingContent, activeToolCalls, loadingConversations, selectedModel,
        setModel, fetchConversations, createConversation,
        selectConversation, deleteConversation, sendMessage, startNewChat,
    };
});
