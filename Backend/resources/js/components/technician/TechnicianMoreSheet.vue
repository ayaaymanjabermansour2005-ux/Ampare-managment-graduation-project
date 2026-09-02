<script setup>
import { computed } from "vue";
import { useRouter } from "vue-router";
import { useI18n } from "vue-i18n";
import { useAuthStore } from "@/stores/auth";
import { useConfirm } from "@/composables/useConfirm";
import { ChevronLeft, ChevronRight, LogOut } from "@lucide/vue";
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
        class="fixed inset-0 z-[100] bg-black/50 backdrop-blur-sm"
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
            class="glass-card !rounded-b-none !rounded-t-2xl absolute inset-x-0 bottom-0 w-full max-w-lg mx-auto !bg-white/95 dark:!bg-[#1c1e20]/97 p-4 pb-[calc(1rem+env(safe-area-inset-bottom))] max-h-[80vh] overflow-y-auto shadow-2xl"
          >
            <div class="w-10 h-1 rounded-full bg-[#e7e2d6] dark:bg-white/15 mx-auto mb-4"></div>

            <h3 class="text-[11.5px] font-bold text-[#8A6D1F] dark:text-[#D4AF37] tracking-wide mb-3 px-1">
              {{ t("technician_portal.more_label") }}
            </h3>

            <div class="grid grid-cols-3 gap-2.5 mb-4">
              <button
                v-for="item in PRIMARY_ITEMS"
                :key="item.routeName"
                type="button"
                @click="go(item.routeName)"
                class="flex flex-col items-center justify-center gap-2.5 p-4 rounded-2xl border border-[#e7e2d6] dark:border-white/10 hover:border-[#8A6D1F]/40 hover:bg-[#EBF1E7]/40 dark:hover:bg-white/5 transition"
              >
                <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#3E582E] to-[#52733D] text-white flex items-center justify-center text-base shrink-0">
                  <AppIcon :name="item.icon" />
                </span>
                <span class="text-[11.5px] font-bold text-center leading-tight">{{ item.label }}</span>
              </button>
            </div>

            <div class="border-t border-[#f0ece0] dark:border-white/5 pt-2 space-y-0.5">
              <button
                v-for="item in SECONDARY_ITEMS"
                :key="item.routeName"
                type="button"
                @click="go(item.routeName)"
                class="w-full flex items-center gap-3 px-3 py-3 rounded-xl text-[12.5px] font-bold hover:bg-[#f4efe5]/60 dark:hover:bg-white/5 transition text-start"
              >
                <span class="w-8 h-8 rounded-lg bg-[#EBF1E7] dark:bg-white/5 flex items-center justify-center text-[#3E582E] dark:text-[#a8d19a] shrink-0">
                  <AppIcon :name="item.icon" />
                </span>
                <span class="flex-1 min-w-0">{{ item.label }}</span>
                <ChevronLeft class="rtl:block ltr:hidden text-[#c9c3b2] dark:text-white/20 text-xs" aria-hidden="true" />
                <ChevronRight class="ltr:block rtl:hidden text-[#c9c3b2] dark:text-white/20 text-xs" aria-hidden="true" />
              </button>

              <button
                type="button"
                @click="handleLogout"
                class="w-full flex items-center gap-3 px-3 py-3 rounded-xl text-[12.5px] font-bold text-[#D9534F] hover:bg-[#D9534F]/10 transition text-start"
              >
                <span class="w-8 h-8 rounded-lg bg-[#D9534F]/10 flex items-center justify-center text-[#D9534F] shrink-0">
                  <LogOut class="text-sm" aria-hidden="true" />
                </span>
                <span class="flex-1 min-w-0">{{ t("common.logout") }}</span>
              </button>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>