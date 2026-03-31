<template>
  <div class="flex h-screen bg-dark-950">
    <!-- Sidebar -->
    <Sidebar />

    <!-- Main chat area -->
    <main class="flex-1 flex flex-col h-screen">
      <!-- Header -->
      <header class="border-b border-dark-800 bg-dark-900/50 backdrop-blur-sm px-6 py-4 flex items-center justify-between">
        <div>
          <h2 class="text-lg font-semibold text-white">
            {{ chat.currentConversation?.title || 'New Conversation' }}
          </h2>
          <p class="text-xs text-dark-500 mt-0.5">{{ chat.selectedModel }}</p>
        </div>
      </header>

      <!-- Messages -->
      <div ref="messagesContainer" class="flex-1 overflow-y-auto px-6 py-6 space-y-6">
        <!-- Empty state -->
        <div v-if="chat.messages.length === 0 && !chat.isStreaming" class="flex flex-col items-center justify-center h-full text-center">
          <div class="w-20 h-20 bg-dark-800 rounded-2xl flex items-center justify-center mb-6">
            <svg class="w-10 h-10 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z" />
            </svg>
          </div>
          <h3 class="text-xl font-semibold text-white mb-2">How can I help you today?</h3>
          <p class="text-dark-400 max-w-md">
            I can search the web, browse websites, read/write files, run commands, and more.
          </p>

          <!-- Quick prompts -->
          <div class="grid grid-cols-2 gap-3 mt-8 max-w-lg">
            <button
              v-for="prompt in quickPrompts"
              :key="prompt"
              @click="chat.sendMessage(prompt)"
              class="text-left px-4 py-3 bg-dark-800/50 hover:bg-dark-800 border border-dark-700/50 rounded-xl text-sm text-dark-300 hover:text-white transition"
            >
              {{ prompt }}
            </button>
          </div>
        </div>

        <!-- Message list -->
        <template v-for="message in chat.messages" :key="message.id">
          <ToolCallDisplay v-if="message.role === 'tool'" :message="message" />
          <MessageBubble v-else :message="message" />
        </template>

        <!-- Active tool calls (running) -->
        <template v-if="chat.isStreaming">
          <ActiveToolCall
            v-for="tc in runningToolCalls"
            :key="tc.id"
            :toolCall="tc"
          />
        </template>

        <!-- Streaming response -->
        <StreamingMessage
          v-if="chat.isStreaming && (chat.streamingContent || chat.activeToolCalls.length === 0)"
          :content="chat.streamingContent"
        />
      </div>

      <!-- Input -->
      <ChatInput />
    </main>
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted } from 'vue';
import { useChatStore } from '../stores/chat';
import Sidebar from '../components/Sidebar.vue';
import MessageBubble from '../components/MessageBubble.vue';
import StreamingMessage from '../components/StreamingMessage.vue';
import ChatInput from '../components/ChatInput.vue';
import ToolCallDisplay from '../components/ToolCallDisplay.vue';
import ActiveToolCall from '../components/ActiveToolCall.vue';

const chat = useChatStore();
const messagesContainer = ref(null);

const quickPrompts = [
    'Search the web for latest AI news',
    'What is the current date and time?',
    'Write a Python script and save it to a file',
    'Calculate the compound interest on $10,000 at 5% for 10 years',
];

const runningToolCalls = computed(() =>
    chat.activeToolCalls.filter(tc => tc.status === 'running')
);

function scrollToBottom() {
    nextTick(() => {
        const el = messagesContainer.value;
        if (el) {
            el.scrollTop = el.scrollHeight;
        }
    });
}

watch(() => chat.messages.length, scrollToBottom);
watch(() => chat.streamingContent, scrollToBottom);
watch(() => chat.activeToolCalls.length, scrollToBottom);

onMounted(() => {
    chat.fetchConversations();
});
</script>
