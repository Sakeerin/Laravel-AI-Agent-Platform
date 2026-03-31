<template>
  <div class="flex gap-4 justify-start">
    <div class="shrink-0 mt-1">
      <div class="w-8 h-8 bg-amber-600 rounded-lg flex items-center justify-center">
        <svg class="w-5 h-5 text-white animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
        </svg>
      </div>
    </div>

    <div class="max-w-[75%] bg-dark-800/50 border border-amber-500/20 rounded-2xl px-4 py-3">
      <div class="flex items-center gap-2">
        <div class="w-2 h-2 bg-amber-400 rounded-full animate-pulse"></div>
        <span class="text-sm text-amber-300 font-medium">
          Using {{ toolDisplayName }}
        </span>
      </div>
      <p v-if="toolCall.arguments" class="text-xs text-dark-400 mt-1 font-mono truncate max-w-md">
        {{ argumentsSummary }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    toolCall: { type: Object, required: true },
});

const TOOL_NAMES = {
    web_search: 'Web Search',
    browser: 'Browser',
    file_system: 'File System',
    shell_command: 'Shell',
    calculator: 'Calculator',
    datetime: 'Date/Time',
};

const toolDisplayName = computed(() =>
    TOOL_NAMES[props.toolCall.name] || props.toolCall.name
);

const argumentsSummary = computed(() => {
    const args = props.toolCall.arguments;
    if (!args) return '';
    if (args.query) return `"${args.query}"`;
    if (args.url) return args.url;
    if (args.command) return `$ ${args.command}`;
    if (args.expression) return args.expression;
    if (args.path) return `${args.action || 'read'}: ${args.path}`;
    return JSON.stringify(args).slice(0, 100);
});
</script>
