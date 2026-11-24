/*
 * Main TypeScript entry. The built script and CSS are included in base.html.twig.
 */

import './assets/tailwind.css';
import './assets/styles.scss';

import PrimeVue              from 'primevue/config';
import AuraCompat            from '@primeuix/themes/aura-compat';
import AutoComplete          from 'primevue/autocomplete';
import Accordion             from 'primevue/accordion';
import AccordionPanel        from 'primevue/accordionpanel';
import Avatar                from 'primevue/avatar';
import AvatarGroup           from 'primevue/avatargroup';
import Badge                 from 'primevue/badge';
import BlockUI               from 'primevue/blockui';
import Button                from 'primevue/button';
import ButtonGroup           from 'primevue/buttongroup';
import Breadcrumb            from 'primevue/breadcrumb';
import DatePicker            from 'primevue/datepicker';
import Card                  from 'primevue/card';
import Chart                 from 'primevue/chart';
import ChartDataLabels       from 'chartjs-plugin-datalabels';
import { Chart as ChartJS }  from 'chart.js';
import CascadeSelect         from 'primevue/cascadeselect';
import Carousel              from 'primevue/carousel';
import Checkbox              from 'primevue/checkbox';
import Chip                  from 'primevue/chip';
import InputTags             from 'primevue/inputtags';
import ColorPicker           from 'primevue/colorpicker';
import Column                from 'primevue/column';
import ColumnGroup           from 'primevue/columngroup';
import ConfirmDialog         from 'primevue/confirmdialog';
import ConfirmPopup          from 'primevue/confirmpopup';
import ConfirmationService   from 'primevue/confirmationservice';
import ContextMenu           from 'primevue/contextmenu';
import DataTable             from 'primevue/datatable';
import DataView              from 'primevue/dataview';
import DeferredContent       from 'primevue/deferredcontent';
import Dialog                from 'primevue/dialog';
import DialogService         from 'primevue/dialogservice';
import Divider               from 'primevue/divider';
import Dock                  from 'primevue/dock';
import Select                from 'primevue/select';
import DynamicDialog         from 'primevue/dynamicdialog';
import Fieldset              from 'primevue/fieldset';
import FileUpload            from 'primevue/fileupload';
import FloatLabel            from 'primevue/floatlabel';
import Galleria              from 'primevue/galleria';
import IconField             from 'primevue/iconfield';
import Image                 from 'primevue/image';
import Inplace               from 'primevue/inplace';
import InputGroup            from 'primevue/inputgroup';
import InputGroupAddon       from 'primevue/inputgroupaddon';
import InputIcon             from 'primevue/inputicon';
import ToggleSwitch          from 'primevue/toggleswitch';
import InputText             from 'primevue/inputtext';
import InputNumber           from 'primevue/inputnumber';
import Knob                  from 'primevue/knob';
import Listbox               from 'primevue/listbox';
import MegaMenu              from 'primevue/megamenu';
import Menu                  from 'primevue/menu';
import Menubar               from 'primevue/menubar';
import Message               from 'primevue/message';
import Mask                  from 'primevue/mask';
import MultiSelect           from 'primevue/multiselect';
import OrderList             from 'primevue/orderlist';
import OrganizationChart     from 'primevue/organizationchart';
import Popover               from 'primevue/popover';
import Paginator             from 'primevue/paginator';
import Panel                 from 'primevue/panel';
import PanelMenu             from 'primevue/panelmenu';
import Password              from 'primevue/password';
import PickList              from 'primevue/picklist';
import ProgressBar           from 'primevue/progressbar';
import ProgressSpinner       from 'primevue/progressspinner';
import Rating                from 'primevue/rating';
import RadioButton           from 'primevue/radiobutton';
import Ripple                from 'primevue/ripple';
import Row                   from 'primevue/row';
import SelectButton          from 'primevue/selectbutton';
import ScrollPanel           from 'primevue/scrollpanel';
import ScrollTop             from 'primevue/scrolltop';
import Skeleton              from 'primevue/skeleton';
import Slider                from 'primevue/slider';
import Sidebar               from 'primevue/sidebar';
import SpeedDial             from 'primevue/speeddial';
import SplitButton           from 'primevue/splitbutton';
import Splitter              from 'primevue/splitter';
import SplitterPanel         from 'primevue/splitterpanel';
import Steps                 from 'primevue/steps';
import StyleClass            from 'primevue/styleclass';
import Tabs                  from 'primevue/tabs';
import TieredMenu            from 'primevue/tieredmenu';
import Textarea              from 'primevue/textarea';
import Toast                 from 'primevue/toast';
import ToastService          from 'primevue/toastservice';
import Toolbar               from 'primevue/toolbar';
import TabPanel              from 'primevue/tabpanel';
import Tag                   from 'primevue/tag';
import Terminal              from 'primevue/terminal';
import Timeline              from 'primevue/timeline';
import ToggleButton          from 'primevue/togglebutton';
import Tooltip               from 'primevue/tooltip';
import Tree                  from 'primevue/tree';
import TreeSelect            from 'primevue/treeselect';
import TreeTable             from 'primevue/treetable';
import VirtualScroller       from 'primevue/virtualscroller';

import BlockViewer from './components/BlockViewer.vue';

import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';

// v-badge was removed in PrimeVue 5. The Sakai port will replace its usages;
// this no-op keeps the current demo templates from logging an unknown directive.
const LegacyBadgeDirective = {};

