<script setup lang="ts">
import { onMounted, ref } from "vue";
import PhotoService, { type Photo } from "../../service/PhotoService";
import ProductService, { type Product } from "../../service/ProductService";

const products = ref<Product[]>([]);
const images = ref<Photo[]>([]);
const imagePreviewOpen = ref(false);
const previewImage = "/demo/images/galleria/galleria11.jpg";
const carouselResponsiveOptions = ref([
    { breakpoint: "1024px", numVisible: 3, numScroll: 3 },
    { breakpoint: "768px", numVisible: 2, numScroll: 2 },
    { breakpoint: "560px", numVisible: 1, numScroll: 1 },
]);

const productService = new ProductService();
const photoService = new PhotoService();

onMounted(() => {
    productService.getProductsSmall().then((data) => (products.value = data));
    photoService.getImages().then((data) => (images.value = data));
});
</script>

<template>
    <div class="grid grid-cols-12 gap-4 w-full [&_.p-component]:w-full">
        <div class="col-span-12">
            <div class="card">
                <h5>Carousel</h5>
                <Carousel
                    :value="products"
                    :numVisible="3"
                    :numScroll="3"
                    :circular="false"
                    :responsiveOptions="carouselResponsiveOptions"
                >
                    <template #item="{ data }: { data: Product }">
                        <div
                            class="rounded m-2 text-center p-2"
                            style="
                                border: 1px solid var(--p-content-border-color);
                            "
                        >
                            <div class="my-5">
                                <img
                                    :src="'/demo/images/product/' + data.image"
                                    :alt="data.name"
                                    class="product-image"
                                />
                            </div>
                            <div>
                                <h5 class="mb-1">
                                    {{ data.name }}
                                </h5>
                                <h6 class="mt-0 mb-3">${{ data.price }}</h6>
                                <Badge
                                    :class="
                                        'product-badge status-' +
                                        (
                                            data.inventoryStatus || ''
                                        ).toLowerCase()
                                    "
                                    >{{ data.inventoryStatus }}</Badge
                                >
                                <div class="my-5">
                                    <Button
                                        icon="pi pi-search"
                                        class="mr-2 p-button-rounded"
                                    ></Button>
                                    <Button
                                        icon="pi pi-star-fill"
                                        class="mr-2 p-button-rounded p-button-success"
                                    ></Button>
                                    <Button
                                        icon="pi pi-cog"
                                        class="p-button-rounded p-button-info"
                                    ></Button>
                                </div>
                            </div>
                        </div>
                    </template>
                </Carousel>
            </div>
        </div>

        <div class="col-span-12">
            <div class="card">
                <h5>Gallery</h5>
                <Gallery style="height: 32rem; max-width: 800px; margin: auto">
                    <GalleryBackdrop />
                    <GalleryPrev
                        ><i class="pi pi-chevron-left"></i
                    ></GalleryPrev>
                    <GalleryNext
                        ><i class="pi pi-chevron-right"></i
                    ></GalleryNext>
                    <GalleryContent>
                        <GalleryItem
                            v-for="(item, index) in images"
                            :key="item.itemImageSrc || index"
                        >
                            <img
                                :src="item.itemImageSrc"
                                :alt="item.alt || `image-${index + 1}`"
                                style="
                                    max-width: 100%;
                                    max-height: 100%;
                                    object-fit: contain;
                                "
                            />
                        </GalleryItem>
                    </GalleryContent>
                    <GalleryFooter>
                        <GalleryThumbnail>
                            <GalleryThumbnailContent>
                                <GalleryThumbnailItem
                                    v-for="(item, index) in images"
                                    :key="item.thumbnailImageSrc || index"
                                    :index="index"
                                >
                                    <img
                                        :src="item.thumbnailImageSrc"
                                        :alt="
                                            item.alt || `thumbnail-${index + 1}`
                                        "
                                        style="
                                            width: 100%;
                                            height: 100%;
                                            object-fit: cover;
                                        "
                                    />
                                </GalleryThumbnailItem>
                            </GalleryThumbnailContent>
                        </GalleryThumbnail>
                    </GalleryFooter>
                </Gallery>
            </div>
        </div>

        <div class="col-span-12">
            <div class="card">
                <h5>Image</h5>
                <div class="flex justify-center">
                    <img
                        :src="previewImage"
                        alt="Image"
                        width="250"
                        style="cursor: pointer"
                        @click="imagePreviewOpen = true"
                    />
                </div>
                <Gallery
                    v-if="imagePreviewOpen"
                    fullscreen
                    @update:fullscreen="imagePreviewOpen = $event"
                >
                    <GalleryBackdrop />
                    <GalleryHeader style="justify-content: flex-end">
                        <GalleryFullScreen
                            ><i class="pi pi-times"></i
                        ></GalleryFullScreen>
                    </GalleryHeader>
                    <GalleryContent>
                        <GalleryItem>
                            <img :src="previewImage" alt="Image" />
                        </GalleryItem>
                    </GalleryContent>
                </Gallery>
            </div>
        </div>
    </div>
</template>
