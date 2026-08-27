<script setup>
import { useI18n } from "vue-i18n";
import AiChatWorkspace from "@/components/ai/AiChatWorkspace.vue";

const { t } = useI18n();
</script>

<template><AiChatWorkspace mode="technician" :title="t('ai_chat_workspace.technician_diagnostic_title')" /></template>
