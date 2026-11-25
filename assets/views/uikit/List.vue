<script setup lang="ts">
import { onMounted, ref } from 'vue';
import ProductService, { type Product } from '../../service/ProductService';

interface City {
    name: string;
    code: string;
}

interface SortOption {
    label: string;
    value: string;
}

const picklistValue = ref<City[][]>([
    [
        { name: 'San Francisco', code: 'SF' },
        { name: 'London', code: 'LDN' },
        { name: 'Paris', code: 'PRS' },
        { name: 'Istanbul', code: 'IST' },
        { name: 'Berlin', code: 'BRL' },
        { name: 'Barcelona', code: 'BRC' },
        { name: 'Rome', code: 'RM' }
    ],
    []
]);

const orderlistValue = ref<City[]>([
    { name: 'San Francisco', code: 'SF' },
    { name: 'London', code: 'LDN' },
    { name: 'Paris', code: 'PRS' },
    { name: 'Istanbul', code: 'IST' },
    { name: 'Berlin', code: 'BRL' },
    { name: 'Barcelona', code: 'BRC' },
    { name: 'Rome', code: 'RM' }
]);

const dataviewValue = ref<Product[] | null>(null);
const layout = ref<'grid' | 'list'>('grid');
const layoutOptions = ref<Array<'grid' | 'list'>>(['grid', 'list']);
const sortKey = ref<SortOption | null>(null);
const sortOrder = ref<number | null>(null);
const sortField = ref<string | null>(null);
const sortOptions = ref<SortOption[]>([
    { label: 'Price High to Low', value: '!price' },
    { label: 'Price Low to High', value: 'price' }
]);

const productService = new ProductService();

onMounted(() => {
    productService.getProductsSmall().then((data) => (dataviewValue.value = data));
});

const onSortChange = (event: { value: SortOption }): void => {
    const value = event.value.value;
    const sortValue = event.value;

    if (value.indexOf('!') === 0) {
        sortOrder.value = -1;
        sortField.value = value.substring(1, value.length);
        sortKey.value = sortValue;
    } else {
        sortOrder.value = 1;
        sortField.value = value;
        sortKey.value = sortValue;
    }
};

const getSeverity = (product: Product): string | undefined => {
    switch (product.inventoryStatus) {
        case 'INSTOCK':
            return 'success';
        case 'LOWSTOCK':
            return 'warning';
        case 'OUTOFSTOCK':
            return 'danger';
        default:
            return undefined;
    }
};

const getSlotItem = (slotProps: any): any => slotProps.item;
</script>

