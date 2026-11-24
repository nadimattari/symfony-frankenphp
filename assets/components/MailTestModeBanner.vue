<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';

import { mailService } from '../service/mail';
import { useAuthStore } from '../store/auth';
import type { MailSettings } from '../types/api';

/**
 * Non-critical warning shown only to authenticated users. A failed settings
 * request simply hides the banner instead of interrupting the page.
 */
const auth = useAuthStore();
const settings = ref<MailSettings | null>(null);

async function loadSettings(): Promise<void> {
    if (!auth.isAuthenticated) {
        settings.value = null;
        return;
    }

    try {
        settings.value = await mailService.settings();
    } catch {
        settings.value = null;
    }
}

onMounted(() => {
    void loadSettings();
});

watch(() => auth.isAuthenticated, () => {
    void loadSettings();
});
</script>

<template>
    <Message v-if="settings?.testMode" severity="warn" :closable="false" class="mb-4">
        <span class="font-semibold">TEST MODE</span>
        <span v-if="settings.redirectTo"> — all mail is redirected to <strong>{{ settings.redirectTo }}</strong>.</span>
        <span v-else> — messages go to a local test inbox.</span>
    </Message>
</template>

<style scoped></style>
