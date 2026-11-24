<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';
import type { ChartData, ChartOptions } from 'chart.js';
import ChartCanvas from '../chart/ChartCanvas.vue';
import { useLayoutState } from '../../layout/composables/layout';

const { layoutConfig, isDarkTheme } = useLayoutState();

const chartData = ref<ChartData<'bar'>>(setChartData());
const chartOptions = ref<ChartOptions<'bar'>>(setChartOptions());

function setChartData(): ChartData<'bar'> {
    const documentStyle = getComputedStyle(document.documentElement);

    return {
        labels: ['Q1', 'Q2', 'Q3', 'Q4'],
        datasets: [
            {
                type: 'bar',
                label: 'Subscriptions',
                backgroundColor: documentStyle.getPropertyValue('--p-primary-400'),
                data: [4000, 10000, 15000, 4000],
                barThickness: 32
            },
            {
                type: 'bar',
                label: 'Advertising',
                backgroundColor: documentStyle.getPropertyValue('--p-primary-300'),
                data: [2100, 8400, 2400, 7500],
                barThickness: 32
            },
            {
                type: 'bar',
                label: 'Affiliate',
                backgroundColor: documentStyle.getPropertyValue('--p-primary-200'),
                data: [4100, 5200, 3400, 7400],
                borderRadius: 8,
                borderSkipped: false,
                barThickness: 32
            }
        ]
    };
}

function setChartOptions(): ChartOptions<'bar'> {
    const documentStyle = getComputedStyle(document.documentElement);
    const borderColor = documentStyle.getPropertyValue('--p-surface-200');
    const textMutedColor = documentStyle.getPropertyValue('--p-text-muted-color');

    return {
        maintainAspectRatio: false,
        aspectRatio: 0.8,
        scales: {
            x: {
                stacked: true,
                ticks: { color: textMutedColor },
                grid: { color: 'transparent' }
            },
            y: {
                stacked: true,
                ticks: { color: textMutedColor },
                grid: { color: borderColor, drawTicks: false }
            }
        }
    };
}

watch([() => layoutConfig.primary, () => layoutConfig.surface, isDarkTheme], () => {
    chartData.value = setChartData();
    chartOptions.value = setChartOptions();
});

onMounted(() => {
    chartData.value = setChartData();
    chartOptions.value = setChartOptions();
});
</script>

<template>
    <div class="card">
        <div class="font-semibold text-xl mb-4">Revenue Stream</div>
        <ChartCanvas type="bar" :data="chartData" :options="chartOptions" class="h-80" />
    </div>
</template>

<style lang="scss" scoped></style>
