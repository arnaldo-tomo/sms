<script setup lang="ts">
import {
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Legend,
    LinearScale,
    Tooltip,
} from 'chart.js';
import { computed } from 'vue';
import { Bar } from 'vue-chartjs';

ChartJS.register(CategoryScale, LinearScale, BarElement, Tooltip, Legend);

const props = defineProps<{
    data: { date: string; sent: number; delivered: number; failed: number }[];
}>();

const chartData = computed(() => ({
    labels: props.data.map((d) =>
        new Date(d.date).toLocaleDateString('pt-PT', { day: '2-digit', month: 'short' }),
    ),
    datasets: [
        {
            label: 'Entregues',
            data: props.data.map((d) => d.delivered),
            backgroundColor: '#10b981',
            borderRadius: 4,
            stack: 'a',
        },
        {
            label: 'Enviados',
            data: props.data.map((d) => Math.max(0, d.sent - d.delivered)),
            backgroundColor: '#6366f1',
            borderRadius: 4,
            stack: 'a',
        },
        {
            label: 'Falhados',
            data: props.data.map((d) => d.failed),
            backgroundColor: '#ef4444',
            borderRadius: 4,
            stack: 'a',
        },
    ],
}));

const options = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'bottom' as const, labels: { usePointStyle: true, boxWidth: 8 } },
    },
    scales: {
        x: { stacked: true, grid: { display: false } },
        y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } },
    },
};
</script>

<template>
    <div class="h-72">
        <Bar :data="chartData" :options="options" />
    </div>
</template>
