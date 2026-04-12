<template>
  <div class="flex h-screen bg-dark-950">
    <Sidebar />

    <main class="flex-1 flex flex-col h-screen overflow-hidden">
      <header class="border-b border-dark-800 bg-dark-900/50 backdrop-blur-sm px-6 py-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h2 class="text-lg font-semibold text-white">Skill marketplace</h2>
            <p class="text-xs text-dark-500 mt-0.5">Browse, install, and add webhook skills to your agent</p>
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <input
              v-model="store.query"
              type="search"
              placeholder="Search packages…"
              class="rounded-lg bg-dark-800 border border-dark-700 text-sm text-white px-3 py-2 w-48 placeholder:text-dark-500 focus:outline-none focus:ring-2 focus:ring-primary-500"
              @keyup.enter="store.fetchPackages()"
            />
            <input
              v-model="store.category"
              type="text"
              placeholder="Category"
              class="rounded-lg bg-dark-800 border border-dark-700 text-sm text-white px-3 py-2 w-32 placeholder:text-dark-500 focus:outline-none focus:ring-2 focus:ring-primary-500"
              @keyup.enter="store.fetchPackages()"
            />
            <button
              type="button"
              class="rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium px-4 py-2"
              @click="store.fetchPackages()"
            >
              Search
            </button>
            <router-link
              to="/skills"
              class="rounded-lg border border-dark-600 text-dark-300 hover:text-white text-sm px-4 py-2"
            >
              Manage skills
            </router-link>
          </div>
        </div>
      </header>

      <div class="flex-1 overflow-y-auto p-6">
        <div v-if="store.loading" class="flex justify-center py-20">
          <div class="w-8 h-8 border-2 border-primary-500 border-t-transparent rounded-full animate-spin"></div>
        </div>

        <div v-else-if="store.error" class="bg-red-500/10 border border-red-500/20 rounded-xl p-4 text-red-400 text-sm max-w-3xl mx-auto">
          {{ store.error }}
        </div>

        <div v-else class="max-w-5xl mx-auto space-y-10">
          <section>
            <h3 class="text-sm font-semibold text-dark-400 uppercase tracking-wider mb-3">Featured &amp; catalog</h3>
            <div class="grid gap-3 sm:grid-cols-2">
              <div
                v-for="pkg in store.packages"
                :key="pkg.slug"
                class="bg-dark-900/50 border border-dark-800 rounded-xl p-4 flex flex-col gap-3"
              >
                <div class="flex items-start gap-3">
                  <span class="text-2xl leading-none">{{ pkg.icon || '🔌' }}</span>
                  <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 flex-wrap">
                      <h4 class="font-medium text-white">{{ pkg.title }}</h4>
                      <span v-if="pkg.is_featured" class="text-[10px] px-1.5 py-0.5 bg-primary-500/20 text-primary-300 rounded-full">Featured</span>
                      <span v-if="pkg.is_premium" class="text-[10px] px-1.5 py-0.5 bg-amber-500/10 text-amber-400 rounded-full">Premium</span>
                    </div>
                    <p class="text-sm text-dark-400 mt-1 leading-relaxed">{{ pkg.description }}</p>
                    <p class="text-xs text-dark-500 mt-2">
                      <span class="text-dark-400">{{ pkg.category }}</span>
                      · v{{ pkg.version }}
                      <span v-if="pkg.tool_name" class="font-mono text-primary-400/80"> · {{ pkg.tool_name }}</span>
                    </p>
                  </div>
                </div>
                <div class="flex items-center gap-2 pt-2 border-t border-dark-800/60">
                  <template v-if="store.installedSlugs.has(pkg.slug)">
                    <button
                      type="button"
                      class="text-sm rounded-lg bg-dark-800 text-red-300 hover:bg-red-500/10 px-3 py-1.5 border border-dark-700"
                      @click="uninstall(pkg.slug)"
                    >
                      Uninstall
                    </button>
                    <span class="text-xs text-dark-500">Installed</span>
                  </template>
                  <button
                    v-else
                    type="button"
                    class="text-sm rounded-lg bg-primary-600 hover:bg-primary-700 text-white px-3 py-1.5"
                    @click="install(pkg.slug)"
                  >
                    Install
                  </button>
                </div>
              </div>
            </div>

            <p v-if="store.packages.length === 0" class="text-center text-dark-500 text-sm py-12">
              No packages match your filters. Clear search or run
              <code class="bg-dark-800 px-2 py-0.5 rounded text-dark-300">php artisan db:seed --class=SkillPackageSeeder</code>
            </p>
          </section>

          <section class="border-t border-dark-800 pt-10">
            <h3 class="text-sm font-semibold text-dark-400 uppercase tracking-wider mb-3">Custom webhook skill (no-code)</h3>
            <p class="text-xs text-dark-500 mb-4 max-w-2xl">
              Define a JSON tool schema and a secure HTTP endpoint. In production, set
              <code class="bg-dark-800 px-1 rounded">SKILLS_HTTP_WEBHOOK_ALLOWED_HOSTS</code>
              to restrict callable hostnames.
            </p>

            <form class="bg-dark-900/40 border border-dark-800 rounded-xl p-4 space-y-3 max-w-xl" @submit.prevent="submitCustom">
              <div class="grid gap-3 sm:grid-cols-2">
                <label class="block text-xs text-dark-400">
                  Tool name (snake_case)
                  <input v-model="custom.name" required class="mt-1 w-full rounded-lg bg-dark-800 border border-dark-700 text-sm text-white px-3 py-2" placeholder="my_api_action" />
                </label>
                <label class="block text-xs text-dark-400">
                  Display name
                  <input v-model="custom.display_name" required class="mt-1 w-full rounded-lg bg-dark-800 border border-dark-700 text-sm text-white px-3 py-2" />
                </label>
              </div>
              <label class="block text-xs text-dark-400">
                Description
                <textarea v-model="custom.description" required rows="2" class="mt-1 w-full rounded-lg bg-dark-800 border border-dark-700 text-sm text-white px-3 py-2"></textarea>
              </label>
              <div class="grid gap-3 sm:grid-cols-2">
                <label class="block text-xs text-dark-400">
                  Webhook URL
                  <input v-model="custom.endpoint" required type="url" class="mt-1 w-full rounded-lg bg-dark-800 border border-dark-700 text-sm text-white px-3 py-2" />
                </label>
                <label class="block text-xs text-dark-400">
                  Method
                  <select v-model="custom.method" class="mt-1 w-full rounded-lg bg-dark-800 border border-dark-700 text-sm text-white px-3 py-2">
                    <option value="POST">POST</option>
                    <option value="GET">GET</option>
                    <option value="PUT">PUT</option>
                    <option value="PATCH">PATCH</option>
                  </select>
                </label>
              </div>
              <label class="block text-xs text-dark-400">
                Parameters JSON schema (object)
                <textarea v-model="custom.parametersJson" required rows="6" class="mt-1 w-full rounded-lg bg-dark-800 border border-dark-700 text-sm font-mono text-dark-200 px-3 py-2"></textarea>
              </label>
              <p v-if="customError" class="text-xs text-red-400">{{ customError }}</p>
              <button type="submit" class="rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium px-4 py-2" :disabled="customSubmitting">
                {{ customSubmitting ? 'Saving…' : 'Create webhook skill' }}
              </button>
            </form>
          </section>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue';
