<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useLayoutState } from './composables/layout';

const { layoutConfig, toggleMenu } = useLayoutState();

const outsideClickListener = ref<EventListener | null>(null);
const topbarMenuActive = ref(false);
const router = useRouter();

onMounted(bindOutsideClickListener);
onBeforeUnmount(unbindOutsideClickListener);

const logoUrl = computed(() => `layout/images/${layoutConfig.darkTheme ? 'logo-white' : 'logo-dark'}.svg`);

const topbarMenuClasses = computed(() => ({
    'layout-topbar-menu-mobile-active': topbarMenuActive.value
}));

function onTopBarMenuButton(): void {
    topbarMenuActive.value = !topbarMenuActive.value;
}

function onSettingsClick(): void {
    topbarMenuActive.value = false;
    void router.push('/documentation');
}

function bindOutsideClickListener(): void {
    if (outsideClickListener.value) {
        return;
    }

    outsideClickListener.value = (event: Event) => {
        if (isOutsideClicked(event)) {
            topbarMenuActive.value = false;
        }
    };

    document.addEventListener('click', outsideClickListener.value);
}

function unbindOutsideClickListener(): void {
    if (!outsideClickListener.value) {
        return;
    }

    document.removeEventListener('click', outsideClickListener.value);
    outsideClickListener.value = null;
}

function isOutsideClicked(event: Event): boolean {
    if (!topbarMenuActive.value) {
        return false;
    }

    const menuEl = document.querySelector<HTMLElement>('.layout-topbar-menu');
    const buttonEl = document.querySelector<HTMLElement>('.layout-topbar-menu-button');
    const target = event.target as Node | null;

    return !(
        (menuEl?.isSameNode(target) ?? false) ||
        (menuEl?.contains(target) ?? false) ||
        (buttonEl?.isSameNode(target) ?? false) ||
        (buttonEl?.contains(target) ?? false)
    );
}
</script>

<template>
    <div class="layout-topbar">
        <router-link to="/" class="layout-topbar-logo">
            <img :src="logoUrl" alt="logo" />
            <span>SAKAI</span>
        </router-link>

        <button class="p-link layout-menu-button layout-topbar-button" type="button" aria-label="Toggle menu" @click="toggleMenu">
            <i class="pi pi-bars"></i>
        </button>

        <button class="p-link layout-topbar-menu-button layout-topbar-button" type="button" aria-label="Toggle user menu" @click="onTopBarMenuButton">
            <i class="pi pi-ellipsis-v"></i>
        </button>

        <div class="layout-topbar-menu" :class="topbarMenuClasses">
            <button type="button" class="p-link layout-topbar-button" @click="onTopBarMenuButton">
                <i class="pi pi-calendar"></i>
                <span>Calendar</span>
            </button>
            <button type="button" class="p-link layout-topbar-button" @click="onTopBarMenuButton">
                <i class="pi pi-user"></i>
                <span>Profile</span>
            </button>
            <button type="button" class="p-link layout-topbar-button" @click="onSettingsClick">
                <i class="pi pi-cog"></i>
                <span>Settings</span>
            </button>
        </div>
    </div>
</template>

<style lang="scss" scoped></style>
