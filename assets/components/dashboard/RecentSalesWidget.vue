<script setup lang="ts">
import { onMounted, ref } from 'vue';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import ProductService, { type Product } from '../../service/ProductService';

const products = ref<Product[]>([]);

function formatCurrency(value: number | undefined): string {
    return (value ?? 0).toLocaleString('en-US', { style: 'currency', currency: 'USD' });
}

onMounted(() => {
    const productService = new ProductService();
    productService.getProductsSmall().then((data: Product[]) => {
        products.value = data;
    });
});
</script>

<template>
    <div class="card">
        <div class="font-semibold text-xl mb-4">Recent Sales</div>
        <DataTable :value="products" :rows="5" paginator>
            <Column style="width: 15%" header="Image">
                <template #body="{ data }: { data: Product }">
                    <img :src="`/demo/images/product/${data.image}`" :alt="data.image" width="50" class="shadow" />
                </template>
            </Column>
            <Column field="name" header="Name" sortable style="width: 35%"></Column>
            <Column field="price" header="Price" sortable style="width: 35%">
                <template #body="{ data }: { data: Product }">
                    {{ formatCurrency(data.price) }}
                </template>
            </Column>
            <Column style="width: 15%" header="View">
                <template #body>
                    <Button icon="pi pi-search" type="button" text></Button>
                </template>
            </Column>
        </DataTable>
    </div>
</template>

<style lang="scss" scoped></style>
