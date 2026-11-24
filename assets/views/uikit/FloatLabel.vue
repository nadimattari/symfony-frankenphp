<script setup lang="ts">
import { onMounted, ref } from 'vue';
import AutoComplete from 'primevue/autocomplete';
import FloatLabel from 'primevue/floatlabel';
import InputGroup from 'primevue/inputgroup';
import InputGroupAddon from 'primevue/inputgroupaddon';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import CountryService, { type Country } from '../../service/CountryService';

interface City {
    name: string;
    code: string;
}

const countries = ref<Country[]>([]);
const cities = ref<City[]>([
    { name: 'New York', code: 'NY' },
    { name: 'Rome', code: 'RM' },
    { name: 'London', code: 'LDN' },
    { name: 'Istanbul', code: 'IST' },
    { name: 'Paris', code: 'PRS' }
]);
const filteredCountries = ref<Country[]>([]);
const value1 = ref<string | null>(null);
const value2 = ref<Country | null>(null);
const value3 = ref<Date | null>(null);
const value4 = ref<string[]>([]);
const value5 = ref<string | null>(null);
const value6 = ref<number | null>(null);
const value7 = ref<string | null>(null);
const value8 = ref<City | null>(null);
const value9 = ref<City[]>([]);
const value10 = ref<string | null>(null);
const countryService = new CountryService();

onMounted(() => {
    countryService.getCountries().then((data) => (countries.value = data));
});

const searchCountry = (event: { query: string }): void => {
    const filtered: Country[] = [];
    const query = event.query;
    for (let i = 0; i < countries.value.length; i++) {
        const country = countries.value[i];
        if (country.name.toLowerCase().indexOf(query.toLowerCase()) == 0) {
            filtered.push(country);
        }
    }
    filteredCountries.value = filtered;
};
</script>

<template>
    <div class="card">
        <h5>Float Label</h5>
        <div class="grid grid-cols-12 gap-4 w-full [&_.p-component]:w-full mt-3">
            <div class="mb-4 [&>label]:mb-2 [&>small]:mt-1 col-span-12 md:col-span-4">
                <FloatLabel>
                    <InputText type="text" id="inputtext" v-model="value1" />
                    <label for="inputtext">InputText</label>
                </FloatLabel>
            </div>
            <div class="mb-4 [&>label]:mb-2 [&>small]:mt-1 col-span-12 md:col-span-4">
                <FloatLabel>
                    <AutoComplete id="autocomplete" v-model="value2" :suggestions="filteredCountries" @complete="searchCountry($event)" field="name"></AutoComplete>
                    <label for="autocomplete">AutoComplete</label>
                </FloatLabel>
            </div>
            <div class="mb-4 [&>label]:mb-2 [&>small]:mt-1 col-span-12 md:col-span-4">
                <FloatLabel>
                    <DatePicker inputId="calendar" v-model="value3"></DatePicker>
                    <label for="calendar">Calendar</label>
                </FloatLabel>
            </div>
            <div class="mb-4 [&>label]:mb-2 [&>small]:mt-1 col-span-12 md:col-span-4">
                <FloatLabel>
                    <InputTags inputId="chips" v-model="value4"></InputTags>
                    <label for="chips">Chips</label>
                </FloatLabel>
            </div>
            <div class="mb-4 [&>label]:mb-2 [&>small]:mt-1 col-span-12 md:col-span-4">
                <FloatLabel>
                    <InputText id="mask" v-model="value5" v-mask="{ mask: '99/99/9999', slotChar: 'mm/dd/yyyy' }" />
                    <label for="mask">Mask</label>
                </FloatLabel>
            </div>
            <div class="mb-4 [&>label]:mb-2 [&>small]:mt-1 col-span-12 md:col-span-4">
                <FloatLabel>
                    <InputNumber id="inputnumber" v-model="value6"></InputNumber>
                    <label for="inputnumber">InputNumber</label>
                </FloatLabel>
            </div>
            <div class="mb-4 [&>label]:mb-2 [&>small]:mt-1 col-span-12 md:col-span-4">
                <InputGroup>
                    <InputGroupAddon>
                        <i class="pi pi-user"></i>
                    </InputGroupAddon>
                    <FloatLabel>
                        <InputText type="text" id="inputgroup" v-model="value7" />
                        <label for="inputgroup">InputGroup</label>
                    </FloatLabel>
                </InputGroup>
            </div>
            <div class="mb-4 [&>label]:mb-2 [&>small]:mt-1 col-span-12 md:col-span-4">
                <FloatLabel>
                    <Select id="dropdown" :options="cities" v-model="value8" optionLabel="name"></Select>
                    <label for="dropdown">Dropdown</label>
                </FloatLabel>
            </div>
            <div class="mb-4 [&>label]:mb-2 [&>small]:mt-1 col-span-12 md:col-span-4">
                <FloatLabel>
                    <Select id="multiselect" multiple :options="cities" v-model="value9" optionLabel="name" :filter="false"></Select>
                    <label for="multiselect">Select (multiple)</label>
                </FloatLabel>
            </div>
            <div class="mb-4 [&>label]:mb-2 [&>small]:mt-1 col-span-12 md:col-span-4">
                <FloatLabel>
                    <Textarea inputId="textarea" rows="3" cols="30" v-model="value10"></Textarea>
                    <label for="textarea">Textarea</label>
                </FloatLabel>
            </div>
        </div>
    </div>
</template>
