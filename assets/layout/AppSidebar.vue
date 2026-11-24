<script setup lang="ts">
import { onBeforeUnmount, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import AppMenu from './AppMenu.vue';
import { useLayoutState } from './composables/layout';

const { layoutState, isDesktop, hasOpenOverlay } = useLayoutState();
const route = useRoute();
const sidebarRef = ref<HTMLElement | null>(null);
let outsideClickListener: EventListener | null = null;

watch(
    () => route.path,
    (newPath) => {
        layoutState.activePath = isDesktop() ? null : newPath;
        layoutState.overlayMenuActive = false;
        layoutState.staticMenuMobileActive = false;
        layoutState.menuHoverActive = false;
    },
    { immediate: true }
);

watch(hasOpenOverlay, (open) => {
    if (!isDesktop()) {
        return;
    }

    if (open) {
        bindOutsideClickListener();
    } else {
        unbindOutsideClickListener();
    }
});

function bindOutsideClickListener(): void {
    if (outsideClickListener) {
        return;
    }

    outsideClickListener = (event: Event) => {
        if (isOutsideClicked(event)) {
            layoutState.overlayMenuActive = false;
        }
    };

    document.addEventListener('click', outsideClickListener);
}

function unbindOutsideClickListener(): void {
    if (!outsideClickListener) {
        return;
    }

    document.removeEventListener('click', outsideClickListener);
    outsideClickListener = null;
}

function isOutsideClicked(event: Event): boolean {
    const sidebarEl = sidebarRef.value;
    const topbarEl = document.querySelector<HTMLElement>('.layout-menu-button');
    const target = event.target as Node | null;

    return !(
        (sidebarEl?.isSameNode(target) ?? false) ||
        (sidebarEl?.contains(target) ?? false) ||
        (topbarEl?.isSameNode(target) ?? false) ||
        (topbarEl?.contains(target) ?? false)
    );
}

onBeforeUnmount(unbindOutsideClickListener);
</script>

<template>
    <div ref="sidebarRef" class="layout-sidebar">
        <AppMenu />
    </div>
</template>

<style lang="scss" scoped></style>
