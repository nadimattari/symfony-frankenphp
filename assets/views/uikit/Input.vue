<script setup lang="ts">
import { onMounted, ref } from 'vue';
import CountryService, { type Country } from '../../service/CountryService';
import NodeService, { type TreeNodeData } from '../../service/NodeService';

interface City {
    name: string;
    code: string;
}

interface OptionItem {
    name: string;
}

const floatValue = ref<string | null>(null);
const autoValue = ref<Country[]>([]);
const selectedAutoValue = ref<Country[] | null>(null);
const autoFilteredValue = ref<Country[]>([]);
const calendarValue = ref<Date | null>(null);
const inputNumberValue = ref<number | null>(null);
const chipsValue = ref<string[]>([]);
const sliderValue = ref(50);
const ratingValue = ref<number | null>(null);
const colorValue = ref('#1976D2');
const radioValue = ref<string | null>(null);
const checkboxValue = ref<string[]>([]);
const switchValue = ref(false);
const listboxValues = ref<City[]>([
    { name: 'New York', code: 'NY' },
    { name: 'Rome', code: 'RM' },
    { name: 'London', code: 'LDN' },
    { name: 'Istanbul', code: 'IST' },
    { name: 'Paris', code: 'PRS' }
]);
const listboxValue = ref<City | null>(null);
const dropdownValues = ref<City[]>([
    { name: 'New York', code: 'NY' },
    { name: 'Rome', code: 'RM' },
    { name: 'London', code: 'LDN' },
    { name: 'Istanbul', code: 'IST' },
    { name: 'Paris', code: 'PRS' }
]);
const dropdownValue = ref<City | null>(null);
const multiselectValues = ref<Country[]>([
    { name: 'Australia', code: 'AU' },
    { name: 'Brazil', code: 'BR' },
    { name: 'China', code: 'CN' },
    { name: 'Egypt', code: 'EG' },
    { name: 'France', code: 'FR' },
    { name: 'Germany', code: 'DE' },
    { name: 'India', code: 'IN' },
    { name: 'Japan', code: 'JP' },
    { name: 'Spain', code: 'ES' },
    { name: 'United States', code: 'US' }
]);

const multiselectValue = ref<Country[]>([]);
const toggleValue = ref(false);
const selectButtonValue1 = ref<OptionItem | null>(null);
const selectButtonValues1 = ref<OptionItem[]>([{ name: 'Option 1' }, { name: 'Option 2' }, { name: 'Option 3' }]);
const selectButtonValue2 = ref<OptionItem[] | null>(null);
const selectButtonValues2 = ref<OptionItem[]>([{ name: 'Option 1' }, { name: 'Option 2' }, { name: 'Option 3' }]);
const knobValue = ref(50);
const inputGroupValue = ref(false);
const treeSelectNodes = ref<any[] | undefined>(undefined);
const selectedNode = ref<any>(null);
const countryService = new CountryService();
const nodeService = new NodeService();

onMounted(() => {
    countryService.getCountries().then((data) => (autoValue.value = data));
    nodeService.getTreeNodes().then((data) => (treeSelectNodes.value = data));
});

