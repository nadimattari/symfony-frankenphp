<script setup lang="ts">
import { FilterMatchMode, FilterOperator } from '@primevue/core/api';
import { onBeforeMount, ref } from 'vue';
import CustomerService, { type Customer } from '../../service/CustomerService';
import ProductService, { type Product } from '../../service/ProductService';

interface Representative {
    name: string;
    image: string;
}

const customer1 = ref<Customer[] | null>(null);
const customer2 = ref<Customer[] | null>(null);
const customer3 = ref<Customer[] | null>(null);
const filters1 = ref<any>(null);
const loading1 = ref<boolean>(false);
const loading2 = ref<boolean>(false);
const idFrozen = ref(false);
const products = ref<any[] | null>(null);
const expandedRows = ref<any>([]);
const statuses = ref(['unqualified', 'qualified', 'new', 'negotiation', 'renewal', 'proposal']);
const representatives = ref<Representative[]>([
    { name: 'Amy Elsner', image: 'amyelsner.png' },
    { name: 'Anna Fali', image: 'annafali.png' },
    { name: 'Asiya Javayant', image: 'asiyajavayant.png' },
    { name: 'Bernardo Dominic', image: 'bernardodominic.png' },
    { name: 'Elwin Sharvill', image: 'elwinsharvill.png' },
    { name: 'Ioni Bowcher', image: 'ionibowcher.png' },
    { name: 'Ivan Magalhaes', image: 'ivanmagalhaes.png' },
    { name: 'Onyama Limba', image: 'onyamalimba.png' },
    { name: 'Stephen Shaw', image: 'stephenshaw.png' },
    { name: 'XuXue Feng', image: 'xuxuefeng.png' }
]);

const customerService = new CustomerService();
const productService = new ProductService();

const getBadgeSeverity = (inventoryStatus: string): string => {
    switch (inventoryStatus.toLowerCase()) {
        case 'instock':
            return 'success';
        case 'lowstock':
            return 'warning';
        case 'outofstock':
            return 'danger';
        default:
            return 'info';
    }
};
const getSeverity = (status: string): string | undefined => {
    switch (status) {
        case 'unqualified':
            return 'danger';
        case 'qualified':
            return 'success';
        case 'new':
            return 'info';
        case 'negotiation':
            return 'warning';
        case 'renewal':
            return undefined;
        default:
            return undefined;
    }
};

onBeforeMount(() => {
    productService.getProductsWithOrdersSmall().then((data) => (products.value = data));
    customerService.getCustomersLarge().then((data) => {
        customer1.value = data;
        loading1.value = false;
        customer1.value.forEach((customer: any) => (customer.date = new Date(customer.date)));
    });
    customerService.getCustomersLarge().then((data) => (customer2.value = data));
    customerService.getCustomersMedium().then((data) => (customer3.value = data));
    loading2.value = false;

    initFilters1();
});

const initFilters1 = (): void => {
    filters1.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        name: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
        'country.name': { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
        representative: { value: null, matchMode: FilterMatchMode.IN },
        date: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.DATE_IS }] },
        balance: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.EQUALS }] },
        status: { operator: FilterOperator.OR, constraints: [{ value: null, matchMode: FilterMatchMode.EQUALS }] },
        activity: { value: [0, 100], matchMode: FilterMatchMode.BETWEEN },
        verified: { value: null, matchMode: FilterMatchMode.EQUALS }
    };
};

const clearFilter1 = (): void => {
    initFilters1();
};
const expandAll = (): void => {
    expandedRows.value = (products.value ?? []).reduce((acc: any, p: any) => (acc[p.id] = true) && acc, {});
};
const collapseAll = (): void => {
    expandedRows.value = null;
};
const formatCurrency = (value: number | undefined): string => {
    return (value ?? 0).toLocaleString('en-US', { style: 'currency', currency: 'USD' });
};

