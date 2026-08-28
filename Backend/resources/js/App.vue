<script setup>
import { ref } from "vue";
import { RouterView, useRouter } from "vue-router";
import { storeToRefs } from "pinia";
import { useI18n } from "vue-i18n";
import { setLucideProps } from "@lucide/vue";
import { useConnectivityStore } from "./stores/connectivity";
import { useAuthStore } from "./stores/auth";
import { useRealtimeNotifications } from "./composables/useRealtimeNotifications";
import ConfirmDialog from "./components/common/ConfirmDialog.vue";
import ToastContainer from "./components/common/ToastContainer.vue";
import SplashView from "./views/splash/SplashView.vue";

const router = useRouter();
const connectivityStore = useConnectivityStore();
const authStore = useAuthStore();
const { t } = useI18n();

// سياسة الأيقونات الموحّدة على مستوى التطبيق كامل: كل أيقونة Lucide بترث حجمها
// من font-size السياق المحيط بها (1em) بدل حجم بكسل ثابت — نفس سلوك <i> الخاص
// بـ Font Awesome سابقًا، فبالتالي الهجرة ما بتغيّر أي حجم ظاهر فعليًا. سماكة
// الخط 2 كـ default موحّد؛ أي مكوّن بحاجة قيمة مختلفة يمرّرها محليًا كـ prop.
setLucideProps({ size: "1em", strokeWidth: 2 });

const { needsReauth, pendingCount, isOnline } = storeToRefs(connectivityStore);

useRealtimeNotifications();

// الـ Splash يُخفى فقط بعد اكتمال فحص الجلسة الأولي (authStore.initialized،
// تُصبح true داخل fetchUser() في finally — راجع router/index.js) + انتهاء
// الحد الأدنى الداخلي (minDuration) الخاص بالمكون نفسه. لا setTimeout إضافي هنا.
const splashDone = ref(false);

function goToLogin() {
  router.push({ name: "login" });
}
</script>

<template>
  <SplashView v-if="!splashDone" :ready="authStore.initialized" @done="splashDone = true" />

  <template v-else>
    <div v-if="needsReauth" class="reauth-banner" role="alert">
      <span>{{ t("common.reauth_banner_message", { count: pendingCount }) }}</span>
      <button type="button" @click="goToLogin">{{ t("common.reauth_banner_login_button") }}</button>
    </div>

    <div v-if="!isOnline" class="offline-banner" role="status">
      {{ t("common.offline_banner_message") }}
    </div>

    <RouterView />
  </template>

  <ConfirmDialog />
  <ToastContainer />
</template>

<style scoped>
.reauth-banner,
.offline-banner {
  position: sticky;
  top: 0;
  z-index: 50;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 10px 16px;
  font-size: 0.9rem;
}

.reauth-banner {
  background: #fef3c7;
  color: #92400e;
}

.reauth-banner button {
  flex-shrink: 0;
  padding: 4px 14px;
  border-radius: 6px;
  border: 1px solid #92400e;
  background: transparent;
  color: #92400e;
  cursor: pointer;
  font-weight: 600;
}

.reauth-banner button:hover {
  background: #92400e;
  color: #fef3c7;
}

.offline-banner {
  background: #fee2e2;
  color: #991b1b;
  justify-content: center;
}
</style>