<template>
  <div class="flex h-screen bg-dark-950">
    <Sidebar />

    <main class="flex-1 flex flex-col h-screen overflow-hidden">
      <header class="border-b border-dark-800 bg-dark-900/50 backdrop-blur-sm px-6 py-4">
        <h2 class="text-lg font-semibold text-white">Memory &amp; Personalization</h2>
        <p class="text-xs text-dark-500 mt-0.5">Long-term memory, persona, context limits, and gentle reminders</p>
      </header>

      <div class="border-b border-dark-800 px-6 flex gap-1 overflow-x-auto">
        <button
          v-for="t in tabs"
          :key="t.id"
          type="button"
          class="px-3 py-2 text-xs font-medium rounded-t-lg transition shrink-0"
          :class="tab === t.id ? 'bg-dark-800 text-white' : 'text-dark-500 hover:text-dark-300'"
          @click="tab = t.id"
        >
          {{ t.label }}
        </button>
      </div>

      <div class="flex-1 overflow-y-auto p-6">
        <div v-if="store.error" class="mb-4 bg-amber-500/10 border border-amber-500/20 rounded-xl p-3 text-amber-400 text-sm">
          {{ store.error }}
        </div>

        <!-- Settings -->
        <div v-show="tab === 'settings'" class="max-w-3xl mx-auto space-y-6">
          <div v-if="!store.settings && store.loading" class="flex justify-center py-16">
            <div class="w-8 h-8 border-2 border-primary-500 border-t-transparent rounded-full animate-spin"></div>
          </div>
          <form v-else-if="store.settings" class="space-y-5" @submit.prevent="saveAll">
            <div>
              <label class="block text-xs font-medium text-dark-400 mb-1">Persona / system style</label>
              <textarea
                v-model="form.persona"
                rows="4"
                placeholder="Optional instructions that shape how you want the assistant to behave (tone, expertise, constraints)…"
                class="w-full px-3 py-2 bg-dark-800 border border-dark-700 rounded-lg text-sm text-white placeholder-dark-600"
              ></textarea>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
              <label class="flex items-center gap-2 text-sm text-dark-300 cursor-pointer">
                <input v-model="form.memory_enabled" type="checkbox" class="rounded border-dark-600 bg-dark-800 text-primary-500" />
                Recall memories in chat
              </label>
              <label class="flex items-center gap-2 text-sm text-dark-300 cursor-pointer">
                <input v-model="form.memory_auto_extract" type="checkbox" class="rounded border-dark-600 bg-dark-800 text-primary-500" />
                Auto-extract from conversations
              </label>
              <label class="flex items-center gap-2 text-sm text-dark-300 cursor-pointer">
                <input v-model="form.heartbeat_enabled" type="checkbox" class="rounded border-dark-600 bg-dark-800 text-primary-500" />
                Hourly “heartbeat” reminders (needs queue + scheduler)
              </label>
            </div>

            <div class="grid sm:grid-cols-3 gap-4">
              <div>
                <label class="block text-xs text-dark-500 mb-1">Memory top K</label>
                <input v-model.number="form.memory_top_k" type="number" min="1" max="20"
                  class="w-full px-3 py-2 bg-dark-800 border border-dark-700 rounded-lg text-sm text-white" />
              </div>
              <div>
                <label class="block text-xs text-dark-500 mb-1">Min similarity (0–1)</label>
                <input v-model.number="form.memory_min_score" type="number" step="0.05" min="0" max="1"
                  class="w-full px-3 py-2 bg-dark-800 border border-dark-700 rounded-lg text-sm text-white" />
              </div>
              <div>
                <label class="block text-xs text-dark-500 mb-1">Max messages in context</label>
                <input v-model.number="form.context_max_messages" type="number" min="4" max="200"
                  class="w-full px-3 py-2 bg-dark-800 border border-dark-700 rounded-lg text-sm text-white" />
              </div>
            </div>

            <div>
              <label class="block text-xs text-dark-500 mb-1">Embedding backend</label>
              <select v-model="form.embedding_backend"
                class="w-full px-3 py-2 bg-dark-800 border border-dark-700 rounded-lg text-sm text-white">
                <option value="openai:text-embedding-3-small">OpenAI text-embedding-3-small</option>
                <option value="openai:text-embedding-3-large">OpenAI text-embedding-3-large</option>
                <option value="ollama:nomic-embed-text">Ollama nomic-embed-text</option>
                <option value="ollama:mxbai-embed-large">Ollama mxbai-embed-large</option>
              </select>
              <p class="text-[11px] text-dark-600 mt-1">Vectors are stored in MySQL as JSON; cosine search runs in the app (pgvector-ready later).</p>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs text-dark-500 mb-1">Extraction model</label>
                <input v-model="form.extraction_model" type="text"
                  class="w-full px-3 py-2 bg-dark-800 border border-dark-700 rounded-lg text-sm text-white font-mono" />
              </div>
              <div>
                <label class="block text-xs text-dark-500 mb-1">Heartbeat model</label>
                <input v-model="form.heartbeat_model" type="text"
                  class="w-full px-3 py-2 bg-dark-800 border border-dark-700 rounded-lg text-sm text-white font-mono" />
              </div>
            </div>

            <button type="submit" :disabled="store.saving"
              class="px-5 py-2.5 rounded-lg text-sm font-medium bg-primary-600 hover:bg-primary-500 disabled:opacity-50 text-white transition">
              Save settings
            </button>
          </form>
        </div>

        <!-- Memories -->
        <div v-show="tab === 'memories'" class="max-w-3xl mx-auto space-y-4">
          <form class="flex flex-wrap gap-2" @submit.prevent="addMem">
            <input v-model="newMemory" type="text" placeholder="Add a memory manually…"
              class="flex-1 min-w-[200px] px-3 py-2 bg-dark-800 border border-dark-700 rounded-lg text-sm text-white placeholder-dark-600" />
            <button type="submit" :disabled="store.saving || !newMemory.trim()"
              class="px-4 py-2 rounded-lg text-sm font-medium bg-dark-800 border border-dark-700 text-white hover:bg-dark-700 disabled:opacity-40">
              Add
            </button>
          </form>

          <div v-if="store.loading && !store.memories?.data?.length" class="flex justify-center py-16">
            <div class="w-8 h-8 border-2 border-primary-500 border-t-transparent rounded-full animate-spin"></div>
          </div>

          <div v-else class="space-y-2">
            <div
              v-for="m in store.memories.data || []"
              :key="m.id"
              class="flex items-start gap-3 bg-dark-900/50 border border-dark-800 rounded-xl p-3"
            >
              <p class="flex-1 text-sm text-dark-200 leading-relaxed">{{ m.content }}</p>
              <span class="text-[10px] uppercase text-dark-600 shrink-0">{{ m.source }}</span>
              <button type="button" class="text-xs text-red-400 hover:text-red-300 shrink-0" @click="delMem(m.id)">Delete</button>
            </div>
            <p v-if="!(store.memories.data || []).length" class="text-center text-dark-500 text-sm py-8">No memories yet. Chat with auto-extract on, or add manually.</p>
          </div>

          <div v-if="(store.memories.last_page || 1) > 1" class="flex justify-center gap-2 pt-4">
            <button
              type="button"
              class="text-xs text-dark-400 hover:text-white"
              :disabled="(store.memories.current_page || 1) <= 1"
              @click="store.fetchMemories((store.memories.current_page || 1) - 1)"
            >Previous</button>
            <span class="text-xs text-dark-600">{{ store.memories.current_page }} / {{ store.memories.last_page }}</span>
            <button
              type="button"
              class="text-xs text-dark-400 hover:text-white"
              :disabled="(store.memories.current_page || 1) >= (store.memories.last_page || 1)"
              @click="store.fetchMemories((store.memories.current_page || 1) + 1)"
            >Next</button>
          </div>
        </div>

        <!-- Reminders -->
        <div v-show="tab === 'reminders'" class="max-w-3xl mx-auto space-y-3">
          <p class="text-xs text-dark-500">Generated from memory when heartbeat is enabled (requires <code class="text-dark-400">php artisan schedule:work</code> or cron).</p>
          <div
            v-for="r in store.reminders"
            :key="r.id"
            class="bg-dark-900/50 border border-dark-800 rounded-xl p-4"
            :class="r.read_at ? 'opacity-50' : ''"
          >
            <div class="flex justify-between gap-2">
              <h4 class="font-medium text-white text-sm">{{ r.title }}</h4>
              <button
                v-if="!r.read_at"
                type="button"
                class="text-xs text-primary-400 hover:text-primary-300 shrink-0"
                @click="store.ackReminder(r.id)"
              >Dismiss</button>
            </div>
            <p v-if="r.body" class="text-sm text-dark-400 mt-1">{{ r.body }}</p>
            <p class="text-[10px] text-dark-600 mt-2">{{ formatTime(r.created_at) }}</p>
          </div>
          <p v-if="!store.reminders.length" class="text-center text-dark-500 text-sm py-8">No reminders yet.</p>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { reactive, ref, watch, onMounted } from 'vue';
