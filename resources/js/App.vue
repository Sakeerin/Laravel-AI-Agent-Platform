<template>
  <router-view />
  <OnboardingModal v-if="showOnboarding" @completed="onOnboardingDone" />
</template>

<script setup>
import { computed } from 'vue';
import { useAuthStore } from './stores/auth';
import OnboardingModal from './components/OnboardingModal.vue';

const auth = useAuthStore();

const showOnboarding = computed(() => {
    if (!auth.isAuthenticated || !auth.user) {
        return false;
    }
    const done = auth.user.agent_settings?.onboarding_completed;
    return done !== true;
});

async function onOnboardingDone() {
    await auth.fetchUser();
}
</script>
