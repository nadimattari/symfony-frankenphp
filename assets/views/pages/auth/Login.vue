<script setup lang="ts">
import { computed, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import AppConfig from "../../../layout/AppConfig.vue";
import { useLayout } from "../../../layout/composables/layout";
import { useAuthStore } from "../../../store/auth";
import { ApiError } from "../../../utils/fetch";

const { layoutConfig } = useLayout();
const route = useRoute();
const router = useRouter();
const auth = useAuthStore();

const username = ref("");
const password = ref("");
const submitted = ref(false);
const submitting = ref(false);
const errorMessage = ref("");

const usernameInvalid = computed(
    () => submitted.value && username.value.trim() === "",
);
const passwordInvalid = computed(
    () => submitted.value && password.value === "",
);

const logoUrl = computed(() => {
    return `/layout/images/${layoutConfig.darkTheme.value ? "logo-white" : "logo-dark"}.svg`;
});

async function authenticate(): Promise<void> {
    submitted.value = true;
    errorMessage.value = "";

    if (usernameInvalid.value || passwordInvalid.value) {
        return;
    }

    submitting.value = true;
    try {
        await auth.login({
            username: username.value.trim(),
            password: password.value,
        });

        const redirect =
            typeof route.query.redirect === "string"
                ? route.query.redirect
                : "/";
        await router.push(redirect);
    } catch (error) {
        errorMessage.value =
            error instanceof ApiError && error.status === 401
                ? "Credentials are incorrect. Please try again."
                : "Sign in failed. Please try again.";
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <div
        class="bg-surface-50 dark:bg-surface-950 flex items-center justify-center min-h-screen min-w-screen overflow-hidden"
    >
        <div class="flex flex-col items-center justify-center">
            <div
                style="
                    border-radius: 56px;
                    padding: 0.3rem;
                    background: linear-gradient(
                        180deg,
                        var(--p-primary-color) 10%,
                        rgba(33, 150, 243, 0) 30%
                    );
                "
            >
                <div
                    class="w-full bg-surface-0 dark:bg-surface-900 py-20 px-8 sm:px-20"
                    style="border-radius: 53px"
                >
                    <div class="text-center mb-8">
                        <img
                            :src="logoUrl"
                            alt="Sakai logo"
                            class="mb-8 w-16 shrink-0 mx-auto"
                        />
                        <div
                            class="text-surface-900 dark:text-surface-0 text-3xl font-medium mb-4"
                        >
                            Welcome to PrimeLand!
                        </div>
                        <span class="text-muted-color font-medium"
                            >Sign in to continue</span
                        >
                    </div>

                    <form novalidate @submit.prevent="authenticate">
                        <label
                            for="username"
                            class="block text-surface-900 dark:text-surface-0 text-xl font-medium mb-2"
                            >Username</label
                        >
                        <InputText
                            id="username"
                            v-model="username"
                            type="text"
                            autocomplete="username"
                            placeholder="Username"
                            class="w-full md:w-[30rem] mb-2"
                            :invalid="usernameInvalid"
                            :aria-invalid="usernameInvalid"
                            aria-describedby="username-error"
                        />
                        <small
                            v-if="usernameInvalid"
                            id="username-error"
                            class="block text-red-500 mb-6"
                            role="alert"
                            >Enter your username.</small
                        >
                        <div v-else class="mb-8"></div>

                        <label
                            for="password"
                            class="block text-surface-900 dark:text-surface-0 font-medium text-xl mb-2"
                            >Password</label
                        >
                        <InputPassword
                            id="password"
                            v-model="password"
                            placeholder="Password"
                            autocomplete="current-password"
                            :feedback="false"
                            :toggle-mask="true"
                            fluid
                            class="mb-2"
                            :invalid="passwordInvalid"
                            :aria-invalid="passwordInvalid"
                            aria-describedby="password-error"
                        />
                        <small
                            v-if="passwordInvalid"
                            id="password-error"
                            class="block text-red-500 mb-6"
                            role="alert"
                            >Enter your password.</small
                        >
                        <div v-else class="mb-8"></div>

                        <small
                            v-if="errorMessage"
                            id="auth-error"
                            class="block text-red-500 mb-4"
                            role="alert"
                            >{{ errorMessage }}</small
                        >

                        <Button
                            type="submit"
                            label="Sign In"
                            class="w-full"
                            :loading="submitting"
                            :disabled="submitting"
                        />
                    </form>
                </div>
            </div>
        </div>
    </div>
    <AppConfig simple />
</template>

<style scoped>
.pi-eye {
    transform: scale(1.6);
    margin-right: 1rem;
}

.pi-eye-slash {
    transform: scale(1.6);
    margin-right: 1rem;
}
</style>
