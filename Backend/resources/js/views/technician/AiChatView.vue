<script setup>
import { useI18n } from "vue-i18n";
import AiChatWorkspace from "@/components/ai/AiChatWorkspace.vue";

const { t } = useI18n();
</script>

<template>
  <div class="sm:w-screen sm:mx-[calc(50%-50vw)]">
    <div class="sm:max-w-5xl sm:mx-auto sm:px-4">
      <AiChatWorkspace mode="technician" :title="t('ai_chat_workspace.technician_diagnostic_title')" />
    </div>
  </div>
</template>