<script setup lang="ts">
import { onMounted, ref } from 'vue';
import AutoComplete from 'primevue/autocomplete';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Textarea from 'primevue/textarea';
import CountryService, { type Country } from '../../service/CountryService';

interface City {
    name: string;
    code: string;
}

const countries = ref<Country[]>([]);
const filteredCountries = ref<Country[]>([]);
const value1 = ref<string | null>(null);
const value2 = ref<Country | null>(null);
const value3 = ref<Date | null>(null);
const value4 = ref<string[]>([]);
const value5 = ref<string | null>(null);
const value6 = ref<number | null>(null);
const value7 = ref<City | null>(null);
const value8 = ref<City[]>([]);
const value9 = ref<string | null>(null);
const value10 = ref<string | null>(null);
const cities = ref<City[]>([
    { name: 'New York', code: 'NY' },
    { name: 'Rome', code: 'RM' },
    { name: 'London', code: 'LDN' },
    { name: 'Istanbul', code: 'IST' },
    { name: 'Paris', code: 'PRS' }
]);
const countryService = new CountryService();

onMounted(() => {
    countryService.getCountries().then((data) => {
        countries.value = data;
    });
});

const searchCountry = (event: { query: string }): void => {
    setTimeout(() => {
        if (!event.query.trim().length) {
            filteredCountries.value = [...countries.value];
        } else {
            filteredCountries.value = countries.value.filter((country) => {
                return country.name.toLowerCase().startsWith(event.query.toLowerCase());
            });
        }
    }, 250);
};
</script>

<template>
    <div class="grid p-fluid">
        <div class="col">
            <div class="card">
                <h5>Invalid State</h5>
                <div class="p-fluid grid">
                    <div class="col-12 md:col-6">
                        <div class="field">
                            <label for="inputtext">InputText</label>
                            <InputText id="inputtext" type="text" v-model="value1" invalid />
                        </div>
                        <div class="field">
                            <label for="autocomplete">AutoComplete</label>
                            <AutoComplete id="autocomplete" v-model="value2" :suggestions="filteredCountries" @complete="searchCountry($event)" field="name" invalid />
                        </div>
                        <div class="field">
                            <label for="calendar">Calendar</label>
                            <DatePicker id="calendar" v-model="value3" invalid :showIcon="true" />
                        </div>
                        <div class="field">
                            <label for="chips">Chips</label>
                            <InputTags id="chips" v-model="value4" invalid />
                        </div>
                        <div class="field">
                            <label for="password">Password</label>
                            <Password id="password" v-model="value10" invalid />
                        </div>
                    </div>

                    <div class="col-12 md:col-6">
                        <div class="field">
                            <label for="mask">Mask</label>
                            <InputText id="mask" v-model="value5" v-mask="{ mask: '99/99/9999', slotChar: 'mm/dd/yyyy' }" invalid />
                        </div>
                        <div class="field">
                            <label for="inputnumber">InputNumber</label>
                            <InputNumber id="inputnumber" v-model="value6" invalid />
                        </div>
                        <div class="field">
                            <label for="dropdown">Dropdown</label>
                            <Select id="dropdown" v-model="value7" :options="cities" optionLabel="name" invalid />
                        </div>
                        <div class="field">
                            <label for="multiselect">Select (multiple)</label>
                            <Select id="multiselect" multiple v-model="value8" :options="cities" optionLabel="name" invalid />
                        </div>
                        <div class="field">
                            <label for="textarea">Textarea</label>
                            <Textarea id="textarea" v-model="value9" rows="3" invalid />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
