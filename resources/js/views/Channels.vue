<template>
  <div class="flex h-screen bg-dark-950">
    <Sidebar />

    <main class="flex-1 flex flex-col h-screen overflow-hidden">
      <header class="border-b border-dark-800 bg-dark-900/50 backdrop-blur-sm px-6 py-4">
        <div>
          <h2 class="text-lg font-semibold text-white">Channels</h2>
          <p class="text-xs text-dark-500 mt-0.5">
            LINE · Telegram · Slack Events API · Discord Interactions — one connection per provider · uses your account AI keys
          </p>
        </div>
      </header>

      <div class="flex-1 overflow-y-auto p-6">
        <div v-if="store.loading" class="flex justify-center py-20">
          <div class="w-8 h-8 border-2 border-primary-500 border-t-transparent rounded-full animate-spin"></div>
        </div>

        <div v-else-if="store.error && !store.items.length" class="bg-red-500/10 border border-red-500/20 rounded-xl p-4 text-red-400 text-sm">
          {{ store.error }}
        </div>

        <div v-else class="max-w-4xl mx-auto space-y-8">
          <p v-if="store.error" class="text-amber-400 text-sm">{{ store.error }}</p>

          <!-- Add forms -->
          <div class="grid gap-6 md:grid-cols-2">
            <div v-if="!hasLine" class="bg-dark-900/50 border border-dark-800 rounded-xl p-5 space-y-4">
              <h3 class="text-sm font-semibold text-white flex items-center gap-2">
                <span class="text-lg leading-none">LINE</span>
                <span class="text-[10px] px-1.5 py-0.5 bg-emerald-500/10 text-emerald-400 rounded-full uppercase">Official Account</span>
              </h3>
              <p class="text-xs text-dark-500 leading-relaxed">
                Use Channel secret &amp; channel access token from LINE Developers. Webhook URL is shown after save (HTTPS required for LINE).
              </p>
              <form class="space-y-3" @submit.prevent="submitLine">
                <input v-model="lineForm.label" type="text" placeholder="Label (optional)"
                  class="w-full px-3 py-2 bg-dark-800 border border-dark-700 rounded-lg text-sm text-white placeholder-dark-500" />
                <input v-model="lineForm.line_channel_secret" type="password" required placeholder="Channel secret"
                  class="w-full px-3 py-2 bg-dark-800 border border-dark-700 rounded-lg text-sm text-white placeholder-dark-500" />
                <input v-model="lineForm.line_channel_access_token" type="password" required placeholder="Channel access token"
                  class="w-full px-3 py-2 bg-dark-800 border border-dark-700 rounded-lg text-sm text-white placeholder-dark-500" />
                <button type="submit" :disabled="store.saving"
                  class="w-full py-2.5 rounded-lg text-sm font-medium bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white transition">
                  Save LINE connection
                </button>
              </form>
            </div>

            <div v-if="!hasTelegram" class="bg-dark-900/50 border border-dark-800 rounded-xl p-5 space-y-4">
              <h3 class="text-sm font-semibold text-white flex items-center gap-2">
                <span class="text-lg leading-none">Telegram</span>
                <span class="text-[10px] px-1.5 py-0.5 bg-sky-500/10 text-sky-400 rounded-full uppercase">Bot API</span>
              </h3>
              <p class="text-xs text-dark-500 leading-relaxed">
                Bot token from @BotFather. After saving, copy the webhook URL &amp; secret, then click &quot;Register webhook&quot;.
              </p>
              <form class="space-y-3" @submit.prevent="submitTelegram">
                <input v-model="telegramForm.label" type="text" placeholder="Label (optional)"
                  class="w-full px-3 py-2 bg-dark-800 border border-dark-700 rounded-lg text-sm text-white placeholder-dark-500" />
                <input v-model="telegramForm.telegram_bot_token" type="password" required placeholder="Bot token"
                  class="w-full px-3 py-2 bg-dark-800 border border-dark-700 rounded-lg text-sm text-white placeholder-dark-500" />
                <button type="submit" :disabled="store.saving"
                  class="w-full py-2.5 rounded-lg text-sm font-medium bg-sky-600 hover:bg-sky-500 disabled:opacity-50 text-white transition">
                  Save Telegram connection
                </button>
              </form>
            </div>

            <div v-if="!hasSlack" class="bg-dark-900/50 border border-dark-800 rounded-xl p-5 space-y-4">
              <h3 class="text-sm font-semibold text-white flex items-center gap-2">
                <span class="text-lg leading-none">Slack</span>
                <span class="text-[10px] px-1.5 py-0.5 bg-violet-500/10 text-violet-400 rounded-full uppercase">Events API</span>
              </h3>
              <p class="text-xs text-dark-500 leading-relaxed">
                Enable Events <code class="text-dark-400">message.*</code> and set Request URL to the webhook below after saving. Bot needs <code class="text-dark-400">chat:write</code> scopes.
              </p>
              <form class="space-y-3" @submit.prevent="submitSlack">
                <input v-model="slackForm.label" type="text" placeholder="Label (optional)"
                  class="w-full px-3 py-2 bg-dark-800 border border-dark-700 rounded-lg text-sm text-white placeholder-dark-500" />
                <input v-model="slackForm.slack_signing_secret" type="password" required placeholder="Signing secret"
                  class="w-full px-3 py-2 bg-dark-800 border border-dark-700 rounded-lg text-sm text-white placeholder-dark-500" />
                <input v-model="slackForm.slack_bot_token" type="password" required placeholder="Bot token (xoxb-...)"
                  class="w-full px-3 py-2 bg-dark-800 border border-dark-700 rounded-lg text-sm text-white placeholder-dark-500" />
                <button type="submit" :disabled="store.saving"
                  class="w-full py-2.5 rounded-lg text-sm font-medium bg-violet-600 hover:bg-violet-500 disabled:opacity-50 text-white transition">
                  Save Slack connection
                </button>
              </form>
            </div>

            <div v-if="!hasDiscord" class="bg-dark-900/50 border border-dark-800 rounded-xl p-5 space-y-4">
              <h3 class="text-sm font-semibold text-white flex items-center gap-2">
                <span class="text-lg leading-none">Discord</span>
                <span class="text-[10px] px-1.5 py-0.5 bg-indigo-500/10 text-indigo-400 rounded-full uppercase">Interactions</span>
              </h3>
              <p class="text-xs text-dark-500 leading-relaxed">
                Paste <strong class="text-dark-300">Application ID</strong>, <strong class="text-dark-300">Public Key</strong> from the Discord app. Set Interactions endpoint URL to the webhook after saving. Requires PHP <code class="text-dark-400">sodium</code> ext. Create a slash command with a <strong class="text-dark-300">string</strong> option (e.g. <code class="text-dark-400">/ask prompt</code>).
              </p>
              <form class="space-y-3" @submit.prevent="submitDiscord">
                <input v-model="discordForm.label" type="text" placeholder="Label (optional)"
                  class="w-full px-3 py-2 bg-dark-800 border border-dark-700 rounded-lg text-sm text-white placeholder-dark-500" />
                <input v-model="discordForm.discord_application_id" type="text" required placeholder="Application ID"
                  class="w-full px-3 py-2 bg-dark-800 border border-dark-700 rounded-lg text-sm text-white placeholder-dark-500" />
                <input v-model="discordForm.discord_public_key" type="text" required placeholder="Public key (hex)"
                  class="w-full px-3 py-2 bg-dark-800 border border-dark-700 rounded-lg text-sm text-white placeholder-dark-500 font-mono text-xs" />
                <input v-model="discordForm.discord_bot_token" type="password" placeholder="Bot token (optional)"
                  class="w-full px-3 py-2 bg-dark-800 border border-dark-700 rounded-lg text-sm text-white placeholder-dark-500" />
                <button type="submit" :disabled="store.saving"
                  class="w-full py-2.5 rounded-lg text-sm font-medium bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white transition">
                  Save Discord connection
                </button>
              </form>
            </div>
          </div>

          <!-- Existing connections -->
          <div class="space-y-4">
            <h3 class="text-xs font-semibold text-dark-500 uppercase tracking-wider">Active connections</h3>

            <div
              v-for="conn in store.items"
              :key="conn.id"
              class="bg-dark-900/50 border border-dark-800 rounded-xl p-4 space-y-3"
            >
              <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                  <div class="flex items-center gap-2">
                    <span class="font-medium text-white capitalize">{{ conn.provider }}</span>
                    <span v-if="conn.label" class="text-dark-500 text-sm">· {{ conn.label }}</span>
                    <span
                      class="text-[10px] px-1.5 py-0.5 rounded-full font-medium"
                      :class="conn.is_enabled ? 'bg-emerald-500/10 text-emerald-400' : 'bg-dark-700 text-dark-400'"
                    >
                      {{ conn.is_enabled ? 'enabled' : 'disabled' }}
                    </span>
                  </div>
                  <div class="mt-2 flex flex-wrap items-center gap-2">
                    <code class="text-[11px] text-dark-300 bg-dark-800 px-2 py-1 rounded max-w-full truncate block">{{ conn.webhook_url }}</code>
                    <button type="button" class="text-xs text-primary-400 hover:text-primary-300" @click="copy(conn.webhook_url)">Copy URL</button>
                  </div>
                </div>
                <div class="flex items-center gap-2">
                  <label class="flex items-center gap-2 text-xs text-dark-400 cursor-pointer select-none">
                    <input
                      type="checkbox"
                      :checked="conn.is_enabled"
                      @change="toggleEnabled(conn, $event.target.checked)"
                      class="rounded border-dark-600 bg-dark-800 text-primary-500"
                    />
                    On
                  </label>
                  <button
                    type="button"
                    class="text-xs text-red-400 hover:text-red-300 px-2 py-1 rounded-lg hover:bg-red-500/10"
                    @click="confirmRemove(conn)"
                  >
                    Delete
                  </button>
                </div>
              </div>

              <div v-if="conn.provider === 'telegram'" class="flex flex-wrap gap-2 pt-2 border-t border-dark-800/60">
                <button
                  type="button"
                  :disabled="store.saving || !conn.is_enabled"
                  class="px-3 py-1.5 rounded-lg text-xs font-medium bg-sky-600/80 hover:bg-sky-600 disabled:opacity-40 text-white transition"
                  @click="registerTg(conn)"
                >
                  Register webhook with Telegram
                </button>
                <button
                  type="button"
                  class="px-3 py-1.5 rounded-lg text-xs font-medium bg-dark-800 text-dark-300 hover:text-white border border-dark-700"
                  @click="loadSecrets(conn)"
                >
                  Show verify secret
                </button>
              </div>

              <p v-if="conn.provider === 'telegram' && webhookOkFor === conn.id" class="text-xs text-emerald-400 pt-2">
                Webhook registered with Telegram.
              </p>

              <div v-if="detailById[conn.id]?.telegram_webhook_secret" class="text-xs text-dark-500">
                <span class="text-dark-400">Secret token (sent as </span>
                <code class="text-dark-300">X-Telegram-Bot-Api-Secret-Token</code>
                <span class="text-dark-400">):</span>
                <code class="block text-dark-200 mt-1 break-all bg-dark-800 p-2 rounded-lg">{{ detailById[conn.id].telegram_webhook_secret }}</code>
                <button type="button" class="mt-1 text-primary-400 hover:text-primary-300" @click="copy(detailById[conn.id].telegram_webhook_secret)">Copy</button>
              </div>

              <p v-if="conn.provider === 'slack'" class="text-[11px] text-dark-500 pt-2 border-t border-dark-800/60 leading-relaxed">
                In Slack API: Event Subscriptions → Request URL = webhook above. Subscribe to message events in channels or DMs your bot is in.
              </p>

              <p v-if="conn.provider === 'discord'" class="text-[11px] text-dark-500 pt-2 border-t border-dark-800/60 leading-relaxed">
                Developer Portal → Interactions → URL = webhook above. Use a slash command with a string option; the bot defers the response then posts the AI reply (requires <code class="text-dark-400">sodium</code> on the server).
              </p>
            </div>

            <p v-if="store.items.length === 0" class="text-center text-dark-500 text-sm py-8">
              No connections yet. Add a provider above.
            </p>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { computed, reactive, ref, onMounted } from 'vue';
