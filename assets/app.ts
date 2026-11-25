/*
 * Main TypeScript entry. The built script and CSS are included in base.html.twig.
 */

import "./assets/tailwind.css";
import "./assets/styles.scss";

import PrimeVue from "primevue/config";
import AuraCompat from "@primeuix/themes/aura-compat";
import AutoComplete from "primevue/autocomplete";
import Accordion from "primevue/accordion";
import AccordionPanel from "primevue/accordionpanel";
import AccordionHeader from "primevue/accordionheader";
import AccordionContent from "primevue/accordioncontent";
import Avatar from "primevue/avatar";
import AvatarGroup from "primevue/avatargroup";
import Badge from "primevue/badge";
import BlockUI from "primevue/blockui";
import Button from "primevue/button";
import ButtonGroup from "primevue/buttongroup";
import Breadcrumb from "primevue/breadcrumb";
import DatePicker from "primevue/datepicker";
import Card from "primevue/card";
import CascadeSelect from "primevue/cascadeselect";
import Carousel from "primevue/carousel";
import Checkbox from "primevue/checkbox";
import Chip from "primevue/chip";
import InputTags from "primevue/inputtags";
import Column from "primevue/column";
import ColumnGroup from "primevue/columngroup";
import ConfirmDialog from "primevue/confirmdialog";
import ConfirmPopup from "primevue/confirmpopup";
import ConfirmationService from "primevue/confirmationservice";
import ContextMenu from "primevue/contextmenu";
import DataTable from "primevue/datatable";
import DataView from "primevue/dataview";
import DeferredContent from "primevue/deferredcontent";
import Dialog from "primevue/dialog";
import DialogService from "primevue/dialogservice";
import Divider from "primevue/divider";
import Dock from "primevue/dock";
import Select from "primevue/select";
import DynamicDialog from "primevue/dynamicdialog";
import Fieldset from "primevue/fieldset";
import FileUpload from "primevue/fileupload";
import FloatLabel from "primevue/floatlabel";
import Gallery from "primevue/gallery";
import GalleryBackdrop from "primevue/gallerybackdrop";
import GalleryContent from "primevue/gallerycontent";
import GalleryFooter from "primevue/galleryfooter";
import GalleryFullScreen from "primevue/galleryfullscreen";
import GalleryHeader from "primevue/galleryheader";
import GalleryItem from "primevue/galleryitem";
import GalleryNext from "primevue/gallerynext";
import GalleryPrev from "primevue/galleryprev";
import GalleryThumbnail from "primevue/gallerythumbnail";
import GalleryThumbnailContent from "primevue/gallerythumbnailcontent";
import GalleryThumbnailItem from "primevue/gallerythumbnailitem";
import IconField from "primevue/iconfield";
import Inplace from "primevue/inplace";
import InputGroup from "primevue/inputgroup";
import InputGroupAddon from "primevue/inputgroupaddon";
import InputIcon from "primevue/inputicon";
import ToggleSwitch from "primevue/toggleswitch";
import InputColor from "primevue/inputcolor";
import InputColorArea from "primevue/inputcolorarea";
import InputColorAreaBackground from "primevue/inputcolorareabackground";
import InputColorAreaHandle from "primevue/inputcolorareahandle";
import InputColorInput from "primevue/inputcolorinput";
import InputColorSlider from "primevue/inputcolorslider";
import InputColorSliderHandle from "primevue/inputcolorsliderhandle";
import InputColorSliderTrack from "primevue/inputcolorslidertrack";
import InputColorSwatch from "primevue/inputcolorswatch";
import InputColorSwatchBackground from "primevue/inputcolorswatchbackground";
import InputColorTransparencyGrid from "primevue/inputcolortransparencygrid";
import InputPassword from "primevue/inputpassword";
import InputText from "primevue/inputtext";
import InputNumber from "primevue/inputnumber";
import Knob from "primevue/knob";
import Listbox from "primevue/listbox";
import MegaMenu from "primevue/megamenu";
import Menu from "primevue/menu";
import Menubar from "primevue/menubar";
import Message from "primevue/message";
import Mask from "primevue/mask";
import OrderList from "primevue/orderlist";
import OrganizationChart from "primevue/organizationchart";
import OverlayBadge from "primevue/overlaybadge";
import Popover from "primevue/popover";
import Paginator from "primevue/paginator";
import Panel from "primevue/panel";
import PanelMenu from "primevue/panelmenu";
import PickList from "primevue/picklist";
import ProgressBar from "primevue/progressbar";
import ProgressSpinner from "primevue/progressspinner";
import Rating from "primevue/rating";
import RadioButton from "primevue/radiobutton";
import Ripple from "primevue/ripple";
import Row from "primevue/row";
import SelectButton from "primevue/selectbutton";
import ScrollArea from "primevue/scrollarea";
import ScrollAreaContent from "primevue/scrollareacontent";
import ScrollAreaHandle from "primevue/scrollareahandle";
import ScrollAreaScrollbar from "primevue/scrollareascrollbar";
import ScrollAreaViewport from "primevue/scrollareaviewport";
import ScrollTop from "primevue/scrolltop";
import Skeleton from "primevue/skeleton";
import Slider from "primevue/slider";
import Sidebar from "primevue/sidebar";
import SpeedDial from "primevue/speeddial";
import SplitButton from "primevue/splitbutton";
import Splitter from "primevue/splitter";
import SplitterPanel from "primevue/splitterpanel";
import Stepper from "primevue/stepper";
import StepList from "primevue/steplist";
import Step from "primevue/step";
import StyleClass from "primevue/styleclass";
import Tabs from "primevue/tabs";
import TabList from "primevue/tablist";
import Tab from "primevue/tab";
import TabPanels from "primevue/tabpanels";
import TieredMenu from "primevue/tieredmenu";
import Textarea from "primevue/textarea";
import Toast from "primevue/toast";
import ToastService from "primevue/toastservice";
import Toolbar from "primevue/toolbar";
import TabPanel from "primevue/tabpanel";
import Tag from "primevue/tag";
import Terminal from "primevue/terminal";
import Timeline from "primevue/timeline";
import ToggleButton from "primevue/togglebutton";
import Tooltip from "primevue/tooltip";
import Tree from "primevue/tree";
import TreeSelect from "primevue/treeselect";
import TreeTable from "primevue/treetable";
import VirtualScroller from "primevue/virtualscroller";

