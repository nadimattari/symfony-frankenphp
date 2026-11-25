<script setup lang="ts">
import { ref, watch } from "vue";
import type { ChartData, ChartOptions } from "chart.js";
import ChartCanvas from "../../components/chart/ChartCanvas.vue";
import { useLayout } from "../../layout/composables/layout";

const { layoutConfig } = useLayout();
let documentStyle = getComputedStyle(document.documentElement);
let textColor = documentStyle.getPropertyValue("--p-text-color");
let textColorSecondary = documentStyle.getPropertyValue("--p-text-muted-color");
let surfaceBorder = documentStyle.getPropertyValue("--p-surface-200");

const lineData = ref<ChartData>({ labels: [], datasets: [] });
const pieData = ref<ChartData>({ labels: [], datasets: [] });
const polarData = ref<ChartData>({ labels: [], datasets: [] });
const barData = ref<ChartData>({ labels: [], datasets: [] });
const radarData = ref<ChartData>({ labels: [], datasets: [] });

const lineOptions = ref<ChartOptions>({});
const pieOptions = ref<ChartOptions>({});
const polarOptions = ref<ChartOptions>({});
const barOptions = ref<ChartOptions>({});
const radarOptions = ref<ChartOptions>({});

const setColorOptions = (): void => {
    documentStyle = getComputedStyle(document.documentElement);
    textColor = documentStyle.getPropertyValue("--p-text-color");
    textColorSecondary = documentStyle.getPropertyValue("--p-text-muted-color");
    surfaceBorder = documentStyle.getPropertyValue("--p-surface-200");
};

const setChart = (): void => {
    barData.value = {
        labels: [
            "January",
            "February",
            "March",
            "April",
            "May",
            "June",
            "July",
        ],
        datasets: [
            {
                label: "My First dataset",
                backgroundColor:
                    documentStyle.getPropertyValue("--p-primary-500"),
                borderColor: documentStyle.getPropertyValue("--p-primary-500"),
                data: [65, 59, 80, 81, 56, 55, 40],
            },
            {
                label: "My Second dataset",
                backgroundColor:
                    documentStyle.getPropertyValue("--p-primary-200"),
                borderColor: documentStyle.getPropertyValue("--p-primary-200"),
                data: [28, 48, 40, 19, 86, 27, 90],
            },
        ],
    };
    barOptions.value = {
        plugins: {
            legend: {
                labels: {
                    color: textColor,
                },
            },
        },
        scales: {
            x: {
                ticks: {
                    color: textColorSecondary,
                    font: {
                        weight: 500,
                    },
                },
                grid: {
                    display: false,
                },
                border: {
                    display: false,
                },
            },
            y: {
                ticks: {
                    color: textColorSecondary,
                },
                grid: {
                    color: surfaceBorder,
                },
                border: {
                    display: false,
                },
            },
        },
    };

    pieData.value = {
        labels: ["A", "B", "C"],
        datasets: [
            {
                data: [540, 325, 702],
                backgroundColor: [
                    documentStyle.getPropertyValue("--p-indigo-500"),
                    documentStyle.getPropertyValue("--p-purple-500"),
                    documentStyle.getPropertyValue("--p-teal-500"),
                ],
                hoverBackgroundColor: [
                    documentStyle.getPropertyValue("--p-indigo-400"),
                    documentStyle.getPropertyValue("--p-purple-400"),
                    documentStyle.getPropertyValue("--teal-400"),
                ],
            },
        ],
    };

    pieOptions.value = {
        plugins: {
            legend: {
                labels: {
                    usePointStyle: true,
                    color: textColor,
                },
            },
        },
    };

    lineData.value = {
        labels: [
            "January",
            "February",
            "March",
            "April",
            "May",
            "June",
            "July",
        ],
        datasets: [
            {
                label: "First Dataset",
                data: [65, 59, 80, 81, 56, 55, 40],
                fill: false,
                backgroundColor:
                    documentStyle.getPropertyValue("--p-primary-500"),
                borderColor: documentStyle.getPropertyValue("--p-primary-500"),
                tension: 0.4,
            },
            {
                label: "Second Dataset",
                data: [28, 48, 40, 19, 86, 27, 90],
                fill: false,
                backgroundColor:
                    documentStyle.getPropertyValue("--p-primary-200"),
                borderColor: documentStyle.getPropertyValue("--p-primary-200"),
                tension: 0.4,
            },
        ],
    };

    lineOptions.value = {
        plugins: {
            legend: {
                labels: {
                    color: textColor,
                },
            },
        },
        scales: {
            x: {
                ticks: {
                    color: textColorSecondary,
                },
                grid: {
                    color: surfaceBorder,
                },
                border: {
                    display: false,
                },
            },
            y: {
                ticks: {
                    color: textColorSecondary,
                },
                grid: {
                    color: surfaceBorder,
                },
                border: {
                    display: false,
                },
            },
        },
    };

    polarData.value = {
        datasets: [
            {
                data: [11, 16, 7, 3],
                backgroundColor: [
                    documentStyle.getPropertyValue("--p-indigo-500"),
                    documentStyle.getPropertyValue("--p-purple-500"),
                    documentStyle.getPropertyValue("--p-teal-500"),
                    documentStyle.getPropertyValue("--p-orange-500"),
                ],
                label: "My dataset",
            },
        ],
        labels: ["Indigo", "Purple", "Teal", "Orange"],
    };

    polarOptions.value = {
        plugins: {
            legend: {
                labels: {
                    color: textColor,
                },
            },
        },
        scales: {
            r: {
                grid: {
                    color: surfaceBorder,
                },
            },
        },
    };

    radarData.value = {
        labels: [
            "Eating",
            "Drinking",
            "Sleeping",
            "Designing",
            "Coding",
            "Cycling",
            "Running",
        ],
        datasets: [
            {
                label: "My First dataset",
                borderColor: documentStyle.getPropertyValue("--p-indigo-400"),
                pointBackgroundColor:
                    documentStyle.getPropertyValue("--p-indigo-400"),
                pointBorderColor:
                    documentStyle.getPropertyValue("--p-indigo-400"),
                pointHoverBackgroundColor: textColor,
                pointHoverBorderColor:
                    documentStyle.getPropertyValue("--p-indigo-400"),
                data: [65, 59, 90, 81, 56, 55, 40],
            },
            {
                label: "My Second dataset",
                borderColor: documentStyle.getPropertyValue("--p-purple-400"),
                pointBackgroundColor:
                    documentStyle.getPropertyValue("--p-purple-400"),
                pointBorderColor:
                    documentStyle.getPropertyValue("--p-purple-400"),
                pointHoverBackgroundColor: textColor,
                pointHoverBorderColor:
                    documentStyle.getPropertyValue("--p-purple-400"),
                data: [28, 48, 40, 19, 96, 27, 100],
            },
        ],
    };

    radarOptions.value = {
        plugins: {
            legend: {
                labels: {
                    color: textColor,
                },
            },
        },
        scales: {
            r: {
                grid: {
                    color: textColorSecondary,
                },
            },
        },
    };
};

