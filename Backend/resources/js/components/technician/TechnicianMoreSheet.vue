<script setup>
import { computed } from "vue";
import { useRouter } from "vue-router";
import { useI18n } from "vue-i18n";
import { useAuthStore } from "@/stores/auth";
import { useConfirm } from "@/composables/useConfirm";
import { LogOut } from "@lucide/vue";
import AppIcon from "@/components/ui/AppIcon.vue";


const props = defineProps({ open: { type: Boolean, default: false } });
const emit = defineEmits(["close"]);

const router = useRouter();
const authStore = useAuthStore();
const { t } = useI18n();
const { confirm } = useConfirm();

const PRIMARY_ITEMS = computed(() => [
  { label: t("technician_portal.quick_complaint_label"), icon: "fa-comment-exclamation", routeName: "technician.complaint" },
  { label: t("technician_portal.quick_fault_label"), icon: "fa-triangle-exclamation", routeName: "technician.fault" },
  { label: t("menu.ai_chat"), icon: "fa-robot", routeName: "technician.ai-chat" },
]);

// إعدادات الحساب — مكوّن مشترك بين كل الأدوار (SHARED)، ليست جزءًا من التبويبات
// الخمسة الأساسية، لكن أُعيدت هنا لأنها الوسيلة الوحيدة لدى الفني لتغيير كلمة
// المرور أو بياناته الشخصية.
const SECONDARY_ITEMS = computed(() => [
  { label: t("technician_portal.settings_label"), icon: "fa-gear", routeName: "technician.settings" },
]);

function go(routeName) {
  emit("close");
  router.push({ name: routeName });
}

async function handleLogout() {
  const confirmed = await confirm({
    title: t("common.logout"),
    message: t("common.logout_confirm"),
    confirmLabel: t("common.logout"),
    variant: "danger",
  });
  if (!confirmed) return;

  emit("close");
  await authStore.logout();
  router.push({ name: "login" });
}
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="props.open"
        class="fixed inset-0 z-50 bg-gray-700/40 backdrop-blur-[2px]"
        @click.self="emit('close')"
      >
        <Transition
          enter-active-class="transition duration-250 ease-out"
          enter-from-class="translate-y-full"
          enter-to-class="translate-y-0"
          leave-active-class="transition duration-200 ease-in"
          leave-from-class="translate-y-0"
          leave-to-class="translate-y-full"
        >
          <div
            v-if="props.open"
            class="absolute inset-x-0 bottom-0 bg-surface dark:bg-[#1c1e20] rounded-t-2xl p-4 pb-[calc(1rem+env(safe-area-inset-bottom))] max-h-[80vh] overflow-y-auto"
          >
            <div class="w-10 h-1 rounded-full bg-border mx-auto mb-4"></div>

            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 mb-2 px-1">{{ t("technician_portal.more_label") }}</h3>
            <div class="grid grid-cols-3 gap-2 mb-4">
              <button
                v-for="item in PRIMARY_ITEMS"
                :key="item.routeName"
                type="button"
                @click="go(item.routeName)"
                class="flex flex-col items-center justify-center gap-2 p-4 rounded-card border border-border hover:border-primary-300 hover:bg-primary-50/40 transition"
              >
                <AppIcon :name="item.icon" class="text-xl text-primary-600" />
                <span class="text-xs font-medium text-gray-700 dark:text-gray-300 text-center">{{ item.label }}</span>
              </button>
            </div>

            <div class="border-t border-border pt-2 space-y-0.5">
              <button
                v-for="item in SECONDARY_ITEMS"
                :key="item.routeName"
                type="button"
                @click="go(item.routeName)"
                class="w-full flex items-center gap-3 px-3 py-3 rounded-lg text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5 transition text-start"
              >
                <AppIcon :name="item.icon" class="w-5 text-center text-gray-400 dark:text-gray-500" />
                {{ item.label }}
              </button>

              <button
                type="button"
                @click="handleLogout"
                class="w-full flex items-center gap-3 px-3 py-3 rounded-lg text-sm text-danger hover:bg-danger-bg transition text-start"
              >
                <LogOut class="w-5 text-center" aria-hidden="true" />
                {{ t("common.logout") }}
              </button>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>
