import { computed, reactive, toRefs, unref, type Ref } from 'vue';

export type MenuMode = 'static' | 'overlay';
export type InputVariant = 'outlined' | 'filled';
export type PresetName = 'AuraCompat' | 'Aura' | 'Lara' | 'Nora';

export interface LayoutConfig {
    ripple: boolean;
    darkTheme: boolean;
    inputVariant: InputVariant;
    menuMode: MenuMode;
    preset: PresetName;
    primary: string;
    surface: string | null;
    scale: number;
    activeMenuItem: string | null;
    /**
     * Legacy change signal watched by views that still derive chart colours from the
     * v3 theme name (see Chart.vue). Updated whenever a v5 preset/palette changes.
     */
    theme: string;
}

export interface LayoutState {
    staticMenuDesktopInactive: boolean;
    overlayMenuActive: boolean;
    profileSidebarVisible: boolean;
    configSidebarVisible: boolean;
    staticMenuMobileActive: boolean;
    menuHoverActive: boolean;
    activePath: string | null;
}

export type ActiveMenuItem = string | Ref<string | null> | null;

const layoutConfig = reactive<LayoutConfig>({
    ripple: true,
    darkTheme: false,
    inputVariant: 'outlined',
    menuMode: 'static',
    preset: 'AuraCompat',
    primary: 'emerald',
    surface: null,
    scale: 14,
    activeMenuItem: null,
    theme: 'AuraCompat-emerald'
});

const layoutState = reactive<LayoutState>({
    staticMenuDesktopInactive: false,
    overlayMenuActive: false,
    profileSidebarVisible: false,
    configSidebarVisible: false,
    staticMenuMobileActive: false,
    menuHoverActive: false,
    activePath: null
});

/**
 * Raw reactive API matching the upstream Sakai 5 composable. Used by components
 * that have been ported to the v5 layout shape.
 */
export function useLayoutState() {
    const isDesktop = (): boolean => window.innerWidth > 991;

    const executeDarkModeToggle = (): void => {
        layoutConfig.darkTheme = !layoutConfig.darkTheme;
        document.documentElement.classList.toggle('app-dark', layoutConfig.darkTheme);
    };

    // Upstream wraps this in startViewTransition(), but that makes the class toggle
    // asynchronous and can be skipped (rapid toggles / background tabs). Keep the
    // state change synchronous and deterministic.
    const toggleDarkMode = (): void => {
        executeDarkModeToggle();
    };

    const setDarkMode = (value: boolean): void => {
        if (layoutConfig.darkTheme !== value) {
            toggleDarkMode();
        }
    };

    const toggleConfigSidebar = (): void => {
        layoutState.configSidebarVisible = !layoutState.configSidebarVisible;
    };

    const hideMobileMenu = (): void => {
        layoutState.staticMenuMobileActive = false;
    };

    const toggleMenu = (): void => {
        if (isDesktop()) {
            if (layoutConfig.menuMode === 'static') {
                layoutState.staticMenuDesktopInactive = !layoutState.staticMenuDesktopInactive;
            }

            if (layoutConfig.menuMode === 'overlay') {
                layoutState.overlayMenuActive = !layoutState.overlayMenuActive;
            }
        } else {
            layoutState.staticMenuMobileActive = !layoutState.staticMenuMobileActive;
        }
    };

    const changeMenuMode = (value: MenuMode): void => {
        layoutConfig.menuMode = value;
        layoutState.staticMenuDesktopInactive = false;
        layoutState.overlayMenuActive = false;
        layoutState.staticMenuMobileActive = false;
        layoutState.menuHoverActive = false;
    };

    const setScale = (scale: number): void => {
        layoutConfig.scale = scale;
    };

    const setActiveMenuItem = (item: ActiveMenuItem): void => {
        layoutConfig.activeMenuItem = unref(item);
    };

    const isSidebarActive = computed(() => layoutState.overlayMenuActive || layoutState.staticMenuMobileActive);
    const isDarkTheme = computed(() => layoutConfig.darkTheme);
    const hasOpenOverlay = computed(() => layoutState.overlayMenuActive);

    return {
        layoutConfig,
        layoutState,
        setScale,
        setActiveMenuItem,
        toggleMenu,
        onMenuToggle: toggleMenu,
        toggleDarkMode,
        setDarkMode,
        toggleConfigSidebar,
        hideMobileMenu,
        changeMenuMode,
        isDesktop,
        isSidebarActive,
        isDarkTheme,
        hasOpenOverlay
    };
}

/**
 * Compatibility view for the components and views not yet ported to the v5
 * layout shape. Their templates still read `layoutConfig.foo.value`, which
 * toRefs preserves. Remove this wrapper when Tasks 11-13 finish the port.
 */
export function useLayout() {
    const state = useLayoutState();

    return {
        ...state,
        layoutConfig: toRefs(layoutConfig) as { [K in keyof LayoutConfig]: Ref<LayoutConfig[K]> },
        layoutState: toRefs(layoutState) as { [K in keyof LayoutState]: Ref<LayoutState[K]> }
    };
}
