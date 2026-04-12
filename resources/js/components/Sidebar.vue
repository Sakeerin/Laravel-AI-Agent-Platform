<template>
  <aside class="w-72 bg-dark-900 border-r border-dark-800 flex flex-col h-screen">
    <!-- Header -->
    <div class="p-4 border-b border-dark-800">
      <button
        @click="chat.startNewChat()"
        class="w-full flex items-center gap-3 px-4 py-3 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-medium transition"
      >
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        New Chat
      </button>
    </div>

    <!-- Conversations list -->
    <div class="flex-1 overflow-y-auto p-2">
      <div v-if="chat.loadingConversations" class="flex justify-center py-8">
        <div class="w-6 h-6 border-2 border-primary-500 border-t-transparent rounded-full animate-spin"></div>
      </div>

      <div v-else-if="chat.conversations.length === 0" class="text-center py-8 text-dark-500 text-sm">
        No conversations yet
      </div>

      <div
        v-for="conv in chat.conversations"
        :key="conv.id"
        @click="chat.selectConversation(conv)"
        class="group flex items-center gap-2 px-3 py-2.5 rounded-lg cursor-pointer mb-0.5 transition"
        :class="chat.currentConversation?.id === conv.id
          ? 'bg-dark-800 text-white'
          : 'text-dark-400 hover:bg-dark-800/50 hover:text-dark-200'"
      >
        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
        </svg>
        <span class="truncate text-sm flex-1">{{ conv.title }}</span>
        <button
          @click.stop="handleDelete(conv.id)"
          class="opacity-0 group-hover:opacity-100 p-1 hover:text-red-400 transition"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Navigation -->
    <div class="p-2 border-t border-dark-800">
      <router-link
        to="/"
        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition"
        :class="$route.name === 'chat' ? 'bg-dark-800 text-white' : 'text-dark-400 hover:bg-dark-800/50 hover:text-dark-200'"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
        </svg>
        Chat
      </router-link>
      <router-link
        to="/skills"
        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition"
        :class="$route.name === 'skills' ? 'bg-dark-800 text-white' : 'text-dark-400 hover:bg-dark-800/50 hover:text-dark-200'"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75a4.5 4.5 0 01-4.884 4.484c-1.076-.091-2.264.071-2.95.904l-7.152 8.684a2.548 2.548 0 11-3.586-3.586l8.684-7.152c.833-.686.995-1.874.904-2.95a4.5 4.5 0 016.336-4.486l-3.276 3.276a3.004 3.004 0 002.25 2.25l3.276-3.276c.256.565.398 1.192.398 1.852z" />
        </svg>
        Skills
      </router-link>
      <router-link
        to="/marketplace"
        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition"
        :class="$route.name === 'marketplace' ? 'bg-dark-800 text-white' : 'text-dark-400 hover:bg-dark-800/50 hover:text-dark-200'"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
        </svg>
        Marketplace
      </router-link>
      <router-link
        to="/tasks"
        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition"
        :class="$route.name === 'tasks' ? 'bg-dark-800 text-white' : 'text-dark-400 hover:bg-dark-800/50 hover:text-dark-200'"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
        </svg>
        Tasks
      </router-link>
      <router-link
        to="/channels"
        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition"
        :class="$route.name === 'channels' ? 'bg-dark-800 text-white' : 'text-dark-400 hover:bg-dark-800/50 hover:text-dark-200'"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 20.25c4.97 0 9-3.694 9-8.25s-4.03-8.25-9-8.25S3 7.444 3 12c0 2.104.859 4.023 2.273 5.48.432.447.74 1.04.586 1.641a4.483 4.483 0 01-1.437 2.06c.443.57.996 1.064 1.626 1.453C7.732 20.917 9.806 21.25 12 21.25z" />
        </svg>
        Channels
      </router-link>
      <router-link
        to="/personalization"
        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition"
        :class="$route.name === 'personalization' ? 'bg-dark-800 text-white' : 'text-dark-400 hover:bg-dark-800/50 hover:text-dark-200'"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 11.25h7.5m-7.5 3h4.875M12 3c7.18 0 9.75 2.57 9.75 9.75 0 4.125-1.125 6.75-3.375 8.25m-12.75 0c-2.25-1.5-3.375-4.125-3.375-8.25C3 5.57 5.57 3 12 3z" />
        </svg>
        Memory
      </router-link>
    </div>

    <!-- User section -->
    <div class="p-4 border-t border-dark-800">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 bg-primary-600 rounded-full flex items-center justify-center text-white font-semibold text-sm">
          {{ auth.user?.name?.charAt(0)?.toUpperCase() || '?' }}
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium text-white truncate">{{ auth.user?.name }}</p>
          <p class="text-xs text-dark-500 truncate">{{ auth.user?.email }}</p>
        </div>
        <button @click="handleLogout" class="p-2 text-dark-500 hover:text-red-400 transition" title="Sign out">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
          </svg>
        </button>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useChatStore } from '../stores/chat';

const router = useRouter();
const auth = useAuthStore();
const chat = useChatStore();

async function handleLogout() {
    await auth.logout();
    router.push('/login');
}

async function handleDelete(id) {
    if (confirm('Delete this conversation?')) {
        await chat.deleteConversation(id);
    }
}
</script>
