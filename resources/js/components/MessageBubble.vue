<template>
  <div class="flex gap-4" :class="message.role === 'user' ? 'justify-end' : 'justify-start'">
    <!-- Avatar for assistant -->
    <div v-if="message.role === 'assistant'" class="shrink-0 mt-1">
      <div class="w-8 h-8 bg-primary-600 rounded-lg flex items-center justify-center">
        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
        </svg>
      </div>
    </div>

    <!-- Message content -->
    <div
      class="max-w-[75%] rounded-2xl px-4 py-3"
      :class="{
        'bg-primary-600 text-white': message.role === 'user',
        'bg-dark-800 text-dark-100': message.role === 'assistant' && !message.is_error,
        'bg-red-500/10 text-red-400 border border-red-500/20': message.is_error,
      }"
    >
      <div v-if="message.role === 'user'" class="whitespace-pre-wrap break-words">{{ message.content }}</div>
      <div
        v-else
        class="message-content prose prose-invert max-w-none break-words"
        v-html="renderedContent"
      ></div>

      <!-- Token info for assistant messages -->
      <div v-if="message.role === 'assistant' && message.input_tokens" class="mt-2 text-xs text-dark-500">
        {{ message.input_tokens + message.output_tokens }} tokens
      </div>
    </div>

    <!-- Avatar for user -->
    <div v-if="message.role === 'user'" class="shrink-0 mt-1">
      <div class="w-8 h-8 bg-dark-700 rounded-lg flex items-center justify-center text-dark-300 text-sm font-semibold">
        U
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useMarkdown } from '../composables/useMarkdown';

const props = defineProps({
    message: { type: Object, required: true },
});

const { render } = useMarkdown();
const renderedContent = computed(() => render(props.message.content));
</script>
