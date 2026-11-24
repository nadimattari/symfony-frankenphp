<script setup lang="ts">
import { ref } from 'vue';
import Button from 'primevue/button';
import Menu from 'primevue/menu';
import type { MenuItem } from 'primevue/menuitem';

interface BestSeller {
    name: string;
    category: string;
    share: number;
    barClass: string;
    labelClass: string;
}

const menu = ref<InstanceType<typeof Menu> | null>(null);

const items = ref<MenuItem[]>([
    { label: 'Add New', icon: 'pi pi-fw pi-plus' },
    { label: 'Remove', icon: 'pi pi-fw pi-trash' }
]);

const products: BestSeller[] = [
    { name: 'Space T-Shirt', category: 'Clothing', share: 50, barClass: 'bg-orange-500', labelClass: 'text-orange-500' },
    { name: 'Portal Sticker', category: 'Accessories', share: 16, barClass: 'bg-cyan-500', labelClass: 'text-cyan-500' },
    { name: 'Supernova Sticker', category: 'Accessories', share: 67, barClass: 'bg-pink-500', labelClass: 'text-pink-500' },
    { name: 'Wonders Notebook', category: 'Office', share: 35, barClass: 'bg-green-500', labelClass: 'text-primary' },
    { name: 'Mat Black Case', category: 'Accessories', share: 75, barClass: 'bg-purple-500', labelClass: 'text-purple-500' },
    { name: 'Robots T-Shirt', category: 'Clothing', share: 40, barClass: 'bg-teal-500', labelClass: 'text-teal-500' }
];

function toggleMenu(event: Event): void {
    menu.value?.toggle(event);
}
</script>

<template>
    <div class="card">
        <div class="flex justify-between items-center mb-6">
            <div class="font-semibold text-xl">Best Selling Products</div>
            <div>
                <Button icon="pi pi-ellipsis-v" text rounded @click="toggleMenu" />
                <Menu ref="menu" popup :model="items" class="min-w-40!" />
            </div>
        </div>
        <ul class="list-none p-0 m-0">
            <li v-for="product in products" :key="product.name" class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
                <div>
                    <span class="text-surface-900 dark:text-surface-0 font-medium mr-2 mb-1 md:mb-0">{{ product.name }}</span>
                    <div class="mt-1 text-muted-color">{{ product.category }}</div>
                </div>
                <div class="mt-2 md:mt-0 flex items-center" :class="product.name !== 'Space T-Shirt' ? 'md:ml-20' : ''">
                    <div class="bg-surface-300 dark:bg-surface-500 rounded-border overflow-hidden w-40 lg:w-24" style="height: 8px">
                        <div class="h-full" :class="product.barClass" :style="{ width: `${product.share}%` }"></div>
                    </div>
                    <span class="ml-4 font-medium" :class="product.labelClass">%{{ product.share }}</span>
                </div>
            </li>
        </ul>
    </div>
</template>

<style lang="scss" scoped></style>
