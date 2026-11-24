<script setup lang="ts">
import { computed } from 'vue';
import AppTopbar from './AppTopbar.vue';
import AppFooter from './AppFooter.vue';
import AppSidebar from './AppSidebar.vue';
import AppConfig from './AppConfig.vue';
import { useLayoutState } from './composables/layout';

const { layoutConfig, layoutState, hideMobileMenu } = useLayoutState();

const containerClass = computed(() => ({
    'layout-overlay': layoutConfig.menuMode === 'overlay',
    'layout-static': layoutConfig.menuMode === 'static',
    'layout-static-inactive': layoutState.staticMenuDesktopInactive && layoutConfig.menuMode === 'static',
    'layout-overlay-active': layoutState.overlayMenuActive,
    'layout-mobile-active': layoutState.staticMenuMobileActive,
    'p-ripple-disabled': !layoutConfig.ripple
}));
</script>

<template>
    <div class="layout-wrapper" :class="containerClass">
        <AppTopbar />
        <AppSidebar />
        <div class="layout-main-container">
            <div class="layout-main">
                <RouterView />
            </div>
            <AppFooter />
        </div>
        <AppConfig />
        <div class="layout-mask" @click="hideMobileMenu"></div>
    </div>
    <Toast />
</template>

<style lang="scss" scoped></style>
