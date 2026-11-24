<script setup lang="ts">
import { onMounted, ref } from 'vue';
import Badge from 'primevue/badge';
import Button from 'primevue/button';
import Carousel from 'primevue/carousel';
import Galleria from 'primevue/galleria';
import Image from 'primevue/image';
import PhotoService, { type Photo } from '../../service/PhotoService';
import ProductService, { type Product } from '../../service/ProductService';

const products = ref<Product[]>([]);
const images = ref<Photo[]>([]);
const galleriaResponsiveOptions = ref([
    { breakpoint: '1024px', numVisible: 5 },
    { breakpoint: '960px', numVisible: 4 },
    { breakpoint: '768px', numVisible: 3 },
    { breakpoint: '560px', numVisible: 1 }
]);
const carouselResponsiveOptions = ref([
    { breakpoint: '1024px', numVisible: 3, numScroll: 3 },
    { breakpoint: '768px', numVisible: 2, numScroll: 2 },
    { breakpoint: '560px', numVisible: 1, numScroll: 1 }
]);

const productService = new ProductService();
const photoService = new PhotoService();

onMounted(() => {
    productService.getProductsSmall().then((data) => (products.value = data));
    photoService.getImages().then((data) => (images.value = data));
});
</script>

<template>
    <div class="grid p-fluid">
        <div class="col-12">
            <div class="card">
                <h5>Carousel</h5>
                <Carousel :value="products" :numVisible="3" :numScroll="3" :circular="false" :responsiveOptions="carouselResponsiveOptions">
                    <template #item="{ data }: { data: Product }">
                        <div class="border-round m-2 text-center p-2" style="border: 1px solid var(--surface-d)">
                            <div class="my-5">
                                <img :src="'demo/images/product/' + data.image" :alt="data.name" class="product-image" />
                            </div>
                            <div>
                                <h5 class="mb-1">
                                    {{ data.name }}
                                </h5>
                                <h6 class="mt-0 mb-3">${{ data.price }}</h6>
                                <Badge :class="'product-badge status-' + (data.inventoryStatus || '').toLowerCase()">{{ data.inventoryStatus }}</Badge>
                                <div class="my-5">
                                    <Button icon="pi pi-search" class="mr-2 p-button-rounded"></Button>
                                    <Button icon="pi pi-star-fill" class="mr-2 p-button-rounded p-button-success"></Button>
                                    <Button icon="pi pi-cog" class="p-button-rounded p-button-info"></Button>
                                </div>
                            </div>
                        </div>
                    </template>
                </Carousel>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <h5>Galleria</h5>
                <Galleria :value="images" :responsiveOptions="galleriaResponsiveOptions" :numVisible="7" :circular="true" containerStyle="max-width: 800px; margin: auto">
                    <template #item="{ item }: { item: Photo }">
                        <img :src="item.itemImageSrc" :alt="item.alt" style="width: 100%; display: block" />
                    </template>
                    <template #thumbnail="{ item }: { item: Photo }">
                        <img :src="item.thumbnailImageSrc" :alt="item.alt" style="width: 100%; display: block;" />
                    </template>
                </Galleria>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <h5>Image</h5>
                <div class="flex justify-content-center">
                    <Image :src="'demo/images/galleria/galleria11.jpg'" alt="Image" width="250" preview />
                </div>
            </div>
        </div>
    </div>
</template>
