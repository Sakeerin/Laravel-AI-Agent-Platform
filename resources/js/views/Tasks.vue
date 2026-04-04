<template>
  <div class="flex h-screen bg-dark-950">
    <Sidebar />

    <main class="flex-1 flex flex-col h-screen overflow-hidden">
      <header class="border-b border-dark-800 bg-dark-900/50 backdrop-blur-sm px-6 py-4">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-lg font-semibold text-white">Task History</h2>
            <p class="text-xs text-dark-500 mt-0.5">
              {{ tasksStore.pagination.total }} total tasks
            </p>
          </div>

          <!-- Status filter -->
          <div class="flex items-center gap-1">
            <button
              v-for="filter in statusFilters"
              :key="filter.value"
              @click="tasksStore.setFilter(filter.value)"
              class="px-3 py-1.5 rounded-lg text-xs font-medium transition"
              :class="tasksStore.statusFilter === filter.value
                ? 'bg-primary-600 text-white'
                : 'text-dark-400 hover:text-white hover:bg-dark-800'"
            >
              {{ filter.label }}
            </button>
          </div>
        </div>
      </header>

      <div class="flex-1 overflow-y-auto p-6">
        <!-- Loading -->
        <div v-if="tasksStore.loading" class="flex justify-center py-20">
          <div class="w-8 h-8 border-2 border-primary-500 border-t-transparent rounded-full animate-spin"></div>
        </div>

        <!-- Error -->
        <div v-else-if="tasksStore.error" class="bg-red-500/10 border border-red-500/20 rounded-xl p-4 text-red-400 text-sm">
          {{ tasksStore.error }}
        </div>

        <!-- Tasks list -->
        <div v-else class="max-w-4xl mx-auto space-y-3">
          <div
            v-for="task in tasksStore.tasks"
            :key="task.id"
            class="bg-dark-900/50 border border-dark-800 rounded-xl p-4 hover:border-dark-700 transition"
          >
            <div class="flex items-start justify-between gap-4">
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                  <span
                    class="shrink-0 w-2 h-2 rounded-full"
                    :class="statusDotClass(task.status)"
                  ></span>
                  <h4 class="font-medium text-white truncate">{{ task.title }}</h4>
                  <span
                    class="text-[10px] px-1.5 py-0.5 rounded-full uppercase tracking-wider font-medium"
                    :class="statusBadgeClass(task.status)"
                  >
                    {{ task.status }}
                  </span>
                </div>

                <p v-if="task.description" class="text-sm text-dark-400 mt-1 truncate">
                  {{ task.description }}
                </p>

                <!-- Progress bar for running tasks -->
                <div v-if="task.status === 'running' && task.progress > 0" class="mt-2">
                  <div class="w-full bg-dark-800 rounded-full h-1.5">
                    <div
                      class="bg-primary-500 h-1.5 rounded-full transition-all duration-500"
                      :style="{ width: task.progress + '%' }"
                    ></div>
                  </div>
                  <span class="text-[10px] text-dark-500 mt-0.5">{{ task.progress }}%</span>
                </div>

                <!-- Result preview -->
                <div v-if="task.result && expandedTasks[task.id]" class="mt-2 bg-dark-800/50 rounded-lg p-3">
                  <pre class="text-xs text-dark-300 font-mono whitespace-pre-wrap max-h-40 overflow-y-auto">{{ formatResult(task.result) }}</pre>
                </div>

                <!-- Error message -->
                <div v-if="task.error && task.status === 'failed'" class="mt-2 bg-red-500/5 border border-red-500/10 rounded-lg p-2">
                  <p class="text-xs text-red-400">{{ task.error }}</p>
                </div>
              </div>

              <div class="flex items-center gap-2 shrink-0">
                <!-- Expand result -->
                <button
                  v-if="task.result"
                  @click="toggleExpand(task.id)"
                  class="p-1.5 text-dark-500 hover:text-dark-300 transition"
                  :title="expandedTasks[task.id] ? 'Collapse' : 'Expand'"
                >
                  <svg class="w-4 h-4 transition-transform" :class="expandedTasks[task.id] ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                  </svg>
                </button>

                <!-- Cancel button -->
                <button
                  v-if="task.status === 'running' || task.status === 'queued'"
                  @click="handleCancel(task.id)"
                  class="px-2.5 py-1 text-xs font-medium text-red-400 hover:text-red-300 bg-red-500/10 hover:bg-red-500/20 rounded-lg transition"
                >
                  Cancel
                </button>
              </div>
            </div>

            <!-- Metadata -->
            <div class="flex items-center gap-3 mt-3 pt-3 border-t border-dark-800/50 text-xs text-dark-500">
              <span>Type: {{ task.type }}</span>
              <span class="w-1 h-1 bg-dark-700 rounded-full"></span>
              <span>{{ formatTime(task.created_at) }}</span>
              <template v-if="task.completed_at">
                <span class="w-1 h-1 bg-dark-700 rounded-full"></span>
                <span>Duration: {{ formatDuration(task.started_at, task.completed_at) }}</span>
              </template>
            </div>
          </div>

          <!-- Empty state -->
          <div v-if="tasksStore.tasks.length === 0" class="text-center py-20">
            <div class="w-16 h-16 bg-dark-800 rounded-2xl flex items-center justify-center mx-auto mb-4">
              <svg class="w-8 h-8 text-dark-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-white mb-2">No Tasks Yet</h3>
            <p class="text-dark-400 text-sm">Tasks will appear here when the AI uses tools during conversations.</p>
          </div>

          <!-- Pagination -->
          <div v-if="tasksStore.pagination.last_page > 1" class="flex items-center justify-center gap-2 pt-4">
            <button
              @click="tasksStore.fetchTasks(tasksStore.pagination.current_page - 1)"
              :disabled="tasksStore.pagination.current_page <= 1"
              class="px-3 py-1.5 rounded-lg text-sm transition disabled:opacity-30 disabled:cursor-not-allowed text-dark-400 hover:text-white hover:bg-dark-800"
            >
              Previous
            </button>
            <span class="text-xs text-dark-500">
              Page {{ tasksStore.pagination.current_page }} of {{ tasksStore.pagination.last_page }}
            </span>
            <button
              @click="tasksStore.fetchTasks(tasksStore.pagination.current_page + 1)"
              :disabled="tasksStore.pagination.current_page >= tasksStore.pagination.last_page"
              class="px-3 py-1.5 rounded-lg text-sm transition disabled:opacity-30 disabled:cursor-not-allowed text-dark-400 hover:text-white hover:bg-dark-800"
            >
              Next
            </button>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { reactive, onMounted } from 'vue';
