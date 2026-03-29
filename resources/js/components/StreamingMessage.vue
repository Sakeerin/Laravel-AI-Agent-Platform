<template>
  <div class="flex gap-4 justify-start" v-if="content">
    <div class="shrink-0 mt-1">
      <div class="w-8 h-8 bg-primary-600 rounded-lg flex items-center justify-center">
        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
        </svg>
      </div>
    </div>
    <div class="max-w-[75%] bg-dark-800 text-dark-100 rounded-2xl px-4 py-3">
      <div class="message-content prose prose-invert max-w-none break-words" v-html="renderedContent"></div>
      <span class="inline-block w-2 h-5 bg-primary-400 animate-pulse ml-0.5 align-text-bottom rounded-sm"></span>
    </div>
  </div>

  <!-- Typing indicator when content is empty but streaming -->
  <div v-else class="flex gap-4 justify-start">
    <div class="shrink-0 mt-1">
      <div class="w-8 h-8 bg-primary-600 rounded-lg flex items-center justify-center">
        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
        </svg>
      </div>
    </div>
    <div class="bg-dark-800 rounded-2xl px-4 py-3">
      <div class="typing-indicator flex gap-1">
        <span class="w-2 h-2 bg-dark-400 rounded-full"></span>
        <span class="w-2 h-2 bg-dark-400 rounded-full"></span>
        <span class="w-2 h-2 bg-dark-400 rounded-full"></span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useMarkdown } from '../composables/useMarkdown';

const props = defineProps({
    content: { type: String, default: '' },
});

const { render } = useMarkdown();
const renderedContent = computed(() => render(props.content));
</script>