watch(
    layoutConfig.theme,
    () => {
        setColorOptions();
        setChart();
    },
    { immediate: true },
);
</script>

<template>
    <div class="grid grid-cols-12 gap-4 w-full [&_.p-component]:w-full">
        <div class="col-span-12 xl:col-span-6">
            <div class="card">
                <h5>Linear Chart</h5>
                <ChartCanvas
                    type="line"
                    :data="lineData"
                    :options="lineOptions"
                ></ChartCanvas>
            </div>
        </div>
        <div class="col-span-12 xl:col-span-6">
            <div class="card">
                <h5>Bar Chart</h5>
                <ChartCanvas
                    type="bar"
                    :data="barData"
                    :options="barOptions"
                ></ChartCanvas>
            </div>
        </div>
        <div class="col-span-12 xl:col-span-6">
            <div class="card flex flex-col items-center">
                <h5 class="text-left w-full">Pie Chart</h5>
                <ChartCanvas
                    type="pie"
                    :data="pieData"
                    :options="pieOptions"
                ></ChartCanvas>
            </div>
        </div>
        <div class="col-span-12 xl:col-span-6">
            <div class="card flex flex-col items-center">
                <h5 class="text-left w-full">Doughnut Chart</h5>
                <ChartCanvas
                    type="doughnut"
                    :data="pieData"
                    :options="pieOptions"
                ></ChartCanvas>
            </div>
        </div>
        <div class="col-span-12 xl:col-span-6">
            <div class="card flex flex-col items-center">
                <h5 class="text-left w-full">Polar Area Chart</h5>
                <ChartCanvas
                    type="polarArea"
                    :data="polarData"
                    :options="polarOptions"
                ></ChartCanvas>
            </div>
        </div>
        <div class="col-span-12 xl:col-span-6">
            <div class="card flex flex-col items-center">
                <h5 class="text-left w-full">Radar Chart</h5>
                <ChartCanvas
                    type="radar"
                    :data="radarData"
                    :options="radarOptions"
                ></ChartCanvas>
            </div>
        </div>
    </div>
</template>
