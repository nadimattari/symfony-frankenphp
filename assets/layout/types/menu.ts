import type { MenuItem as PrimeVueMenuItem, MenuItemCommandEvent } from 'primevue/menuitem';

/**
 * Sakai's sidebar model extends PrimeVue's shared MenuItem contract with a
 * `path` fragment used to expand the active branch of the navigation tree.
 */
export interface MenuItem extends PrimeVueMenuItem {
    label: string;
    icon?: string;
    to?: string;
    url?: string;
    target?: string;
    class?: string;
    badge?: string;
    path?: string;
    visible?: boolean;
    disabled?: boolean;
    separator?: boolean;
    command?: (event: MenuItemCommandEvent) => void;
    items?: MenuItem[];
}
