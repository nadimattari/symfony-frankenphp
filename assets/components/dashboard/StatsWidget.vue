<script setup lang="ts">
interface StatCard {
    label: string;
    value: string;
    trend: string;
    note: string;
    icon: string;
    iconClass: string;
    backgroundClass: string;
}

const stats: StatCard[] = [
    {
        label: "Orders",
        value: "152",
        trend: "24 new",
        note: "since last visit",
        icon: "pi pi-shopping-cart",
        iconClass: "text-blue-500",
        backgroundClass: "bg-blue-100 dark:bg-blue-400/10",
    },
    {
        label: "Revenue",
        value: "$2.100",
        trend: "%52+",
        note: "since last week",
        icon: "pi pi-dollar",
        iconClass: "text-orange-500",
        backgroundClass: "bg-orange-100 dark:bg-orange-400/10",
    },
    {
        label: "Customers",
        value: "28441",
        trend: "520",
        note: "newly registered",
        icon: "pi pi-users",
        iconClass: "text-cyan-500",
        backgroundClass: "bg-cyan-100 dark:bg-cyan-400/10",
    },
    {
        label: "Comments",
        value: "152 Unread",
        trend: "85",
        note: "responded",
        icon: "pi pi-comment",
        iconClass: "text-purple-500",
        backgroundClass: "bg-purple-100 dark:bg-purple-400/10",
    },
];
</script>

<template>
    <div
        v-for="stat in stats"
        :key="stat.label"
        class="col-span-12 lg:col-span-6 xl:col-span-3"
    >
        <div class="card mb-0">
            <div class="flex justify-between mb-4">
                <div>
                    <span class="block text-muted-color font-medium mb-4">{{
                        stat.label
                    }}</span>
                    <div
                        class="text-surface-900 dark:text-surface-0 font-medium text-xl"
                    >
                        {{ stat.value }}
                    </div>
                </div>
                <div
                    class="flex items-center justify-center rounded-border"
                    :class="stat.backgroundClass"
                    style="width: 2.5rem; height: 2.5rem"
                >
                    <i
                        :class="[stat.icon, stat.iconClass]"
                        class="text-xl!"
                    ></i>
                </div>
            </div>
            <span class="text-primary font-medium">{{ stat.trend }} </span>
            <span class="text-muted-color">{{ stat.note }}</span>
        </div>
    </div>
</template>

<style lang="scss" scoped></style>