import BlockViewer from "./components/BlockViewer.vue";

import { createApp } from "vue";
import App from "./App.vue";
import { apiClient } from "./service/api";
import router from "./router";
import { useAuthStore } from "./store/auth";
import { pinia } from "./store/pinia";

const app = createApp(App);
app.use(PrimeVue, {
  ripple: true,
  license: window.PRIMEUI_LICENSE_KEY,
  theme: {
    preset: AuraCompat,
    options: {
      prefix: "p",
      darkModeSelector: ".app-dark",
      // Encore loads the app CSS via <link> while PrimeVue injects its
      // layer-order <style> at the very start of <head>. Give PrimeVue the
      // full order so component styles (primeui) beat Tailwind's base
      // reset but stay below utilities.
      cssLayer: {
        name: "primeui",
        order: "theme, base, primeui, components, utilities",
      },
      cssVariables: true,
    },
  },
});
app.use(pinia);

// Bind the auth store to the shared fetch client after Pinia is installed.
// The wrapper stays framework-agnostic; all storage/UI concerns live here.
const authStore = useAuthStore(pinia);
apiClient.setAuthHooks({
  getToken: () => authStore.token,
  setToken: (token) => authStore.saveToken(token),
  onRefreshed: (payload) => authStore.applyAuthResponse(payload),
  onUnauthorized: () => authStore.logout({ remote: false }),
});

app.use(router);
app.use(ToastService);
app.use(DialogService);
app.use(ConfirmationService);
app.directive("tooltip", Tooltip);
app.directive("ripple", Ripple);
app.directive("styleClass", StyleClass);
app.directive("mask", Mask);

app.component("BlockViewer", BlockViewer);

