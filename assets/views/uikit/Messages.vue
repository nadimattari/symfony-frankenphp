<script setup lang="ts">
import { ref } from "vue";
import { useToast } from "primevue/usetoast";
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import Message from "primevue/message";

interface Msg {
    severity: "success" | "info" | "warn" | "error";
    detail: string;
    content: string;
    id: number;
}

const toast = useToast();
const message = ref<Msg[]>([]);
const username = ref<string | null>(null);
const email = ref<string | null>(null);
const count = ref(0);

const addMessage = (type: Msg["severity"]): void => {
    const details: Record<
        Msg["severity"],
        { detail: string; content: string }
    > = {
        success: { detail: "Success Message", content: "Message sent" },
        info: { detail: "Info Message", content: "PrimeVue rocks" },
        warn: { detail: "Warn Message", content: "There are unsaved changes" },
        error: { detail: "Error Message", content: "Validation failed" },
    };
    const entry = details[type];
    message.value = [
        {
            severity: type,
            detail: entry.detail,
            content: entry.content,
            id: count.value++,
        },
    ];
};

const showSuccess = (): void => {
    toast.add({
        severity: "success",
        summary: "Success Message",
        detail: "Message Detail",
        life: 3000,
    });
};

const showInfo = (): void => {
    toast.add({
        severity: "info",
        summary: "Info Message",
        detail: "Message Detail",
        life: 3000,
    });
};

const showWarn = (): void => {
    toast.add({
        severity: "warn",
        summary: "Warn Message",
        detail: "Message Detail",
        life: 3000,
    });
};

const showError = (): void => {
    toast.add({
        severity: "error",
        summary: "Error Message",
        detail: "Message Detail",
        life: 3000,
    });
};
</script>

<template>
    <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12 lg:col-span-6">
            <div class="card">
                <h5>Toast</h5>
                <Button
                    @click="showSuccess()"
                    label="Success"
                    class="mr-2"
                    severity="success"
                />
                <Button
                    @click="showInfo()"
                    label="Info"
                    class="mr-2"
                    severity="info"
                />
                <Button
                    @click="showWarn()"
                    label="Warn"
                    class="mr-2"
                    severity="warning"
                />
                <Button
                    @click="showError()"
                    label="Error"
                    class="mr-2"
                    severity="danger"
                />
            </div>
        </div>

        <div class="col-span-12 lg:col-span-6">
            <div class="card">
                <h5>Messages</h5>
                <Button
                    label="Success"
                    @click="addMessage('success')"
                    class="mr-2"
                    severity="success"
                />
                <Button
                    label="Info"
                    @click="addMessage('info')"
                    class="mr-2"
                    severity="info"
                />
                <Button
                    label="Warn"
                    @click="addMessage('warn')"
                    class="mr-2"
                    severity="warning"
                />
                <Button
                    label="Error"
                    @click="addMessage('error')"
                    class="mr-2"
                    severity="danger"
                />

                <transition-group name="p-message" tag="div">
                    <Message
                        v-for="msg of message"
                        :severity="msg.severity"
                        :key="msg.content"
                        >{{ msg.content }}</Message
                    >
                </transition-group>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-8">
            <div class="card">
                <h5>Inline</h5>
                <div class="mb-4 flex flex-wrap items-center gap-4">
                    <label for="username1" class="w-[9rem] shrink-0"
                        >Username</label
                    >
                    <div class="min-w-0 flex-1">
                        <InputText
                            id="username1"
                            v-model="username"
                            :required="true"
                            invalid
                            class="mr-2"
                        ></InputText>
                        <Message severity="error">Username is required</Message>
                    </div>
                </div>
                <div class="mb-4 flex flex-wrap items-center gap-4">
                    <label for="email" class="w-[9rem] shrink-0">Email</label>
                    <div class="min-w-0 flex-1">
                        <InputText
                            id="email"
                            v-model="email"
                            :required="true"
                            invalid
                            class="mr-2"
                        ></InputText>
                        <Message severity="error" icon="pi pi-times-circle" />
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-4">
            <div class="card">
                <h5>Help Text</h5>
                <div
                    class="mb-4 [&>label]:mb-2 [&>small]:mt-1 w-full [&_.p-component]:w-full"
                >
                    <label for="username2">Username</label>
                    <InputText
                        id="username2"
                        type="username"
                        class="text-red-500"
                        aria-describedby="username-help"
                    />
                    <small id="username-help" class="text-red-500"
                        >Enter your username to reset your password.</small
                    >
                </div>
            </div>
        </div>
    </div>
</template>
