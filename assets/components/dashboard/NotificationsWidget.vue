<script setup lang="ts">
import { ref } from "vue";
import Button from "primevue/button";
import Menu from "primevue/menu";
import type { MenuItem } from "primevue/menuitem";

interface Notification {
    icon: string;
    iconClass: string;
    iconBackground: string;
    text: string;
    action?: string;
}

interface NotificationGroup {
    label: string;
    items: Notification[];
}

const menu = ref<InstanceType<typeof Menu> | null>(null);

const items = ref<MenuItem[]>([
    { label: "Add New", icon: "pi pi-fw pi-plus" },
    { label: "Remove", icon: "pi pi-fw pi-trash" },
]);

const groups: NotificationGroup[] = [
    {
        label: "TODAY",
        items: [
            {
                icon: "pi pi-dollar",
                iconClass: "text-blue-500",
                iconBackground: "bg-blue-100 dark:bg-blue-400/10",
                text: "Richard Jones has purchased a blue t-shirt for",
                action: "$79.00",
            },
            {
                icon: "pi pi-download",
                iconClass: "text-orange-500",
                iconBackground: "bg-orange-100 dark:bg-orange-400/10",
                text: "Your request for withdrawal of",
                action: "$2500.00 has been initiated.",
            },
        ],
    },
    {
        label: "YESTERDAY",
        items: [
            {
                icon: "pi pi-dollar",
                iconClass: "text-blue-500",
                iconBackground: "bg-blue-100 dark:bg-blue-400/10",
                text: "Keyser Wick has purchased a black jacket for",
                action: "$59.00",
            },
            {
                icon: "pi pi-question",
                iconClass: "text-pink-500",
                iconBackground: "bg-pink-100 dark:bg-pink-400/10",
                text: "Jane Davis has posted a new question about your product.",
            },
        ],
    },
    {
        label: "LAST WEEK",
        items: [
            {
                icon: "pi pi-arrow-up",
                iconClass: "text-green-500",
                iconBackground: "bg-green-100 dark:bg-green-400/10",
                text: "Your revenue has increased by",
                action: "%25",
            },
            {
                icon: "pi pi-heart",
                iconClass: "text-purple-500",
                iconBackground: "bg-purple-100 dark:bg-purple-400/10",
                text: "12 users have added your products to their wishlist.",
            },
        ],
    },
];

function toggleMenu(event: Event): void {
    menu.value?.toggle(event);
}
</script>

<template>
    <div class="card">
        <div class="flex items-center justify-between mb-6">
            <div class="font-semibold text-xl">Notifications</div>
            <div>
                <Button
                    icon="pi pi-ellipsis-v"
                    text
                    rounded
                    @click="toggleMenu"
                />
                <Menu ref="menu" popup :model="items" class="min-w-40!" />
            </div>
        </div>

        <template v-for="group in groups" :key="group.label">
            <span class="block text-muted-color font-medium mb-4">{{
                group.label
            }}</span>
            <ul class="p-0 mx-0 mt-0 mb-6 list-none">
                <li
                    v-for="notification in group.items"
                    :key="notification.text"
                    class="flex items-center py-2 border-b border-surface"
                >
                    <div
                        class="w-12 h-12 flex items-center justify-center mr-4 shrink-0"
                        :class="notification.iconBackground"
                        style="border-radius: 50%"
                    >
                        <i
                            :class="[notification.icon, notification.iconClass]"
                            class="text-xl!"
                        ></i>
                    </div>
                    <span
                        class="text-surface-900 dark:text-surface-0 leading-normal"
                    >
                        {{ notification.text }}
                        <span
                            v-if="notification.action"
                            class="text-primary font-bold"
                            >{{ notification.action }}</span
                        >
                    </span>
                </li>
            </ul>
        </template>
    </div>
</template>

<style lang="scss" scoped></style>
