<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
      <div
        class="w-full max-w-lg rounded-2xl border border-dark-700 bg-dark-900 shadow-xl overflow-hidden"
        role="dialog"
        aria-labelledby="onboarding-title"
      >
        <div class="p-6 border-b border-dark-800">
          <h2 id="onboarding-title" class="text-lg font-semibold text-white">Welcome to {{ appName }}</h2>
          <p class="text-sm text-dark-400 mt-1">A quick tour so you can get productive fast.</p>
        </div>
        <div class="p-6 space-y-4 text-sm text-dark-300">
          <p>
            <span class="text-primary-400 font-medium">Chat</span> — Talk to the assistant; connect your own API keys under account settings when you add a keys UI.
          </p>
          <p>
            <span class="text-primary-400 font-medium">Skills &amp; Marketplace</span> — Extend the agent with tools and community packages.
          </p>
          <p>
            <span class="text-primary-400 font-medium">Channels</span> — Bridge LINE, Telegram, Slack, or Discord to this same brain.
          </p>
          <p>
            <span class="text-primary-400 font-medium">Memory</span> — Optional long-term recall and persona from the Personalization page.
          </p>
        </div>
        <div class="p-6 border-t border-dark-800 flex justify-end gap-2">
          <button
            type="button"
            class="px-4 py-2 rounded-xl bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium transition disabled:opacity-50"
            :disabled="saving"
            @click="complete"
          >
            {{ saving ? 'Saving…' : 'Get started' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref } from 'vue';
import api from '../api/client';

const appName = import.meta.env.VITE_APP_NAME || 'AI Agent Platform';
const emit = defineEmits(['completed']);
const saving = ref(false);

async function complete() {
    saving.value = true;
    try {
        await api.patch('/agent-settings', { onboarding_completed: true });
        emit('completed');
    } finally {
        saving.value = false;
    }
}
</script>
