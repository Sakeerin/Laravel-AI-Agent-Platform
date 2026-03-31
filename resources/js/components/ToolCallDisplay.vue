<template>
  <div class="flex gap-4 justify-start">
    <div class="shrink-0 mt-1">
      <div class="w-8 h-8 rounded-lg flex items-center justify-center"
        :class="message.success ? 'bg-emerald-600' : 'bg-red-600'">
        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17l-5.385-5.38a2.25 2.25 0 013.18-3.181l2.205 2.205 5.25-5.25a2.25 2.25 0 013.18 3.18l-7.045 7.045a1.5 1.5 0 01-2.12-.001l-.265-.265z" v-if="message.success" />
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" v-else />
        </svg>
      </div>
    </div>

    <div class="max-w-[75%] bg-dark-800/50 border rounded-2xl px-4 py-3 text-sm"
      :class="message.success ? 'border-emerald-500/20' : 'border-red-500/20'">

      <!-- Tool header -->
      <div class="flex items-center gap-2 mb-2">
        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium"
          :class="message.success ? 'bg-emerald-500/10 text-emerald-400' : 'bg-red-500/10 text-red-400'">
          <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75a4.5 4.5 0 01-4.884 4.484c-1.076-.091-2.264.071-2.95.904l-7.152 8.684a2.548 2.548 0 11-3.586-3.586l8.684-7.152c.833-.686.995-1.874.904-2.95a4.5 4.5 0 016.336-4.486l-3.276 3.276a3.004 3.004 0 002.25 2.25l3.276-3.276c.256.565.398 1.192.398 1.852z" />
          </svg>
          {{ toolDisplayName }}
        </span>
        <span v-if="message.duration_ms" class="text-xs text-dark-500">
          {{ message.duration_ms }}ms
        </span>
      </div>

      <!-- Result preview -->
      <div class="text-dark-300 max-h-32 overflow-y-auto">
        <pre v-if="isJsonResult" class="whitespace-pre-wrap text-xs font-mono">{{ formattedResult }}</pre>
        <p v-else class="whitespace-pre-wrap text-xs">{{ truncatedResult }}</p>
      </div>

      <!-- Expand toggle -->
      <button
        v-if="message.content && message.content.length > 200"
        @click="expanded = !expanded"
        class="mt-1 text-xs text-primary-400 hover:text-primary-300"
      >
        {{ expanded ? 'Show less' : 'Show more' }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    message: { type: Object, required: true },
});

const expanded = ref(false);

const TOOL_NAMES = {
    web_search: 'Web Search',
    browser: 'Browser',
    file_system: 'File System',
    shell_command: 'Shell',
    calculator: 'Calculator',
    datetime: 'Date/Time',
};

const toolDisplayName = computed(() =>
    TOOL_NAMES[props.message.tool_name] || props.message.tool_name
);

const isJsonResult = computed(() => {
    if (!props.message.content) return false;
    try {
        JSON.parse(props.message.content);
        return true;
    } catch {
        return false;
    }
});

const formattedResult = computed(() => {
    if (!props.message.content) return '';
    try {
        const parsed = JSON.parse(props.message.content);
        const formatted = JSON.stringify(parsed, null, 2);
        return expanded.value ? formatted : formatted.slice(0, 300);
    } catch {
        return props.message.content;
    }
});

const truncatedResult = computed(() => {
    if (!props.message.content) return '';
    return expanded.value ? props.message.content : props.message.content.slice(0, 300);
});
</script>