<template>
    <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12">
            <div class="card">
                <h5>DataView</h5>
                <DataView :value="dataviewValue" :layout="layout" :paginator="true" :rows="9" :sortOrder="sortOrder" :sortField="sortField">
                    <template #header>
                        <div class="grid grid-cols-12 gap-0">
                            <div class="col-span-6 text-left">
                                <Select v-model="sortKey" :options="sortOptions" optionLabel="label" placeholder="Sort By Price" @change="onSortChange($event)" />
                            </div>
                            <div class="col-span-6 text-right">
                                <SelectButton v-model="layout" :options="layoutOptions" :allowEmpty="false">
                                    <template #option="slotProps">
                                        <i :class="[(slotProps as any).option === 'list' ? 'pi pi-bars' : 'pi pi-table']" />
                                    </template>
                                </SelectButton>
                            </div>
                        </div>
                    </template>

                    <template #list="{ items }: { items: any[] }">
                        <div class="grid grid-cols-12 gap-0">
                            <div v-for="(item, index) in items" :key="index" class="col-span-12">
                                <div class="flex flex-col sm:flex-row sm:items-center p-4 gap-3" :class="{ 'border border-surface-200': index !== 0 }">
                                    <div class="md:w-10rem relative">
                                        <img class="block xl:block mx-auto rounded w-full" :src="`/demo/images/product/${item.image}`" :alt="item.name" />
                                        <Tag :value="item.inventoryStatus" :severity="getSeverity(item)" class="absolute" style="left: 4px; top: 4px"></Tag>
                                    </div>
                                    <div class="flex flex-col md:flex-row justify-between md:items-center flex-1 gap-4">
                                        <div class="flex flex-row md:flex-col justify-between items-start gap-2">
                                            <div>
                                                <span class="font-medium text-surface-500 text-sm">{{ item.category }}</span>
                                                <div class="text-lg font-medium text-surface-900 mt-2">{{ item.name }}</div>
                                            </div>
                                            <div class="bg-surface-100 p-1" style="border-radius: 30px">
                                                <div class="bg-surface-0 flex items-center gap-2 justify-center py-1 px-2" style="border-radius: 30px; box-shadow: 0px 1px 2px 0px rgba(0, 0, 0, 0.04), 0px 1px 2px 0px rgba(0, 0, 0, 0.06)">
                                                    <span class="text-surface-900 font-medium text-sm">{{ item.rating }}</span>
                                                    <i class="pi pi-star-fill text-yellow-500"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex flex-col md:items-end gap-5">
                                            <span class="text-xl font-semibold text-surface-900">${{ item.price }}</span>
                                            <div class="flex flex-row-reverse md:flex-row gap-2">
                                                <Button icon="pi pi-heart" outlined></Button>
                                                <Button icon="pi pi-shopping-cart" label="Buy Now" :disabled="item.inventoryStatus === 'OUTOFSTOCK'" class="flex-auto md:flex-initial whitespace-nowrap"></Button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <template #grid="{ items }: { items: any[] }">
                        <div class="grid grid-cols-12 gap-0">
                            <div v-for="(item, index) in items" :key="index" class="col-span-12 sm:col-span-6 md:col-span-4 p-2">
                                <div class="p-4 border border-surface-200 bg-surface-0 rounded flex flex-col">
                                    <div class="bg-surface-50 flex justify-center rounded p-3">
                                        <div class="relative mx-auto">
                                            <img class="rounded w-full" :src="`/demo/images/product/${item.image}`" :alt="item.name" style="max-width: 300px" />
                                            <Tag :value="item.inventoryStatus" :severity="getSeverity(item)" class="absolute" style="left: 4px; top: 4px"></Tag>
                                        </div>
                                    </div>
                                    <div class="pt-4">
                                        <div class="flex flex-row justify-between items-start gap-2">
                                            <div>
                                                <span class="font-medium text-surface-500 text-sm">{{ item.category }}</span>
                                                <div class="text-lg font-medium text-surface-900 mt-1">{{ item.name }}</div>
                                            </div>
                                            <div class="bg-surface-100 p-1" style="border-radius: 30px">
                                                <div class="bg-surface-0 flex items-center gap-2 justify-center py-1 px-2" style="border-radius: 30px; box-shadow: 0px 1px 2px 0px rgba(0, 0, 0, 0.04), 0px 1px 2px 0px rgba(0, 0, 0, 0.06)">
                                                    <span class="text-surface-900 font-medium text-sm">{{ item.rating }}</span>
                                                    <i class="pi pi-star-fill text-yellow-500"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex flex-col gap-4 mt-4">
                                            <span class="text-2xl font-semibold text-surface-900">${{ item.price }}</span>
                                            <div class="flex gap-2">
                                                <Button icon="pi pi-shopping-cart" label="Buy Now" :disabled="item.inventoryStatus === 'OUTOFSTOCK'" class="flex-auto whitespace-nowrap"></Button>
                                                <Button icon="pi pi-heart" outlined></Button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </DataView>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-8">
            <div class="card">
                <h5>PickList</h5>
                <PickList v-model="picklistValue" listStyle="height:250px" dataKey="code">
                    <template #sourceheader> From </template>
                    <template #targetheader> To </template>
                    <template #option="{ option }: any">
                        <div>{{ option.name }}</div>
                    </template>
                </PickList>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-4">
            <div class="card">
                <h5>OrderList</h5>
                <OrderList v-model="orderlistValue" listStyle="height:250px" dataKey="code" :rows="10">
                    <template #header> Cities </template>
                    <template #option="{ option }: any">
                        <div>{{ option.name }}</div>
                    </template>
                </OrderList>
            </div>
        </div>
    </div>
</template>