import { usePersonalizationStore } from '../stores/personalization';
import Sidebar from '../components/Sidebar.vue';

const store = usePersonalizationStore();
const tab = ref('settings');
const newMemory = ref('');

const tabs = [
    { id: 'settings', label: 'Persona & memory' },
    { id: 'memories', label: 'Memories' },
    { id: 'reminders', label: 'Reminders' },
];

const form = reactive({
    persona: '',
    memory_enabled: true,
    memory_auto_extract: true,
    memory_top_k: 5,
    memory_min_score: 0.3,
    context_max_messages: 50,
    heartbeat_enabled: false,
    embedding_backend: 'openai:text-embedding-3-small',
    extraction_model: 'gpt-4o-mini',
    heartbeat_model: 'gpt-4o-mini',
});

watch(() => store.settings, (s) => {
    if (!s) return;
    Object.assign(form, {
        persona: s.persona ?? '',
        memory_enabled: !!s.memory_enabled,
        memory_auto_extract: !!s.memory_auto_extract,
        memory_top_k: s.memory_top_k ?? 5,
        memory_min_score: s.memory_min_score ?? 0.3,
        context_max_messages: s.context_max_messages ?? 50,
        heartbeat_enabled: !!s.heartbeat_enabled,
        embedding_backend: s.embedding_backend || 'openai:text-embedding-3-small',
        extraction_model: s.extraction_model || 'gpt-4o-mini',
        heartbeat_model: s.heartbeat_model || 'gpt-4o-mini',
    });
}, { immediate: true });

async function saveAll() {
    store.error = null;
    try {
        await store.saveSettings({ ...form });
    } catch {
        /* */
    }
}

async function addMem() {
    const t = newMemory.value.trim();
    if (!t) return;
    store.error = null;
    try {
        await store.addMemory(t);
        newMemory.value = '';
    } catch {
        /* */
    }
}

async function delMem(id) {
    if (!confirm('Delete this memory?')) return;
    store.error = null;
    try {
        await store.removeMemory(id);
    } catch {
        /* */
    }
}

function formatTime(d) {
    return d ? new Date(d).toLocaleString() : '';
}

onMounted(async () => {
    await store.fetchSettings();
    await store.fetchMemories(1);
    await store.fetchReminders();
});
</script>