const formatDate = (value: Date | string): string => {
    const date = value instanceof Date ? value : new Date(value);
    return date.toLocaleDateString('en-US', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
};
const calculateCustomerTotal = (name: string): number => {
    let total = 0;
    if (customer3.value) {
        for (let customer of customer3.value as any[]) {
            if (customer.representative.name === name) {
                total++;
            }
        }
    }

    return total;
};
</script>

<template>
    <div class="grid">
        <div class="col-12">
            <div class="card">
                <h5>Filter Menu</h5>
                <DataTable
                    :value="customer1"
                    :paginator="true"
                    :rows="10"
                    dataKey="id"
                    :rowHover="true"
                    v-model:filters="filters1"
                    filterDisplay="menu"
                    :loading="loading1"
                    :globalFilterFields="['name', 'country.name', 'representative.name', 'balance', 'status']"
                    showGridlines
                >
                    <template #header>
                        <div class="flex justify-content-between flex-column sm:flex-row">
                            <Button type="button" icon="pi pi-filter-slash" label="Clear" outlined @click="clearFilter1()" />
                            <IconField iconPosition="left">
                                <InputIcon class="pi pi-search" />
                                <InputText v-model="filters1['global'].value" placeholder="Keyword Search" style="width: 100%" />
                            </IconField>
                        </div>
                    </template>
                    <template #empty> No customers found. </template>
                    <template #loading> Loading customers data. Please wait. </template>
                    <Column field="name" header="Name" style="min-width: 12rem">
                        <template #body="slotProps">
                            {{ (slotProps as any).data.name }}
                        </template>
                        <template #filter="slotProps">
                            <InputText type="text" v-model="(slotProps as any).filterModel.value" class="p-column-filter" placeholder="Search by name" />
                        </template>
                    </Column>
                    <Column header="Country" filterField="country.name" style="min-width: 12rem">
                        <template #body="slotProps">
                            <div class="flex align-items-center gap-2">
                                <img alt="flag" src="/demo/images/flag/flag_placeholder.png" :class="`flag flag-${(slotProps as any).data.country.code}`" style="width: 24px" />
                                <span>{{ (slotProps as any).data.country.name }}</span>
                            </div>
                        </template>
                        <template #filter="slotProps">
                            <InputText type="text" v-model="(slotProps as any).filterModel.value" class="p-column-filter" placeholder="Search by country" />
                        </template>
                        <template #filterclear="slotProps">
                            <Button type="button" icon="pi pi-times" @click="(slotProps as any).filterCallback()" severity="secondary"></Button>
                        </template>
                        <template #filterapply="slotProps">
                            <Button type="button" icon="pi pi-check" @click="(slotProps as any).filterCallback()" severity="success"></Button>
                        </template>
                    </Column>
                    <Column header="Agent" filterField="representative" :showFilterMatchModes="false" :filterMenuStyle="{ width: '14rem' }" style="min-width: 14rem">
                        <template #body="slotProps">
                            <div class="flex align-items-center gap-2">
                                <img :alt="(slotProps as any).data.representative.name" :src="`https://primefaces.org/cdn/primevue/images/avatar/${(slotProps as any).data.representative.image}`" style="width: 32px" />
                                <span>{{ (slotProps as any).data.representative.name }}</span>
                            </div>
                        </template>
                        <template #filter="slotProps">
                            <div class="mb-3 text-bold">Agent Picker</div>
                            <Select multiple v-model="(slotProps as any).filterModel.value" :options="representatives" optionLabel="name" placeholder="Any" class="p-column-filter">
                                <template #option="slotProps">
                                    <div class="p-multiselect-representative-option">
                                        <img :alt="(slotProps as any).option.name" :src="'/demo/images/avatar/' + (slotProps as any).option.image" width="32" style="vertical-align: middle" />
                                        <span style="margin-left: 0.5em; vertical-align: middle" class="image-text">{{ (slotProps as any).option.name }}</span>
                                    </div>
                                </template>
                            </Select>
                        </template>
                    </Column>
                    <Column header="Date" filterField="date" dataType="date" style="min-width: 10rem">
                        <template #body="slotProps">
                            {{ formatDate((slotProps as any).data.date) }}
                        </template>
                        <template #filter="slotProps">
                            <DatePicker v-model="(slotProps as any).filterModel.value" dateFormat="mm/dd/yy" placeholder="mm/dd/yyyy" />
                        </template>
                    </Column>
                    <Column header="Balance" filterField="balance" dataType="numeric" style="min-width: 10rem">
                        <template #body="slotProps">
                            {{ formatCurrency((slotProps as any).data.balance) }}
                        </template>
                        <template #filter="slotProps">
                            <InputNumber v-model="(slotProps as any).filterModel.value" mode="currency" currency="USD" locale="en-US" />
                        </template>
                    </Column>
                    <Column field="status" header="Status" :filterMenuStyle="{ width: '14rem' }" style="min-width: 12rem">
                        <template #body="slotProps">
                            <Tag :severity="getSeverity((slotProps as any).data.status)">{{ (slotProps as any).data.status.toUpperCase() }}</Tag>
                        </template>
                        <template #filter="slotProps">
                            <Select v-model="(slotProps as any).filterModel.value" :options="statuses" placeholder="Any" class="p-column-filter" :showClear="true">
                                <template #value="slotProps">
                                    <Tag :severity="getSeverity((slotProps as any).value)" v-if="(slotProps as any).value">{{ (slotProps as any).value }} </Tag>
                                    <span v-else>{{ (slotProps as any).placeholder }}</span>
                                </template>
                                <template #option="slotProps">
                                    <Tag :severity="getSeverity((slotProps as any).option)">{{ (slotProps as any).option.toUpperCase() }}</Tag>
                                </template>
                            </Select>
                        </template>
                    </Column>
                    <Column field="activity" header="Activity" :showFilterMatchModes="false" style="min-width: 12rem">
                        <template #body="slotProps">
                            <ProgressBar :value="(slotProps as any).data.activity" :showValue="false" style="height: 0.5rem"></ProgressBar>
                        </template>
                        <template #filter="slotProps">
                            <Slider v-model="(slotProps as any).filterModel.value" :range="true" class="m-3"></Slider>
                            <div class="flex align-items-center justify-content-between px-2">
                                <span>{{ (slotProps as any).filterModel.value ? (slotProps as any).filterModel.value[0] : 0 }}</span>
                                <span>{{ (slotProps as any).filterModel.value ? (slotProps as any).filterModel.value[1] : 100 }}</span>
                            </div>
                        </template>
                    </Column>
                    <Column field="verified" header="Verified" dataType="boolean" bodyClass="text-center" style="min-width: 8rem">
                        <template #body="slotProps">
                            <i class="pi" :class="{ 'text-green-500 pi-check-circle': (slotProps as any).data.verified, 'text-pink-500 pi-times-circle': !(slotProps as any).data.verified }"></i>
                        </template>
                        <template #filter="slotProps">
                            <div class="flex align-items-center gap-2">
                                <Checkbox
                                    v-model="(slotProps as any).filterModel.value"
                                    :indeterminate="(slotProps as any).filterModel.value === null"
                                    binary
                                    inputId="verified-filter"
                                />
                                <label for="verified-filter" class="font-bold">Verified</label>
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <h5>Frozen Columns</h5>
                <ToggleButton v-model="idFrozen" onIcon="pi pi-lock" offIcon="pi pi-lock-open" onLabel="Unfreeze Id" offLabel="Freeze Id" style="width: 10rem" />

                <DataTable :value="customer2" :scrollable="true" scrollHeight="400px" :loading="loading2" scrollDirection="both" class="mt-3">
                    <Column field="name" header="Name" style="min-width: 200px" frozen></Column>
                    <Column field="id" header="Id" style="min-width: 100px" :frozen="idFrozen"></Column>
                    <Column field="country.name" header="Country" :style="{ width: '200px' }">
                        <template #body="slotProps">
                            <div class="flex align-items-center gap-2">
                                <img alt="flag" src="/demo/images/flag/flag_placeholder.png" :class="`flag flag-${(slotProps as any).data.country.code}`" style="width: 24px" />
                                <span>{{ (slotProps as any).data.country.name }}</span>
                            </div>
                        </template>
                    </Column>
                    <Column field="date" header="Date" style="min-width: 200px"></Column>
                    <Column field="company" header="Company" style="min-width: 200px"></Column>
                    <Column field="status" header="Status" style="min-width: 200px">
                        <template #body="slotProps">
                            <Tag :severity="getSeverity((slotProps as any).data.status)">{{ (slotProps as any).data.status.toUpperCase() }}</Tag>
                        </template>
                    </Column>
                    <Column field="activity" header="Activity" style="min-width: 200px"></Column>
                    <Column field="representative.name" header="Representative" style="min-width: 200px">
                        <template #body="slotProps">
                            <div class="flex align-items-center gap-2">
                                <img :alt="(slotProps as any).data.representative.name" :src="`https://primefaces.org/cdn/primevue/images/avatar/${(slotProps as any).data.representative.image}`" style="width: 32px" />
                                <span>{{ (slotProps as any).data.representative.name }}</span>
                            </div>
                        </template>
                    </Column>
                    <Column field="balance" header="Balance" style="min-width: 200px" frozen alignFrozen="right">
                        <template #body="slotProps">
                            <span class="text-bold">{{ formatCurrency((slotProps as any).data.balance) }}</span>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <h5>Row Expand</h5>
                <DataTable :value="products" v-model:expandedRows="expandedRows" dataKey="id" tableStyle="min-width: 60rem">
                    <template #header>
                        <div>
                            <Button icon="pi pi-plus" label="Expand All" @click="expandAll" class="mr-2 mb-2" />
                            <Button icon="pi pi-minus" label="Collapse All" @click="collapseAll" class="mb-2" />
                        </div>
                    </template>
                    <Column :expander="true" headerStyle="width: 3rem" />
                    <Column field="name" header="Name" :sortable="true">
                        <template #body="slotProps">
                            {{ (slotProps as any).data.name }}
                        </template>
                    </Column>
                    <Column header="Image">
                        <template #body="slotProps">
                            <img :src="'/demo/images/product/' + (slotProps as any).data.image" :alt="(slotProps as any).data.image" class="shadow-2" width="100" />
                        </template>
                    </Column>
                    <Column field="price" header="Price" :sortable="true">
                        <template #body="slotProps">
                            {{ formatCurrency((slotProps as any).data.price) }}
                        </template>
                    </Column>
                    <Column field="category" header="Category" :sortable="true">
                        <template #body="slotProps">
                            {{ formatCurrency((slotProps as any).data.category) }}
                        </template></Column
                    >
                    <Column field="rating" header="Reviews" :sortable="true">
                        <template #body="slotProps">
                            <Rating :modelValue="(slotProps as any).data.rating" :readonly="true" :cancel="false" />
                        </template>
                    </Column>
                    <Column field="inventoryStatus" header="Status" :sortable="true">
                        <template #body="slotProps">
                            <Tag :severity="getBadgeSeverity((slotProps as any).data.inventoryStatus)">{{ (slotProps as any).data.inventoryStatus }}</Tag>
                        </template>
                    </Column>
                    <template #expansion="slotProps">
                        <div class="p-3">
                            <h5>Orders for {{ (slotProps as any).data.name }}</h5>
                            <DataTable :value="(slotProps as any).data.orders">
                                <Column field="id" header="Id" :sortable="true">
                                    <template #body="slotProps">
                                        {{ (slotProps as any).data.id }}
                                    </template>
                                </Column>
                                <Column field="customer" header="Customer" :sortable="true">
                                    <template #body="slotProps">
                                        {{ (slotProps as any).data.customer }}
                                    </template>
                                </Column>
                                <Column field="date" header="Date" :sortable="true">
                                    <template #body="slotProps">
                                        {{ (slotProps as any).data.date }}
                                    </template>
                                </Column>
                                <Column field="amount" header="Amount" :sortable="true">
                                    <template #body="slotProps">
                                        {{ formatCurrency((slotProps as any).data.amount) }}
                                    </template>
                                </Column>
                                <Column field="status" header="Status" :sortable="true">
                                    <template #body="slotProps">
                                        <span :class="'order-badge order-' + ((slotProps as any).data.status ? (slotProps as any).data.status.toLowerCase() : '')">{{ (slotProps as any).data.status }}</span>
                                    </template>
                                </Column>
                                <Column headerStyle="width:4rem">
                                    <template #body>
                                        <Button icon="pi pi-search" />
                                    </template>
                                </Column>
                            </DataTable>
                        </div>
                    </template>
                </DataTable>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <h5>Subheader Grouping</h5>
                <DataTable :value="customer3" rowGroupMode="subheader" groupRowsBy="representative.name" sortMode="single" sortField="representative.name" :sortOrder="1" scrollable scrollHeight="400px">
                    <Column field="representative.name" header="Representative"></Column>
                    <Column field="name" header="Name" style="min-width: 200px"></Column>
                    <Column field="country" header="Country" style="min-width: 200px">
                        <template #body="slotProps">
                            <div class="flex align-items-center gap-2">
                                <img alt="flag" src="/demo/images/flag/flag_placeholder.png" :class="`flag flag-${(slotProps as any).data.country.code}`" style="width: 24px" />
                                <span>{{ (slotProps as any).data.country.name }}</span>
                            </div>
                        </template>
                    </Column>
                    <Column field="company" header="Company" style="min-width: 200px"></Column>
                    <Column field="status" header="Status" style="min-width: 200px">
                        <template #body="slotProps">
                            <Tag :severity="getSeverity((slotProps as any).data.status)">{{ (slotProps as any).data.status.toUpperCase() }}</Tag>
                        </template>
                    </Column>
                    <Column field="date" header="Date" style="min-width: 200px"></Column>
                    <template #groupheader="slotProps">
                        <div class="flex align-items-center gap-2">
                            <img :alt="(slotProps as any).data.representative.name" :src="'/demo/images/avatar/' + (slotProps as any).data.representative.image" width="32" style="vertical-align: middle" />
                            <span>{{ (slotProps as any).data.representative.name }}</span>
                        </div>
                    </template>
                    <template #groupfooter="slotProps">
                        <td style="text-align: right" class="text-bold pr-6">Total Customers: {{ calculateCustomerTotal((slotProps as any).data.representative.name) }}</td>
                    </template>
                </DataTable>
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss">
:deep(.p-datatable-frozen-tbody) {
    font-weight: bold;
}

:deep(.p-datatable-scrollable .p-frozen-column) {
    font-weight: bold;
}
</style>
