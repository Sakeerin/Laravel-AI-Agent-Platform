<template>
  <div class="flex h-screen bg-dark-950">
    <Sidebar />

    <main class="flex-1 flex flex-col h-screen overflow-hidden">
      <header class="border-b border-dark-800 bg-dark-900/50 backdrop-blur-sm px-6 py-4">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-lg font-semibold text-white">Skills & Tools</h2>
            <p class="text-xs text-dark-500 mt-0.5">
              {{ skills.enabledCount }} of {{ skills.skills.length }} skills enabled
            </p>
          </div>
        </div>
      </header>

      <div class="flex-1 overflow-y-auto p-6">
        <!-- Loading -->
        <div v-if="skills.loading" class="flex justify-center py-20">
          <div class="w-8 h-8 border-2 border-primary-500 border-t-transparent rounded-full animate-spin"></div>
        </div>

        <!-- Error -->
        <div v-else-if="skills.error" class="bg-red-500/10 border border-red-500/20 rounded-xl p-4 text-red-400 text-sm">
          {{ skills.error }}
        </div>

        <!-- Skills by category -->
        <div v-else class="space-y-8 max-w-4xl mx-auto">
          <div v-for="(categorySkills, category) in skills.categories" :key="category">
            <h3 class="text-sm font-semibold text-dark-400 uppercase tracking-wider mb-3 flex items-center gap-2">
              <component :is="getCategoryIcon(category)" class="w-4 h-4" />
              {{ formatCategory(category) }}
            </h3>

            <div class="grid gap-3">
              <div
                v-for="skill in categorySkills"
                :key="skill.id"
                class="bg-dark-900/50 border border-dark-800 rounded-xl p-4 hover:border-dark-700 transition"
              >
                <div class="flex items-start justify-between gap-4">
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                      <h4 class="font-medium text-white">{{ skill.display_name }}</h4>
                      <span v-if="skill.is_system"
                        class="text-[10px] px-1.5 py-0.5 bg-dark-700 text-dark-400 rounded-full uppercase tracking-wider">
                        System
                      </span>
                      <span v-if="skill.requires_approval"
                        class="text-[10px] px-1.5 py-0.5 bg-amber-500/10 text-amber-400 rounded-full uppercase tracking-wider">
                        Approval
                      </span>
                    </div>
                    <p class="text-sm text-dark-400 mt-1 leading-relaxed">{{ skill.description }}</p>

                    <!-- Parameters -->
                    <button
                      @click="toggleParams(skill.id)"
                      class="mt-2 text-xs text-primary-400 hover:text-primary-300 flex items-center gap-1"
                    >
                      <svg class="w-3 h-3 transition-transform" :class="expandedParams[skill.id] ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                      </svg>
                      Parameters
                    </button>

                    <div v-if="expandedParams[skill.id] && skill.parameters_schema?.properties" class="mt-2 space-y-1">
                      <div
                        v-for="(param, paramName) in skill.parameters_schema.properties"
                        :key="paramName"
                        class="flex items-start gap-2 text-xs"
                      >
                        <code class="text-primary-400 bg-dark-800 px-1.5 py-0.5 rounded font-mono shrink-0">{{ paramName }}</code>
                        <span class="text-dark-500">{{ param.type }}</span>
                        <span v-if="isRequired(skill, paramName)" class="text-red-400">*</span>
                        <span class="text-dark-400">{{ param.description }}</span>
                      </div>
                    </div>
                  </div>

                  <!-- Toggle -->
                  <button
                    @click="skills.toggleSkill(skill)"
                    class="shrink-0 relative w-11 h-6 rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 focus:ring-offset-dark-950"
                    :class="skill.is_enabled ? 'bg-primary-600' : 'bg-dark-700'"
                  >
                    <span
                      class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform"
                      :class="skill.is_enabled ? 'translate-x-5' : 'translate-x-0'"
                    />
                  </button>
                </div>

                <div class="flex items-center gap-3 mt-3 pt-3 border-t border-dark-800/50 text-xs text-dark-500">
                  <span>Timeout: {{ skill.timeout_seconds }}s</span>
                  <span class="w-1 h-1 bg-dark-700 rounded-full"></span>
                  <span>Category: {{ skill.category }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Empty state -->
          <div v-if="skills.skills.length === 0" class="text-center py-20">
            <div class="w-16 h-16 bg-dark-800 rounded-2xl flex items-center justify-center mx-auto mb-4">
              <svg class="w-8 h-8 text-dark-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75a4.5 4.5 0 01-4.884 4.484c-1.076-.091-2.264.071-2.95.904l-7.152 8.684a2.548 2.548 0 11-3.586-3.586l8.684-7.152c.833-.686.995-1.874.904-2.95a4.5 4.5 0 016.336-4.486l-3.276 3.276a3.004 3.004 0 002.25 2.25l3.276-3.276c.256.565.398 1.192.398 1.852z" />
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-white mb-2">No Skills Found</h3>
            <p class="text-dark-400 text-sm">Run <code class="bg-dark-800 px-2 py-0.5 rounded">php artisan skills:sync</code> to register built-in skills.</p>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { reactive, onMounted } from 'vue';
import { useSkillsStore } from '../stores/skills';
import Sidebar from '../components/Sidebar.vue';

const skills = useSkillsStore();
const expandedParams = reactive({});

function toggleParams(id) {
    expandedParams[id] = !expandedParams[id];
}

function isRequired(skill, paramName) {
    return skill.parameters_schema?.required?.includes(paramName);
}

function formatCategory(category) {
    return category.charAt(0).toUpperCase() + category.slice(1).replace(/_/g, ' ');
}

function getCategoryIcon(category) {
    return 'span';
}

onMounted(() => {
    skills.fetchSkills();
});
</script>