const searchCountry = (event: { query: string }): void => {
    setTimeout(() => {
        if (!event.query.trim().length) {
            autoFilteredValue.value = [...autoValue.value];
        } else {
            autoFilteredValue.value = autoValue.value.filter((country) => {
                return country.name.toLowerCase().startsWith(event.query.toLowerCase());
            });
        }
    }, 250);
};
</script>
<template>
    <div class="grid grid-cols-12 gap-4 w-full [&_.p-component]:w-full">
        <div class="col-span-12 md:col-span-6">
            <div class="card">
                <h5>InputText</h5>
                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-12 mb-2 lg:col-span-4 lg:mb-0">
                        <InputText type="text" placeholder="Default"></InputText>
                    </div>
                    <div class="col-span-12 mb-2 lg:col-span-4 lg:mb-0">
                        <InputText type="text" placeholder="Disabled" :disabled="true"></InputText>
                    </div>
                    <div class="col-span-12 mb-2 lg:col-span-4 lg:mb-0">
                        <InputText type="text" placeholder="Invalid" invalid />
                    </div>
                </div>

                <h5>Icons</h5>
                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-12 mb-2 lg:col-span-6 lg:mb-0">
                        <IconField>
                            <InputIcon class="pi pi-user" />
                            <InputText type="text" placeholder="Username" />
                        </IconField>
                    </div>
                    <div class="col-span-12 mb-2 lg:col-span-6 lg:mb-0">
                        <IconField iconPosition="left">
                            <InputText type="text" placeholder="Search" />
                            <InputIcon class="pi pi-search" />
                        </IconField>
                    </div>
                </div>

                <h5>Float Label</h5>
                <FloatLabel>
                    <InputText id="username" type="text" v-model="floatValue" />
                    <label for="username">Username</label>
                </FloatLabel>

                <h5>Textarea</h5>
                <Textarea placeholder="Your Message" :autoResize="true" rows="3" cols="30" />

                <h5>AutoComplete</h5>
                <AutoComplete placeholder="Search" id="dd" :dropdown="true" :multiple="true" v-model="selectedAutoValue" :suggestions="autoFilteredValue" @complete="searchCountry($event)" field="name" />

                <h5>Calendar</h5>
                <DatePicker :showIcon="true" :showButtonBar="true" v-model="calendarValue"></DatePicker>

                <h5>Spinner</h5>
                <InputNumber v-model="inputNumberValue" showButtons mode="decimal"></InputNumber>

                <h5>Chips</h5>
                <InputTags v-model="chipsValue" />
            </div>

            <div class="card">
                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-12">
                        <h5>Slider</h5>
                        <InputText :modelValue="String(sliderValue)" @update:modelValue="(v: string | undefined) => (sliderValue = Number(v ?? 0))" />
                        <Slider v-model="sliderValue" />
                    </div>
                    <div class="col-span-12 md:col-span-6">
                        <h5>Rating</h5>
                        <Rating v-model="ratingValue" />
                    </div>
                    <div class="col-span-12 md:col-span-6">
                        <h5>InputColor</h5>
                        <InputColor v-model="colorValue" format="hex">
                            <InputColorArea>
                                <InputColorAreaBackground />
                                <InputColorAreaHandle />
                            </InputColorArea>
                            <div class="flex items-center gap-2 mt-2">
                                <div class="flex-1">
                                    <InputColorSlider>
                                        <InputColorTransparencyGrid />
                                        <InputColorSliderTrack />
                                        <InputColorSliderHandle />
                                    </InputColorSlider>
                                    <InputColorSlider channel="alpha">
                                        <InputColorTransparencyGrid />
                                        <InputColorSliderTrack />
                                        <InputColorSliderHandle />
                                    </InputColorSlider>
                                </div>
                                <InputColorSwatch>
                                    <InputColorTransparencyGrid />
                                    <InputColorSwatchBackground />
                                </InputColorSwatch>
                            </div>
                            <InputColorInput channel="hex" class="mt-2" />
                        </InputColor>
                    </div>
                    <div class="col-span-12">
                        <h5>Knob</h5>
                        <Knob v-model="knobValue" :step="10" :min="-50" :max="50" valueTemplate="{value}%" />
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 md:col-span-6">
            <div class="card">
                <h5>RadioButton</h5>
                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-12 md:col-span-4">
                        <div class="flex items-center gap-2 mb-0">
                            <RadioButton id="option1" name="option" value="Chicago" v-model="radioValue" />
                            <label for="option1">Chicago</label>
                        </div>
                    </div>
                    <div class="col-span-12 md:col-span-4">
                        <div class="flex items-center gap-2 mb-0">
                            <RadioButton id="option2" name="option" value="Los Angeles" v-model="radioValue" />
                            <label for="option2">Los Angeles</label>
                        </div>
                    </div>
                    <div class="col-span-12 md:col-span-4">
                        <div class="flex items-center gap-2 mb-0">
                            <RadioButton id="option3" name="option" value="New York" v-model="radioValue" />
                            <label for="option3">New York</label>
                        </div>
                    </div>
                </div>

                <h5>Checkbox</h5>
                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-12 md:col-span-4">
                        <div class="field-checkbox mb-0">
                            <Checkbox id="checkOption1" name="option" value="Chicago" v-model="checkboxValue" />
                            <label for="checkOption1">Chicago</label>
                        </div>
                    </div>
                    <div class="col-span-12 md:col-span-4">
                        <div class="field-checkbox mb-0">
                            <Checkbox id="checkOption2" name="option" value="Los Angeles" v-model="checkboxValue" />
                            <label for="checkOption2">Los Angeles</label>
                        </div>
                    </div>
                    <div class="col-span-12 md:col-span-4">
                        <div class="field-checkbox mb-0">
                            <Checkbox id="checkOption3" name="option" value="New York" v-model="checkboxValue" />
                            <label for="checkOption3">New York</label>
                        </div>
                    </div>
                </div>

                <h5>Input Switch</h5>
                <ToggleSwitch v-model="switchValue" />
            </div>

            <div class="card">
                <h5>Listbox</h5>
                <Listbox v-model="listboxValue" :options="listboxValues" optionLabel="name" :filter="true" />

                <h5>Dropdown</h5>
                <Select v-model="dropdownValue" :options="dropdownValues" optionLabel="name" placeholder="Select" />

                <h5>Select (multiple)</h5>
                <Select multiple v-model="multiselectValue" :options="multiselectValues" optionLabel="name" placeholder="Select Countries" :filter="true">
                    <template #value="slotProps">
                        <div class="inline-flex items-center py-1 px-2 bg-primary text-primary rounded mr-2" v-for="option of (slotProps as any).value" :key="option.code">
                            <span :class="'mr-2 flag flag-' + option.code.toLowerCase()" style="width: 18px; height: 12px" />
                            <div>{{ option.name }}</div>
                        </div>
                        <template v-if="!(slotProps as any).value || (slotProps as any).value.length === 0">
                            <div class="p-1">Select Countries</div>
                        </template>
                    </template>
                    <template #option="slotProps">
                        <div class="flex items-center">
                            <span :class="'mr-2 flag flag-' + (slotProps as any).option.code.toLowerCase()" style="width: 18px; height: 12px" />
                            <div>{{ (slotProps as any).option.name }}</div>
                        </div>
                    </template>
                </Select>

                <h5>TreeSelect</h5>
                <TreeSelect v-model="selectedNode" :options="treeSelectNodes" placeholder="Select Item"></TreeSelect>
            </div>

            <div class="card">
                <h5>ToggleButton</h5>
                <ToggleButton v-model="toggleValue" onLabel="Yes" offLabel="No" :style="{ width: '10em' }" />

                <h5>SelectButton</h5>
                <SelectButton v-model="selectButtonValue1" :options="selectButtonValues1" optionLabel="name" />

                <h5>SelectButton - Multiple</h5>
                <SelectButton v-model="selectButtonValue2" :options="selectButtonValues2" optionLabel="name" :multiple="true" />
            </div>
        </div>

        <div class="col-span-12">
            <div class="card">
                <h5>Input Groups</h5>
                <div class="grid grid-cols-12 gap-4 w-full [&_.p-component]:w-full">
                    <div class="col-span-12 md:col-span-6">
                        <InputGroup>
                            <InputGroupAddon>
                                <i class="pi pi-user"></i>
                            </InputGroupAddon>
                            <InputText placeholder="Username" />
                        </InputGroup>
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <InputGroup>
                            <InputGroupAddon>
                                <i class="pi pi-clock"></i>
                            </InputGroupAddon>
                            <InputGroupAddon>
                                <i class="pi pi-star-fill"></i>
                            </InputGroupAddon>
                            <InputNumber placeholder="Price" />
                            <InputGroupAddon>$</InputGroupAddon>
                            <InputGroupAddon>.00</InputGroupAddon>
                        </InputGroup>
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <InputGroup>
                            <Button label="Search" />
                            <InputText placeholder="Keyword" />
                        </InputGroup>
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <InputGroup>
                            <InputGroupAddon>
                                <Checkbox v-model="inputGroupValue" :binary="true" />
                            </InputGroupAddon>
                            <InputText placeholder="Confirm" />
                        </InputGroup>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