import { useMarketplaceStore } from '../stores/marketplace';
import api from '../api/client';
import Sidebar from '../components/Sidebar.vue';

const store = useMarketplaceStore();
const customError = ref(null);
const customSubmitting = ref(false);

const defaultSchema = `{
  "type": "object",
  "properties": {
    "message": { "type": "string", "description": "Payload for your webhook" }
  },
  "required": ["message"]
}`;

const custom = reactive({
    name: '',
    display_name: '',
    description: '',
    category: 'custom',
    endpoint: '',
    method: 'POST',
    parametersJson: defaultSchema,
});

onMounted(async () => {
    await Promise.all([store.fetchPackages(), store.fetchMyInstalls()]);
});

async function install(slug) {
    try {
        await store.installPackage(slug);
    } catch (e) {
        store.error = e.response?.data?.message || e.message || 'Install failed';
    }
}

async function uninstall(slug) {
    try {
        await store.uninstallPackage(slug);
    } catch (e) {
        store.error = e.response?.data?.message || e.message || 'Uninstall failed';
    }
}

async function submitCustom() {
    customError.value = null;
    let parameters_schema;
    try {
        parameters_schema = JSON.parse(custom.parametersJson);
    } catch {
        customError.value = 'Parameters must be valid JSON.';
        return;
    }

    const manifest = {
        schema_version: 1,
        name: custom.name.trim(),
        display_name: custom.display_name.trim(),
        description: custom.description.trim(),
        category: custom.category.trim() || 'custom',
        version: '1.0.0',
        parameters_schema,
        execution: {
            type: 'http_webhook',
            endpoint: custom.endpoint.trim(),
            method: custom.method,
            headers: {},
        },
    };

    customSubmitting.value = true;
    try {
        await api.post('/skills/custom', { manifest });
        custom.name = '';
        custom.display_name = '';
        custom.description = '';
        custom.endpoint = '';
        custom.parametersJson = defaultSchema;
        await store.fetchPackages();
    } catch (e) {
        const msg = e.response?.data?.message;
        const err = e.response?.data?.errors;
        customError.value = msg || (err ? JSON.stringify(err) : e.message || 'Request failed');
    } finally {
        customSubmitting.value = false;
    }
}
</script>
