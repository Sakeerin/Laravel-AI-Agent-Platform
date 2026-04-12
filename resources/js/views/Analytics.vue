<template>
  <div class="flex h-screen bg-dark-950">
    <Sidebar />

    <main class="flex-1 flex flex-col h-screen overflow-hidden">
      <header class="border-b border-dark-800 bg-dark-900/50 backdrop-blur-sm px-6 py-4">
        <h2 class="text-lg font-semibold text-white">Usage &amp; cost</h2>
        <p class="text-xs text-dark-500 mt-0.5">Estimated spend from token usage (configure rates in <code class="text-dark-400">config/pricing.php</code>)</p>
      </header>

      <div class="flex-1 overflow-y-auto p-6">
        <div v-if="error" class="mb-4 bg-red-500/10 border border-red-500/20 rounded-xl p-3 text-red-400 text-sm">
          {{ error }}
        </div>

        <div v-if="loading" class="flex justify-center py-24">
          <div class="w-8 h-8 border-2 border-primary-500 border-t-transparent rounded-full animate-spin" />
        </div>

        <template v-else-if="summary">
          <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="rounded-xl border border-dark-800 bg-dark-900/80 p-4">
              <p class="text-xs text-dark-500 uppercase tracking-wide">Period</p>
              <p class="text-2xl font-semibold text-white mt-1">{{ summary.period_days }} days</p>
            </div>
            <div class="rounded-xl border border-dark-800 bg-dark-900/80 p-4">
              <p class="text-xs text-dark-500 uppercase tracking-wide">Input tokens</p>
              <p class="text-2xl font-semibold text-white mt-1">{{ formatNum(summary.totals.input_tokens) }}</p>
            </div>
            <div class="rounded-xl border border-dark-800 bg-dark-900/80 p-4">
              <p class="text-xs text-dark-500 uppercase tracking-wide">Output tokens</p>
              <p class="text-2xl font-semibold text-white mt-1">{{ formatNum(summary.totals.output_tokens) }}</p>
            </div>
            <div class="rounded-xl border border-dark-800 bg-dark-900/80 p-4">
              <p class="text-xs text-dark-500 uppercase tracking-wide">Est. cost (USD)</p>
              <p class="text-2xl font-semibold text-emerald-400 mt-1">${{ summary.totals.estimated_cost_usd.toFixed(4) }}</p>
            </div>
          </div>

          <div class="grid lg:grid-cols-2 gap-6 mb-8">
            <div class="rounded-xl border border-dark-800 bg-dark-900/50 p-4">
              <h3 class="text-sm font-medium text-white mb-3">By model</h3>
              <div class="space-y-2 max-h-56 overflow-y-auto">
                <div
                  v-for="row in summary.by_model"
                  :key="row.model"
                  class="flex justify-between text-sm text-dark-300 border-b border-dark-800/50 pb-2"
                >
                  <span class="truncate mr-2 font-mono text-xs">{{ row.model }}</span>
                  <span class="shrink-0 text-dark-500">{{ formatNum(row.input_tokens + row.output_tokens) }} tok</span>
                </div>
                <p v-if="!summary.by_model.length" class="text-dark-500 text-sm">No usage in this period.</p>
              </div>
            </div>
            <div class="rounded-xl border border-dark-800 bg-dark-900/50 p-4">
              <h3 class="text-sm font-medium text-white mb-3">By source</h3>
              <div class="space-y-2">
                <div
                  v-for="row in summary.by_source"
                  :key="row.source"
                  class="flex justify-between text-sm text-dark-300"
                >
                  <span>{{ row.source }}</span>
                  <span class="text-dark-500">${{ Number(row.estimated_cost_usd).toFixed(4) }} ({{ row.events }} events)</span>
                </div>
                <p v-if="!summary.by_source.length" class="text-dark-500 text-sm">No usage in this period.</p>
              </div>
            </div>
          </div>

          <div v-if="timeseries.length" class="rounded-xl border border-dark-800 bg-dark-900/50 p-4">
            <h3 class="text-sm font-medium text-white mb-2">Daily tokens</h3>
            <VueApexCharts type="area" height="280" :options="chartOptions" :series="chartSeries" />
          </div>
        </template>
      </div>
    </main>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import VueApexCharts from 'vue3-apexcharts';
import api from '../api/client';
import Sidebar from '../components/Sidebar.vue';

const loading = ref(true);
const error = ref('');
const summary = ref(null);
const timeseries = ref([]);

function formatNum(n) {
    return new Intl.NumberFormat().format(n);
}

const chartSeries = computed(() => [
    { name: 'Input tokens', data: timeseries.value.map((p) => p.input_tokens) },
    { name: 'Output tokens', data: timeseries.value.map((p) => p.output_tokens) },
]);

const chartOptions = computed(() => ({
    chart: {
        toolbar: { show: false },
        fontFamily: 'inherit',
        foreColor: '#94a3b8',
    },
    theme: { mode: 'dark' },
    stroke: { curve: 'smooth', width: 2 },
    dataLabels: { enabled: false },
    xaxis: {
        categories: timeseries.value.map((p) => p.date),
        labels: { rotate: -45, style: { fontSize: '10px' } },
    },
    yaxis: { labels: { formatter: (v) => formatNum(Math.round(v)) } },
    tooltip: { theme: 'dark' },
    colors: ['#6366f1', '#22d3ee'],
    grid: { borderColor: '#334155' },
}));

onMounted(async () => {
    loading.value = true;
    error.value = '';
    try {
        const [s, t] = await Promise.all([
            api.get('/analytics/summary', { params: { days: 30 } }),
            api.get('/analytics/timeseries', { params: { days: 30 } }),
        ]);
        summary.value = s.data;
        timeseries.value = t.data.points || [];
    } catch (e) {
        error.value = e.response?.data?.message || e.message || 'Failed to load analytics';
    } finally {
        loading.value = false;
    }
});
</script>