import { useTasksStore } from '../stores/tasks';
import Sidebar from '../components/Sidebar.vue';

const tasksStore = useTasksStore();
const expandedTasks = reactive({});

const statusFilters = [
    { label: 'All', value: '' },
    { label: 'Running', value: 'running' },
    { label: 'Queued', value: 'queued' },
    { label: 'Completed', value: 'completed' },
    { label: 'Failed', value: 'failed' },
    { label: 'Cancelled', value: 'cancelled' },
];

function toggleExpand(id) {
    expandedTasks[id] = !expandedTasks[id];
}

function handleCancel(taskId) {
    if (confirm('Cancel this task?')) {
        tasksStore.cancelTask(taskId);
    }
}

function statusDotClass(status) {
    const map = {
        queued: 'bg-dark-400',
        running: 'bg-amber-400 animate-pulse',
        completed: 'bg-emerald-400',
        failed: 'bg-red-400',
        cancelled: 'bg-dark-600',
    };
    return map[status] || 'bg-dark-500';
}

function statusBadgeClass(status) {
    const map = {
        queued: 'bg-dark-700 text-dark-300',
        running: 'bg-amber-500/10 text-amber-400',
        completed: 'bg-emerald-500/10 text-emerald-400',
        failed: 'bg-red-500/10 text-red-400',
        cancelled: 'bg-dark-700 text-dark-500',
    };
    return map[status] || 'bg-dark-700 text-dark-400';
}

function formatResult(result) {
    if (typeof result === 'string') return result;
    return JSON.stringify(result, null, 2);
}

function formatTime(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleString();
}

function formatDuration(startStr, endStr) {
    if (!startStr || !endStr) return '-';
    const ms = new Date(endStr) - new Date(startStr);
    if (ms < 1000) return `${ms}ms`;
    if (ms < 60000) return `${(ms / 1000).toFixed(1)}s`;
    return `${Math.round(ms / 60000)}m`;
}

onMounted(() => {
    tasksStore.fetchTasks();
});
</script>