app.component("Accordion", Accordion);
app.component("AccordionPanel", AccordionPanel);
app.component("AccordionHeader", AccordionHeader);
app.component("AccordionContent", AccordionContent);
app.component("AutoComplete", AutoComplete);
app.component("Avatar", Avatar);
app.component("AvatarGroup", AvatarGroup);
app.component("Badge", Badge);
app.component("BlockUI", BlockUI);
app.component("Breadcrumb", Breadcrumb);
app.component("Button", Button);
app.component("ButtonGroup", ButtonGroup);
app.component("DatePicker", DatePicker);
app.component("Card", Card);
app.component("Carousel", Carousel);
app.component("CascadeSelect", CascadeSelect);
app.component("Checkbox", Checkbox);
app.component("Chip", Chip);
app.component("InputTags", InputTags);
app.component("Column", Column);
app.component("ColumnGroup", ColumnGroup);
app.component("ConfirmDialog", ConfirmDialog);
app.component("ConfirmPopup", ConfirmPopup);
app.component("ContextMenu", ContextMenu);
app.component("DataTable", DataTable);
app.component("DataView", DataView);
app.component("DeferredContent", DeferredContent);
app.component("Dialog", Dialog);
app.component("Divider", Divider);
app.component("Dock", Dock);
app.component("Select", Select);
app.component("DynamicDialog", DynamicDialog);
app.component("Fieldset", Fieldset);
app.component("FileUpload", FileUpload);
app.component("FloatLabel", FloatLabel);
app.component("Gallery", Gallery);
app.component("GalleryBackdrop", GalleryBackdrop);
app.component("GalleryContent", GalleryContent);
app.component("GalleryFooter", GalleryFooter);
app.component("GalleryFullScreen", GalleryFullScreen);
app.component("GalleryHeader", GalleryHeader);
app.component("GalleryItem", GalleryItem);
app.component("GalleryNext", GalleryNext);
app.component("GalleryPrev", GalleryPrev);
app.component("GalleryThumbnail", GalleryThumbnail);
app.component("GalleryThumbnailContent", GalleryThumbnailContent);
app.component("GalleryThumbnailItem", GalleryThumbnailItem);
app.component("IconField", IconField);
app.component("Inplace", Inplace);
app.component("InputGroup", InputGroup);
app.component("InputGroupAddon", InputGroupAddon);
app.component("InputIcon", InputIcon);
app.component("InputNumber", InputNumber);
app.component("ToggleSwitch", ToggleSwitch);
app.component("InputColor", InputColor);
app.component("InputColorArea", InputColorArea);
app.component("InputColorAreaBackground", InputColorAreaBackground);
app.component("InputColorAreaHandle", InputColorAreaHandle);
app.component("InputColorInput", InputColorInput);
app.component("InputColorSlider", InputColorSlider);
app.component("InputColorSliderHandle", InputColorSliderHandle);
app.component("InputColorSliderTrack", InputColorSliderTrack);
app.component("InputColorSwatch", InputColorSwatch);
app.component("InputColorSwatchBackground", InputColorSwatchBackground);
app.component("InputColorTransparencyGrid", InputColorTransparencyGrid);
app.component("InputPassword", InputPassword);
app.component("InputText", InputText);
app.component("Knob", Knob);
app.component("Listbox", Listbox);
app.component("MegaMenu", MegaMenu);
app.component("Menu", Menu);
app.component("Menubar", Menubar);
app.component("Message", Message);
app.component("OrderList", OrderList);
app.component("OrganizationChart", OrganizationChart);
app.component("OverlayBadge", OverlayBadge);
app.component("Popover", Popover);
app.component("Paginator", Paginator);
app.component("Panel", Panel);
app.component("PanelMenu", PanelMenu);
app.component("PickList", PickList);
app.component("ProgressBar", ProgressBar);
app.component("ProgressSpinner", ProgressSpinner);
app.component("RadioButton", RadioButton);
app.component("Rating", Rating);
app.component("Row", Row);
app.component("SelectButton", SelectButton);
app.component("ScrollArea", ScrollArea);
app.component("ScrollAreaContent", ScrollAreaContent);
app.component("ScrollAreaHandle", ScrollAreaHandle);
app.component("ScrollAreaScrollbar", ScrollAreaScrollbar);
app.component("ScrollAreaViewport", ScrollAreaViewport);
app.component("ScrollTop", ScrollTop);
app.component("Slider", Slider);
app.component("Sidebar", Sidebar);
app.component("Skeleton", Skeleton);
app.component("SpeedDial", SpeedDial);
app.component("SplitButton", SplitButton);
app.component("Splitter", Splitter);
app.component("SplitterPanel", SplitterPanel);
app.component("Stepper", Stepper);
app.component("StepList", StepList);
app.component("Step", Step);
app.component("Tabs", Tabs);
app.component("TabList", TabList);
app.component("Tab", Tab);
app.component("TabPanels", TabPanels);
app.component("TabPanel", TabPanel);
app.component("Tag", Tag);
app.component("Textarea", Textarea);
app.component("Terminal", Terminal);
app.component("TieredMenu", TieredMenu);
app.component("Timeline", Timeline);
app.component("Toast", Toast);
app.component("Toolbar", Toolbar);
app.component("ToggleButton", ToggleButton);
app.component("Tree", Tree);
app.component("TreeSelect", TreeSelect);
app.component("TreeTable", TreeTable);
app.component("VirtualScroller", VirtualScroller);

app.mount("#vue-app");
