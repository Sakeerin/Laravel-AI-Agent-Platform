<template>
  <div class="border-t border-dark-800 bg-dark-900/80 backdrop-blur-sm p-4">
    <!-- Model selector -->
    <div class="flex items-center gap-2 mb-3">
      <label class="text-xs text-dark-500">Model:</label>
      <select
        :value="chat.selectedModel"
        @change="chat.setModel(($event.target).value)"
        class="bg-dark-800 border border-dark-700 rounded-lg px-2.5 py-1.5 text-sm text-dark-200 focus:outline-none focus:ring-1 focus:ring-primary-500"
      >
        <optgroup label="Anthropic">
          <option value="claude-sonnet">Claude Sonnet</option>
          <option value="claude-haiku">Claude Haiku</option>
          <option value="claude-opus">Claude Opus</option>
        </optgroup>
        <optgroup label="OpenAI">
          <option value="gpt-4o">GPT-4o</option>
          <option value="gpt-4o-mini">GPT-4o Mini</option>
        </optgroup>
        <optgroup label="Local">
          <option value="llama3.1">Llama 3.1 (Ollama)</option>
          <option value="mistral">Mistral (Ollama)</option>
        </optgroup>
      </select>
    </div>

    <!-- Input area -->
    <form @submit.prevent="handleSend" class="flex items-end gap-3">
      <div class="flex-1 relative">
        <textarea
          ref="textareaRef"
          v-model="input"
          @keydown.enter.exact="handleEnterKey"
          @input="autoResize"
          :disabled="chat.isStreaming"
          rows="1"
          class="w-full resize-none bg-dark-800 border border-dark-700 rounded-xl px-4 py-3 pr-12 text-white placeholder-dark-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition disabled:opacity-50"
          :class="{ 'max-h-40': true }"
          placeholder="Send a message..."
          style="min-height: 48px"
        ></textarea>
      </div>

      <button
        type="submit"
        :disabled="!input.trim() || chat.isStreaming"
        class="shrink-0 p-3 bg-primary-600 hover:bg-primary-700 text-white rounded-xl transition disabled:opacity-50 disabled:cursor-not-allowed"
      >
        <svg v-if="!chat.isStreaming" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
        </svg>
        <div v-else class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
      </button>
    </form>
  </div>
</template>

<script setup>
import { ref, nextTick } from 'vue';
import { useChatStore } from '../stores/chat';

const chat = useChatStore();
const input = ref('');
const textareaRef = ref(null);

function handleEnterKey(e) {
    if (!e.shiftKey) {
        e.preventDefault();
        handleSend();
    }
}

async function handleSend() {
    const message = input.value.trim();
    if (!message || chat.isStreaming) return;

    input.value = '';
    await nextTick();
    autoResize();
    chat.sendMessage(message);
}

function autoResize() {
    const el = textareaRef.value;
    if (!el) return;
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 160) + 'px';
}
</script>
