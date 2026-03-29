<template>
  <div class="min-h-screen bg-dark-950 flex items-center justify-center px-4">
    <div class="w-full max-w-md">
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-primary-600 rounded-2xl mb-4">
          <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z" />
          </svg>
        </div>
        <h1 class="text-3xl font-bold text-white">Create Account</h1>
        <p class="text-dark-400 mt-2">Join the AI Agent Platform</p>
      </div>

      <form @submit.prevent="handleRegister" class="bg-dark-900 rounded-2xl p-8 border border-dark-800">
        <div v-if="error" class="mb-4 p-3 bg-red-500/10 border border-red-500/20 rounded-lg text-red-400 text-sm">
          {{ error }}
        </div>

        <div class="mb-5">
          <label class="block text-sm font-medium text-dark-300 mb-1.5">Name</label>
          <input
            v-model="form.name"
            type="text"
            required
            class="w-full px-4 py-3 bg-dark-800 border border-dark-700 rounded-xl text-white placeholder-dark-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition"
            placeholder="Your name"
          />
        </div>

        <div class="mb-5">
          <label class="block text-sm font-medium text-dark-300 mb-1.5">Email</label>
          <input
            v-model="form.email"
            type="email"
            required
            class="w-full px-4 py-3 bg-dark-800 border border-dark-700 rounded-xl text-white placeholder-dark-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition"
            placeholder="you@example.com"
          />
        </div>

        <div class="mb-5">
          <label class="block text-sm font-medium text-dark-300 mb-1.5">Password</label>
          <input
            v-model="form.password"
            type="password"
            required
            class="w-full px-4 py-3 bg-dark-800 border border-dark-700 rounded-xl text-white placeholder-dark-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition"
            placeholder="••••••••"
          />
        </div>

        <div class="mb-6">
          <label class="block text-sm font-medium text-dark-300 mb-1.5">Confirm Password</label>
          <input
            v-model="form.password_confirmation"
            type="password"
            required
            class="w-full px-4 py-3 bg-dark-800 border border-dark-700 rounded-xl text-white placeholder-dark-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition"
            placeholder="••••••••"
          />
        </div>

        <button
          type="submit"
          :disabled="auth.loading"
          class="w-full py-3 px-4 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-xl transition disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <span v-if="auth.loading">Creating account...</span>
          <span v-else>Create Account</span>
        </button>

        <p class="text-center text-dark-400 mt-6 text-sm">
          Already have an account?
          <router-link to="/login" class="text-primary-400 hover:text-primary-300 font-medium">Sign in</router-link>
        </p>
      </form>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const router = useRouter();
const auth = useAuthStore();
const error = ref('');
const form = reactive({ name: '', email: '', password: '', password_confirmation: '' });

async function handleRegister() {
    error.value = '';
    try {
        await auth.register(form.name, form.email, form.password, form.password_confirmation);
        router.push('/');
    } catch (e) {
        const errors = e.response?.data?.errors;
        if (errors) {
            error.value = Object.values(errors).flat().join(' ');
        } else {
            error.value = e.response?.data?.message || 'Registration failed';
        }
    }
}
</script>
