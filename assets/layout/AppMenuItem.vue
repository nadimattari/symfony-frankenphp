<script setup lang="ts">
import { computed } from "vue";
import { useLayoutState } from "./composables/layout";
import type { MenuItem } from "./types/menu";

const props = withDefaults(
    defineProps<{
        item: MenuItem;
        root?: boolean;
        parentPath?: string | null;
    }>(),
    {
        root: true,
        parentPath: null,
    },
);

const { layoutState, isDesktop } = useLayoutState();

const fullPath = computed(() => {
    if (!props.item.path) {
        return null;
    }

    return props.parentPath
        ? props.parentPath + props.item.path
        : props.item.path;
});

const isActive = computed(() => {
    if (props.item.path) {
        return (
            layoutState.activePath !== null &&
            fullPath.value !== null &&
            layoutState.activePath.startsWith(fullPath.value)
        );
    }

    return layoutState.activePath === props.item.to;
});

function itemClick(event: Event, item: MenuItem): void {
    if (item.disabled) {
        event.preventDefault();
        return;
    }

    item.command?.({ originalEvent: event, item });

    if (item.items) {
        if (isActive.value) {
            layoutState.activePath =
                layoutState.activePath?.replace(item.path ?? "", "") ?? null;
        } else {
            layoutState.activePath = fullPath.value;
            layoutState.menuHoverActive = true;
        }

        return;
    }

    layoutState.overlayMenuActive = false;
    layoutState.staticMenuMobileActive = false;
    layoutState.menuHoverActive = false;
}

function onMouseEnter(): void {
    if (
        isDesktop() &&
        props.root &&
        props.item.items &&
        layoutState.menuHoverActive
    ) {
        layoutState.activePath = fullPath.value;
    }
}
</script>

<template>
    <li :class="{ 'layout-root-menuitem': root, 'active-menuitem': isActive }">
        <div
            v-if="root && item.visible !== false"
            class="layout-menuitem-root-text"
        >
            {{ item.label }}
        </div>
        <a
            v-if="(!item.to || item.items) && item.visible !== false"
            :href="item.url"
            @click="itemClick($event, item)"
            @mouseenter="onMouseEnter"
            :class="item.class"
            :target="item.target"
            tabindex="0"
        >
            <i :class="item.icon" class="layout-menuitem-icon"></i>
            <span class="layout-menuitem-text">{{ item.label }}</span>
            <i
                v-if="item.items"
                class="pi pi-fw pi-angle-down layout-submenu-toggler"
            ></i>
        </a>
        <router-link
            v-if="item.to && !item.items && item.visible !== false"
            @click="itemClick($event, item)"
            @mouseenter="onMouseEnter"
            exact-active-class="active-route"
            :class="item.class"
            tabindex="0"
            :to="item.to"
        >
            <i :class="item.icon" class="layout-menuitem-icon"></i>
            <span class="layout-menuitem-text">{{ item.label }}</span>
            <i
                v-if="item.items"
                class="pi pi-fw pi-angle-down layout-submenu-toggler"
            ></i>
        </router-link>
        <Transition
            v-if="item.items && item.visible !== false"
            name="layout-submenu"
        >
            <ul v-show="root ? true : isActive" class="layout-submenu">
                <AppMenuItem
                    v-for="child in item.items"
                    :key="
                        child.label + '_' + String(child.to ?? child.path ?? '')
                    "
                    :item="child"
                    :root="false"
                    :parentPath="fullPath"
                />
            </ul>
        </Transition>
    </li>
</template>

<style lang="scss" scoped></style>
