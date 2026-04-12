import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '../api/client';

export const useMarketplaceStore = defineStore('marketplace', () => {
    const packages = ref([]);
    const myInstalls = ref([]);
    const loading = ref(false);
    const error = ref(null);
    const query = ref('');
    const category = ref('');

    const installedSlugs = computed(() => new Set(
        myInstalls.value.map((r) => r.package_slug).filter(Boolean)
    ));

    async function fetchPackages() {
        loading.value = true;
        error.value = null;
        try {
            const params = {};
            if (query.value.trim()) {
                params.q = query.value.trim();
            }
            if (category.value.trim()) {
                params.category = category.value.trim();
            }
            const { data } = await api.get('/marketplace/packages', { params });
            packages.value = Array.isArray(data) ? data : [];
        } catch (e) {
            error.value = e.response?.data?.message || 'Failed to load marketplace';
            packages.value = [];
        } finally {
            loading.value = false;
        }
    }

    async function fetchMyInstalls() {
        try {
            const { data } = await api.get('/marketplace/my-installs');
            myInstalls.value = Array.isArray(data) ? data : [];
        } catch {
            myInstalls.value = [];
        }
    }

    async function installPackage(slug) {
        await api.post(`/marketplace/packages/${slug}/install`);
        await fetchMyInstalls();
    }

    async function uninstallPackage(slug) {
        await api.delete(`/marketplace/packages/${slug}/install`);
        await fetchMyInstalls();
    }

    return {
        packages,
        myInstalls,
        loading,
        error,
        query,
        category,
        installedSlugs,
        fetchPackages,
        fetchMyInstalls,
        installPackage,
        uninstallPackage,
    };
});
