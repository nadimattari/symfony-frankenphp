<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from "vue";
import type { ChartData, ChartOptions, ChartType } from "chart.js";
import { ChartJS } from "./chartjs";

const props = defineProps<{
    type: ChartType;
    data: ChartData<ChartType>;
    options?: ChartOptions<ChartType>;
}>();

const canvas = ref<HTMLCanvasElement | null>(null);
let chart: ChartJS<ChartType> | null = null;

const render = (): void => {
    if (!canvas.value) {
        return;
    }

    chart?.destroy();
    chart = new ChartJS(canvas.value, {
        type: props.type,
        data: props.data,
        options: props.options,
    });
};

onMounted(render);
onBeforeUnmount(() => {
    chart?.destroy();
    chart = null;
});

// The demos replace the whole data/options object when the theme changes;
// recreating the chart keeps colours and scales in sync without deep mutation.
watch(() => [props.type, props.data, props.options], render);

defineExpose({ getChart: () => chart });
</script>

<template>
    <div class="relative w-full">
        <canvas ref="canvas" role="img"></canvas>
    </div>
</template>