import { useChannelsStore } from '../stores/channels';
import Sidebar from '../components/Sidebar.vue';

const store = useChannelsStore();
const lineForm = reactive({ label: '', line_channel_secret: '', line_channel_access_token: '' });
const telegramForm = reactive({ label: '', telegram_bot_token: '' });
const slackForm = reactive({ label: '', slack_signing_secret: '', slack_bot_token: '' });
const discordForm = reactive({
    label: '',
    discord_public_key: '',
    discord_application_id: '',
    discord_bot_token: '',
});
const detailById = reactive({});
const webhookOkFor = ref(null);

const hasLine = computed(() => store.items.some(c => c.provider === 'line'));
const hasTelegram = computed(() => store.items.some(c => c.provider === 'telegram'));
const hasSlack = computed(() => store.items.some(c => c.provider === 'slack'));
const hasDiscord = computed(() => store.items.some(c => c.provider === 'discord'));

async function copy(text) {
    try {
        await navigator.clipboard.writeText(text);
    } catch {
        window.prompt('Copy:', text);
    }
}

async function submitLine() {
    store.error = null;
    try {
        const detail = await store.createLine({ ...lineForm });
        Object.assign(lineForm, { label: '', line_channel_secret: '', line_channel_access_token: '' });
        if (detail?.id) {
            detailById[detail.id] = detail;
        }
    } catch {
        /* store sets error */
    }
}

