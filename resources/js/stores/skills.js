import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '../api/client';

export const useSkillsStore = defineStore('skills', () => {
    const skills = ref([]);
    const loading = ref(false);
    const error = ref(null);

    const categories = computed(() => {
        const cats = {};
        for (const skill of skills.value) {
            if (!cats[skill.category]) {
                cats[skill.category] = [];
            }
            cats[skill.category].push(skill);
        }
        return cats;
    });

    const enabledCount = computed(() =>
        skills.value.filter(s => s.is_enabled).length
    );

    async function fetchSkills() {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await api.get('/skills');
            skills.value = Array.isArray(data) ? data : data.data || [];
        } catch (e) {
            error.value = e.response?.data?.message || 'Failed to load skills';
        } finally {
            loading.value = false;
        }
    }

    async function toggleSkill(skill) {
        const newState = !skill.is_enabled;
        try {
            await api.patch(`/skills/${skill.id}/toggle`, { is_enabled: newState });
            const found = skills.value.find(s => s.id === skill.id);
            if (found) found.is_enabled = newState;
        } catch (e) {
            error.value = e.response?.data?.message || 'Failed to toggle skill';
        }
    }

    return {
        skills, loading, error, categories, enabledCount,
        fetchSkills, toggleSkill,
    };
});