const app = createApp(App);
app.use(PrimeVue, {
    ripple: true,
    license: window.PRIMEUI_LICENSE_KEY,
    theme: {
        preset: AuraCompat,
        options: {
            prefix: 'p',
            darkModeSelector: '.app-dark',
            // Encore loads the app CSS via <link> while PrimeVue injects its
            // layer-order <style> at the very start of <head>. Give PrimeVue the
            // full order so component styles (primeui) beat Tailwind's base
            // reset but stay below utilities. `primevue` is the legacy v3
            // theme.css layer; keeping it first stops those rules from winning
            // until Task 19 removes the legacy theme.
            cssLayer: {
                name: 'primeui',
                order: 'primevue, theme, base, primeui, components, utilities'
            },
            cssVariables: true
        }
    }
});
app.use(createPinia());
app.use(router);
app.use(ToastService);
app.use(DialogService);
app.use(ConfirmationService);
ChartJS.register(ChartDataLabels);

app.directive('tooltip',    Tooltip);
app.directive('badge',      LegacyBadgeDirective);
app.directive('ripple',     Ripple);
app.directive('styleClass', StyleClass);
app.directive('mask',       Mask);

app.component('BlockViewer', BlockViewer);

app.component('Accordion',             Accordion);
app.component('AccordionTab',          AccordionPanel);
app.component('AutoComplete',          AutoComplete);
app.component('Avatar',                Avatar);
app.component('AvatarGroup',           AvatarGroup);
app.component('Badge',                 Badge);
app.component('BlockUI',               BlockUI);
app.component('Breadcrumb',            Breadcrumb);
app.component('Button',                Button);
app.component('ButtonGroup',           ButtonGroup);
app.component('Calendar',              DatePicker);
app.component('DatePicker',            DatePicker);
app.component('Card',                  Card);
app.component('Chart',                 Chart);
app.component('Carousel',              Carousel);
app.component('CascadeSelect',         CascadeSelect);
app.component('Checkbox',              Checkbox);
app.component('Chip',                  Chip);
app.component('Chips',                 InputTags);
app.component('InputTags',             InputTags);
app.component('ColorPicker',           ColorPicker);
app.component('Column',                Column);
app.component('ColumnGroup',           ColumnGroup);
app.component('ConfirmDialog',         ConfirmDialog);
app.component('ConfirmPopup',          ConfirmPopup);
app.component('ContextMenu',           ContextMenu);
app.component('DataTable',             DataTable);
app.component('DataView',              DataView);
app.component('DataViewLayoutOptions', SelectButton);
app.component('DeferredContent',       DeferredContent);
app.component('Dialog',                Dialog);
app.component('Divider',               Divider);
app.component('Dock',                  Dock);
app.component('Dropdown',              Select);
app.component('Select',                Select);
app.component('DynamicDialog',         DynamicDialog);
app.component('Fieldset',              Fieldset);
app.component('FileUpload',            FileUpload);
app.component('FloatLabel',            FloatLabel);
app.component('Galleria',              Galleria);
app.component('IconField',             IconField);
app.component('Image',                 Image);
app.component('InlineMessage',         Message);
app.component('Inplace',               Inplace);
app.component('InputGroup',            InputGroup);
app.component('InputGroupAddon',       InputGroupAddon);
app.component('InputIcon',             InputIcon);
app.component('InputNumber',           InputNumber);
app.component('InputSwitch',           ToggleSwitch);
app.component('ToggleSwitch',          ToggleSwitch);
app.component('InputText',             InputText);
app.component('Knob',                  Knob);
app.component('Listbox',               Listbox);
app.component('MegaMenu',              MegaMenu);
app.component('Menu',                  Menu);
app.component('Menubar',               Menubar);
app.component('Message',               Message);
app.component('MultiSelect',           MultiSelect);
app.component('OrderList',             OrderList);
app.component('OrganizationChart',     OrganizationChart);
app.component('OverlayPanel',          Popover);
app.component('Popover',               Popover);
app.component('Paginator',             Paginator);
app.component('Panel',                 Panel);
app.component('PanelMenu',             PanelMenu);
app.component('Password',              Password);
app.component('PickList',              PickList);
app.component('ProgressBar',           ProgressBar);
app.component('ProgressSpinner',       ProgressSpinner);
app.component('RadioButton',           RadioButton);
app.component('Rating',                Rating);
app.component('Row',                   Row);
app.component('SelectButton',          SelectButton);
app.component('ScrollPanel',           ScrollPanel);
app.component('ScrollTop',             ScrollTop);
app.component('Slider',                Slider);
app.component('Sidebar',               Sidebar);
app.component('Skeleton',              Skeleton);
app.component('SpeedDial',             SpeedDial);
app.component('SplitButton',           SplitButton);
app.component('Splitter',              Splitter);
app.component('SplitterPanel',         SplitterPanel);
app.component('Steps',                 Steps);
app.component('TabMenu',               Tabs);
app.component('TabView',               Tabs);
app.component('TabPanel',              TabPanel);
app.component('Tag',                   Tag);
app.component('Textarea',              Textarea);
app.component('Terminal',              Terminal);
app.component('TieredMenu',            TieredMenu);
app.component('Timeline',              Timeline);
app.component('Toast',                 Toast);
app.component('Toolbar',               Toolbar);
app.component('ToggleButton',          ToggleButton);
app.component('Tree',                  Tree);
app.component('TreeSelect',            TreeSelect);
app.component('TreeTable',             TreeTable);
app.component('VirtualScroller',       VirtualScroller);

app.mount('#vue-app');