async function submitTelegram() {
    store.error = null;
    try {
        const detail = await store.createTelegram({ ...telegramForm });
        Object.assign(telegramForm, { label: '', telegram_bot_token: '' });
        if (detail?.id) {
            detailById[detail.id] = detail;
        }
    } catch {
        /* store sets error */
    }
}

async function submitSlack() {
    store.error = null;
    try {
        const detail = await store.createSlack({ ...slackForm });
        Object.assign(slackForm, { label: '', slack_signing_secret: '', slack_bot_token: '' });
        if (detail?.id) {
            detailById[detail.id] = detail;
        }
    } catch {
        /* */
    }
}

async function submitDiscord() {
    store.error = null;
    try {
        const detail = await store.createDiscord({ ...discordForm });
        Object.assign(discordForm, {
            label: '',
            discord_public_key: '',
            discord_application_id: '',
            discord_bot_token: '',
        });
        if (detail?.id) {
            detailById[detail.id] = detail;
        }
    } catch {
        /* */
    }
}

async function toggleEnabled(conn, on) {
    store.error = null;
    try {
        await store.updateConnection(conn.id, { is_enabled: on });
    } catch {
        await store.fetchAll();
    }
}

async function confirmRemove(conn) {
    if (!confirm(`Remove ${conn.provider} connection? Linked chat threads and their conversations will be deleted.`)) {
        return;
    }
    store.error = null;
    try {
        await store.remove(conn.id);
        delete detailById[conn.id];
    } catch {
        /* */
    }
}

async function registerTg(conn) {
    store.error = null;
    webhookOkFor.value = null;
    try {
        await store.registerTelegramWebhook(conn.id);
        webhookOkFor.value = conn.id;
        window.setTimeout(() => {
            if (webhookOkFor.value === conn.id) {
                webhookOkFor.value = null;
            }
        }, 5000);
    } catch {
        /* */
    }
}

async function loadSecrets(conn) {
    try {
        const d = await store.fetchDetail(conn.id);
        detailById[conn.id] = d;
    } catch {
        /* */
    }
}

onMounted(() => {
    store.fetchAll();
});
</script>
