<script setup lang="ts">
import { ref } from "vue";

interface DropdownItem {
    name: string;
    code: string;
}

const dropdownItems = ref<DropdownItem[]>([
    { name: "Option 1", code: "Option 1" },
    { name: "Option 2", code: "Option 2" },
    { name: "Option 3", code: "Option 3" },
]);

const dropdownItem = ref<DropdownItem | null>(null);
</script>

<template>
    <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12 md:col-span-6">
            <div class="card w-full [&_.p-component]:w-full">
                <h5>Vertical</h5>
                <div class="mb-4 [&>label]:mb-2 [&>small]:mt-1">
                    <label for="name1">Name</label>
                    <InputText id="name1" type="text" />
                </div>
                <div class="mb-4 [&>label]:mb-2 [&>small]:mt-1">
                    <label for="email1">Email</label>
                    <InputText id="email1" type="text" />
                </div>
                <div class="mb-4 [&>label]:mb-2 [&>small]:mt-1">
                    <label for="age1">Age</label>
                    <InputText id="age1" type="text" />
                </div>
            </div>

            <div class="card w-full [&_.p-component]:w-full">
                <h5>Vertical Grid</h5>
                <div class="grid grid-cols-12 gap-4">
                    <div class="mb-4 col-span-12 md:col-span-6">
                        <label for="name2">Name</label>
                        <InputText id="name2" type="text" />
                    </div>
                    <div class="mb-4 col-span-12 md:col-span-6">
                        <label for="email2">Email</label>
                        <InputText id="email2" type="text" />
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 md:col-span-6">
            <div class="card w-full [&_.p-component]:w-full">
                <h5>Horizontal</h5>
                <div
                    class="mb-4 grid grid-cols-12 items-center [&>label]:flex [&>label]:items-center"
                >
                    <label
                        for="name3"
                        class="col-span-12 mb-2 md:col-span-2 md:mb-0"
                        >Name</label
                    >
                    <div class="col-span-12 md:col-span-10">
                        <InputText id="name3" type="text" />
                    </div>
                </div>
                <div
                    class="mb-4 grid grid-cols-12 items-center [&>label]:flex [&>label]:items-center"
                >
                    <label
                        for="email3"
                        class="col-span-12 mb-2 md:col-span-2 md:mb-0"
                        >Email</label
                    >
                    <div class="col-span-12 md:col-span-10">
                        <InputText id="email3" type="text" />
                    </div>
                </div>
            </div>

            <div class="card">
                <h5>Inline</h5>
                <div class="flex flex-wrap items-center gap-4">
                    <div class="mb-4 [&>label]:mb-2 [&>small]:mt-1">
                        <label for="firstname1" class="sr-only"
                            >Firstname</label
                        >
                        <InputText
                            id="firstname1"
                            type="text"
                            placeholder="Firstname"
                        />
                    </div>
                    <div class="mb-4 [&>label]:mb-2 [&>small]:mt-1">
                        <label for="lastname1" class="sr-only">Lastname</label>
                        <InputText
                            id="lastname1"
                            type="text"
                            placeholder="Lastname"
                        />
                    </div>
                    <Button label="Submit"></Button>
                </div>
            </div>

            <div class="card">
                <h5>Help Text</h5>
                <div
                    class="mb-4 [&>label]:mb-2 [&>small]:mt-1 w-full [&_.p-component]:w-full"
                >
                    <label for="username">Username</label>
                    <InputText id="username" type="text" />
                    <small>Enter your username to reset your password.</small>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="card">
                <h5>Advanced</h5>
                <div
                    class="w-full [&_.p-component]:w-full grid grid-cols-12 gap-4"
                >
                    <div
                        class="mb-4 [&>label]:mb-2 [&>small]:mt-1 col-span-12 md:col-span-6"
                    >
                        <label for="firstname2">Firstname</label>
                        <InputText id="firstname2" type="text" />
                    </div>
                    <div
                        class="mb-4 [&>label]:mb-2 [&>small]:mt-1 col-span-12 md:col-span-6"
                    >
                        <label for="lastname2">Lastname</label>
                        <InputText id="lastname2" type="text" />
                    </div>
                    <div class="mb-4 [&>label]:mb-2 [&>small]:mt-1 col-span-12">
                        <label for="address">Address</label>
                        <Textarea id="address" rows="4" />
                    </div>
                    <div
                        class="mb-4 [&>label]:mb-2 [&>small]:mt-1 col-span-12 md:col-span-6"
                    >
                        <label for="city">City</label>
                        <InputText id="city" type="text" />
                    </div>
                    <div
                        class="mb-4 [&>label]:mb-2 [&>small]:mt-1 col-span-12 md:col-span-3"
                    >
                        <label for="state">State</label>
                        <Select
                            id="state"
                            v-model="dropdownItem"
                            :options="dropdownItems"
                            optionLabel="name"
                            placeholder="Select One"
                        ></Select>
                    </div>
                    <div
                        class="mb-4 [&>label]:mb-2 [&>small]:mt-1 col-span-12 md:col-span-3"
                    >
                        <label for="zip">Zip</label>
                        <InputText id="zip" type="text" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
