<script setup>
import { ref, reactive, onMounted, computed } from "vue";
import { useI18n } from "vue-i18n";
import settingService from "@/services/settingService";
import neighborhoodService from "@/services/neighborhoodService";
import commissionTierService from "@/services/commissionTierService";
import { useConfirm } from "@/composables/useConfirm";
import { normalizeApiError } from "@/utils/normalizeApiError";
import { vReveal } from "@/directives/reveal";
import { ChevronLeft, ChevronRight, CircleAlert, CircleCheck, Info, LoaderCircle, MapPinned, Save, SlidersHorizontal, TriangleAlert } from "@lucide/vue";
import AppIcon from "@/components/ui/AppIcon.vue";


const { t, locale } = useI18n();
const { confirm } = useConfirm();

const TABS = [
  { key: "general", labelKey: "menu_groups.general", icon: "fa-gear" },
  { key: "pages", labelKey: "settings_page.tab_pages", icon: "fa-file-lines" },
  { key: "keys", labelKey: "settings_page.tab_keys", icon: "fa-key" },
  { key: "registration", labelKey: "settings_page.tab_registration", icon: "fa-user-plus" },
  { key: "maintenance", labelKey: "settings_page.tab_maintenance", icon: "fa-screwdriver-wrench" },
  { key: "neighborhoods", labelKey: "settings_page.tab_neighborhoods", icon: "fa-map-location-dot" },
  { key: "commission_tiers", labelKey: "settings_page.tab_commission_tiers", icon: "fa-percent" },
];
const activeTab = ref("general");
function tabLabel(tab) {
  return t(tab.labelKey);
}

const settings = ref({});
const isLoading = ref(true);
const isSaving = ref(false);
const error = ref(null);
const saveSuccess = ref(false);

const GENERAL_FIELDS = [
  { key: "site_name", labelKey: "settings_page.field_site_name", type: "text" },
  { key: "logo_url", labelKey: "settings_page.field_logo_url", type: "url" },
  { key: "favicon_url", labelKey: "settings_page.field_favicon_url", type: "url" },
  { key: "email_logo_url", labelKey: "settings_page.field_email_logo_url", type: "url" },
  { key: "invoice_logo_url", labelKey: "settings_page.field_invoice_logo_url", type: "url" },
  { key: "default_language", labelKey: "settings_page.field_default_language", type: "text" },
  { key: "default_currency", labelKey: "settings_page.field_default_currency", type: "text" },
  { key: "default_ampere_price", labelKey: "settings_page.field_default_ampere_price", type: "number" },
  { key: "platform_fee_percentage", labelKey: "settings_page.field_platform_fee_percentage", type: "number" },
  { key: "tax_percentage", labelKey: "settings_page.field_tax_percentage", type: "number" },
  { key: "fallback_usd_ils_rate", labelKey: "settings_page.field_fallback_usd_ils_rate", type: "number" },
];

const PAGE_FIELDS = [
  { key: "about_page_content", labelKey: "settings_page.page_about" },
  { key: "privacy_policy_content", labelKey: "settings_page.page_privacy_policy" },
  { key: "terms_content", labelKey: "settings_page.page_terms" },
  { key: "contact_page_content", labelKey: "settings_page.page_contact" },
];

const SENSITIVE_FIELDS = [
  { key: "maps_api_key", labelKey: "settings_page.key_maps_api" },
  { key: "sms_api_key", labelKey: "settings_page.key_sms_api" },
  { key: "whatsapp_api_key", labelKey: "settings_page.key_whatsapp_api" },
  { key: "payment_gateway_key", labelKey: "settings_page.key_payment_gateway" },
];
const sensitiveInputs = reactive({});

const REGISTRATION_FIELDS = [
  { key: "allow_subscriber_registration", labelKey: "settings_page.reg_allow_subscriber_label", descKey: "settings_page.reg_allow_subscriber_desc" },
  { key: "require_email_verification", labelKey: "settings_page.reg_require_email_verification_label", descKey: "settings_page.reg_require_email_verification_desc" },
  { key: "review_new_accounts_before_activation", labelKey: "settings_page.reg_review_new_accounts_label", descKey: "settings_page.reg_review_new_accounts_desc" },
];
function fieldLabel(f) { return t(f.labelKey); }
function fieldDesc(f) { return t(f.descKey); }

async function fetchSettings() {
  isLoading.value = true;
  error.value = null;
  try {
    const { data } = await settingService.index();
    settings.value = data.data;
  } catch (err) {
    error.value = normalizeApiError(err, t("settings_page.load_failed")).message;
  } finally {
    isLoading.value = false;
  }
}

function buildSavePayload() {
  const payload = { ...settings.value };

  SENSITIVE_FIELDS.forEach((f) => delete payload[f.key]);

  Object.entries(sensitiveInputs).forEach(([key, value]) => {
    if (value) payload[key] = value;
  });

  return payload;
}

async function handleSave() {
  isSaving.value = true;
  error.value = null;
  saveSuccess.value = false;
  try {
    await settingService.update(buildSavePayload());
    saveSuccess.value = true;
    Object.keys(sensitiveInputs).forEach((k) => (sensitiveInputs[k] = ""));
    await fetchSettings();
    setTimeout(() => (saveSuccess.value = false), 3000);
  } catch (err) {
    error.value = normalizeApiError(err, t("settings_page.save_failed")).message;
  } finally {
    isSaving.value = false;
  }
}

function toggleBoolean(key) {
  settings.value[key] = !settings.value[key];
}

const neighborhoods = ref([]);
const isLoadingNeighborhoods = ref(false);
const newNeighborhoodName = ref("");
const newNeighborhoodNameEn = ref("");
const isSavingNeighborhood = ref(false);
const neighborhoodError = ref(null);
const editingId = ref(null);
const editingName = ref("");
const editingNameEn = ref("");

async function fetchNeighborhoods() {
  isLoadingNeighborhoods.value = true;
  try {
    const { data } = await neighborhoodService.list();
    neighborhoods.value = data.data;
  } finally {
    isLoadingNeighborhoods.value = false;
  }
}

async function addNeighborhood() {
  if (!newNeighborhoodName.value.trim()) return;
  isSavingNeighborhood.value = true;
  neighborhoodError.value = null;
  try {
    await neighborhoodService.store({
      name: newNeighborhoodName.value.trim(),
      name_en: newNeighborhoodNameEn.value.trim() || null,
    });
    newNeighborhoodName.value = "";
    newNeighborhoodNameEn.value = "";
    await fetchNeighborhoods();
  } catch (err) {
    const normalized = normalizeApiError(err, t("settings_page.add_neighborhood_failed"));
    neighborhoodError.value = normalized.fieldError("name") ?? normalized.message;
  } finally {
    isSavingNeighborhood.value = false;
  }
}

function startEdit(n) {
  editingId.value = n.id;
  editingName.value = n.name;
  editingNameEn.value = n.name_en ?? "";
}

async function saveEdit(id) {
  neighborhoodError.value = null;
  try {
    await neighborhoodService.update(id, {
      name: editingName.value.trim(),
      name_en: editingNameEn.value.trim() || null,
    });
    editingId.value = null;
    await fetchNeighborhoods();
  } catch (err) {
    const normalized = normalizeApiError(err, t("settings_page.update_neighborhood_failed"));
    neighborhoodError.value = normalized.fieldError("name") ?? normalized.message;
  }
}

const deletingId = ref(null);
async function deleteNeighborhood(id) {
  // FIX: كان الحذف يتنفذ فورًا بدون أي تأكيد، خلافًا لكل عمليات الحذف
  // الأخرى بالتطبيق (تستخدم useConfirm() قبل أي حذف نهائي).
  const confirmed = await confirm({
    title: t("settings_page.delete_neighborhood_title"),
    message: t("settings_page.delete_neighborhood_confirm_message"),
    confirmLabel: t("common.delete"),
    variant: "danger",
  });
  if (!confirmed) return;

  deletingId.value = id;
  neighborhoodError.value = null;
  try {
    await neighborhoodService.destroy(id);
    await fetchNeighborhoods();
  } catch (err) {
    const normalized = normalizeApiError(err, t("settings_page.delete_neighborhood_failed"));
    neighborhoodError.value = normalized.fieldError("neighborhood") ?? normalized.message;
  } finally {
    deletingId.value = null;
  }
}

/* ---------------- شرائح العمولة التلقائية (CommissionTier) ----------------
 * كانت CommissionTierController/Service مبنيّة بالكامل ومستخدَمة فعليًا في
 * حساب العمولة الحقيقي (CommissionRateResolver) بدون أي واجهة إدارية على
 * الإطلاق — الطريقة الوحيدة كانت DB/tinker مباشرة.
 */
const commissionTiers = ref([]);
const isLoadingCommissionTiers = ref(false);
const commissionTierError = ref(null);

async function fetchCommissionTiers() {
  isLoadingCommissionTiers.value = true;
  try {
    const { data } = await commissionTierService.list();
    commissionTiers.value = data.data;
  } finally {
    isLoadingCommissionTiers.value = false;
  }
}

function emptyTierForm() {
  return { min_generators_count: "", max_generators_count: "", commission_rate: "", is_active: true };
}
const newTierForm = reactive(emptyTierForm());
const isSavingTier = ref(false);

async function addCommissionTier() {
  isSavingTier.value = true;
  commissionTierError.value = null;
  try {
    await commissionTierService.create({
      min_generators_count: Number(newTierForm.min_generators_count),
      max_generators_count: newTierForm.max_generators_count === "" ? null : Number(newTierForm.max_generators_count),
      commission_rate: Number(newTierForm.commission_rate),
      is_active: newTierForm.is_active,
    });
    Object.assign(newTierForm, emptyTierForm());
    await fetchCommissionTiers();
  } catch (err) {
    const normalized = normalizeApiError(err, t("settings_page.add_commission_tier_failed"));
    commissionTierError.value = normalized.fieldError("min_generators_count") ?? normalized.fieldError("max_generators_count") ?? normalized.fieldError("commission_rate") ?? normalized.message;
  } finally {
    isSavingTier.value = false;
  }
}

const editingTierId = ref(null);
const editingTierForm = reactive(emptyTierForm());

function startEditTier(tier) {
  editingTierId.value = tier.id;
  editingTierForm.min_generators_count = tier.min_generators_count;
  editingTierForm.max_generators_count = tier.max_generators_count ?? "";
  editingTierForm.commission_rate = tier.commission_rate;
  editingTierForm.is_active = tier.is_active;
}

async function saveEditTier(id) {
  commissionTierError.value = null;
  try {
    await commissionTierService.update(id, {
      min_generators_count: Number(editingTierForm.min_generators_count),
      max_generators_count: editingTierForm.max_generators_count === "" ? null : Number(editingTierForm.max_generators_count),
      commission_rate: Number(editingTierForm.commission_rate),
      is_active: editingTierForm.is_active,
    });
    editingTierId.value = null;
    await fetchCommissionTiers();
  } catch (err) {
    const normalized = normalizeApiError(err, t("settings_page.update_commission_tier_failed"));
    commissionTierError.value = normalized.fieldError("min_generators_count") ?? normalized.fieldError("max_generators_count") ?? normalized.fieldError("commission_rate") ?? normalized.message;
  }
}

const deletingTierId = ref(null);
async function deleteCommissionTier(id) {
  const confirmed = await confirm({
    title: t("settings_page.delete_commission_tier_title"),
    message: t("settings_page.delete_commission_tier_confirm_message"),
    confirmLabel: t("common.delete"),
    variant: "danger",
  });
  if (!confirmed) return;

  deletingTierId.value = id;
  commissionTierError.value = null;
  try {
    await commissionTierService.destroy(id);
    await fetchCommissionTiers();
  } catch (err) {
    commissionTierError.value = normalizeApiError(err, t("settings_page.delete_commission_tier_failed")).message;
  } finally {
    deletingTierId.value = null;
  }
}

function onTabChange(tab) {
  activeTab.value = tab;
  if (tab === "neighborhoods" && neighborhoods.value.length === 0) {
    fetchNeighborhoods();
  }
  if (tab === "commission_tiers" && commissionTiers.value.length === 0) {
    fetchCommissionTiers();
  }
}

onMounted(() => fetchSettings());
</script>

<template>
  <div class="space-y-6">
    <!-- ===== HEADER ===== -->
    <section v-reveal class="glass-card p-5 lg:p-6 relative overflow-hidden">
      <div class="absolute -start-16 -top-16 w-72 h-72 bg-[#D4AF37]/20 dark:bg-[#D4AF37]/25 rounded-full blur-[100px] pointer-events-none"></div>
      <div class="absolute -end-10 -bottom-16 w-72 h-72 bg-[#52733D]/20 dark:bg-[#8cc35a]/15 rounded-full blur-[100px] pointer-events-none"></div>
      <nav class="relative flex items-center justify-start gap-1.5 text-[11px] text-[#9a9d97] dark:text-[#8f938a] mb-2">
        <span>{{ $t("common.home") }}</span>
        <ChevronRight class="rtl:block ltr:hidden text-[9px]" aria-hidden="true" />
        <ChevronLeft class="ltr:block rtl:hidden text-[9px]" aria-hidden="true" />
        <span class="text-[#52733D] dark:text-[#8cc35a] font-bold">{{ $t("settings_page.title") }}</span>
      </nav>
      <div class="relative flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-xl lg:text-2xl font-extrabold mb-1.5 flex items-center gap-2.5">
            <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#3E582E] to-[#52733D] text-white flex items-center justify-center text-base">
              <SlidersHorizontal aria-hidden="true" />
            </span>
            {{ $t("settings_page.title") }}
          </h1>
          <p class="text-[12.5px] text-[#6B6B6B] dark:text-[#aeb1ab] max-w-lg">
            {{ $t("settings_page.subtitle") }}
          </p>
        </div>

        <!-- ===== تبديل التابات ===== -->
        <div class="flex items-center gap-1 bg-[#f4efe5]/70 dark:bg-white/5 rounded-full p-1 flex-wrap">
          <button
            v-for="tab in TABS"
            :key="tab.key"
            type="button"
            @click="onTabChange(tab.key)"
            class="flex items-center gap-1.5 px-4 py-2 rounded-full text-[12px] font-bold transition-colors"
            :class="
              activeTab === tab.key
                ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white shadow-sm'
                : 'text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-white/60 dark:hover:bg-white/5'
            "
          >
            <AppIcon :name="tab.icon" />
            {{ tabLabel(tab) }}
          </button>
        </div>
      </div>
    </section>

    <div v-if="error" v-reveal class="glass-card p-4 text-[12.5px] text-[#D9534F] border border-[#D9534F]/30 flex items-center gap-1.5">
      <CircleAlert aria-hidden="true" />{{ error }}
    </div>
    <div v-if="saveSuccess" v-reveal class="glass-card p-4 text-[12.5px] text-[#28A745] border border-[#28A745]/30 flex items-center gap-1.5">
      <CircleCheck aria-hidden="true" />
      {{ $t("settings_page.settings_saved_success") }}
    </div>

    <div v-if="isLoading" v-reveal class="space-y-3">
      <div v-for="i in 6" :key="i" class="h-12 rounded-lg thumb-loading"></div>
    </div>

    <template v-else>
      <!-- ==================== عام ==================== -->
      <form v-if="activeTab === 'general'" v-reveal @submit.prevent="handleSave" class="glass-card p-6 space-y-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <div v-for="field in GENERAL_FIELDS" :key="field.key">
            <label class="text-[11.5px] font-bold block mb-1.5">{{ fieldLabel(field) }}</label>
            <input
              v-model="settings[field.key]"
              :type="field.type"
              class="w-full bg-[#f4efe5]/60 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-xl px-3.5 py-2.5 text-[12.5px] outline-none focus:border-[#8A6D1F] transition"
            />
          </div>
        </div>

        <button type="submit" :disabled="isSaving" class="btn-fill relative w-full sm:w-auto sm:px-10 bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white text-[12.5px] font-bold py-2.5 rounded-full shadow-md flex items-center justify-center gap-2 disabled:opacity-60">
          <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isSaving" /><Save aria-hidden="true" v-else />
          {{ isSaving ? $t("users_page.saving_ellipsis") : $t("settings_page.save_settings_button") }}
        </button>
      </form>

      <!-- ==================== الصفحات الثابتة ==================== -->
      <form v-else-if="activeTab === 'pages'" v-reveal @submit.prevent="handleSave" class="glass-card p-6 space-y-5">
        <p class="text-[11.5px] text-[#6B6B6B] dark:text-[#a8aaa5] bg-[#EBF1E7] dark:bg-white/5 rounded-lg p-3 flex items-center gap-1.5">
          <Info class="shrink-0" aria-hidden="true" />
          {{ $t("settings_page.pages_notice") }}
        </p>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
          <div v-for="field in PAGE_FIELDS" :key="field.key">
            <label class="text-[11.5px] font-bold block mb-1.5">{{ fieldLabel(field) }}</label>
            <textarea
              v-model="settings[field.key]"
              rows="7"
              class="w-full bg-[#f4efe5]/60 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-xl px-3.5 py-2.5 text-[12.5px] resize-y outline-none focus:border-[#8A6D1F] transition"
            ></textarea>
          </div>
        </div>

        <button type="submit" :disabled="isSaving" class="btn-fill relative w-full sm:w-auto sm:px-10 bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white text-[12.5px] font-bold py-2.5 rounded-full shadow-md flex items-center justify-center gap-2 disabled:opacity-60">
          <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isSaving" /><Save aria-hidden="true" v-else />
          {{ isSaving ? $t("users_page.saving_ellipsis") : $t("settings_page.save_pages_button") }}
        </button>
      </form>

      <!-- ==================== المفاتيح الحسّاسة ==================== -->
      <form v-else-if="activeTab === 'keys'" v-reveal @submit.prevent="handleSave" class="glass-card p-6 space-y-5">
        <p class="text-[11.5px] text-[#8A6D1F] bg-[#D4AF37]/10 border border-[#D4AF37]/30 rounded-lg p-3 flex items-center gap-1.5">
          <TriangleAlert class="shrink-0" aria-hidden="true" />
          {{ $t("settings_page.keys_notice") }}
        </p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div v-for="field in SENSITIVE_FIELDS" :key="field.key">
            <label class="text-[11.5px] font-bold block mb-1.5">
              {{ fieldLabel(field) }}
              <span v-if="settings[field.key]?.is_set" class="status-chip chip-info ms-1">
                {{ $t("settings_page.current_prefix") }}{{ settings[field.key].masked_value }}
              </span>
              <span v-else class="status-chip chip-danger ms-1">{{ $t("settings_page.not_set") }}</span>
            </label>
            <input
              v-model="sensitiveInputs[field.key]"
              type="password"
              :placeholder="$t('settings_page.keep_current_placeholder')"
              class="w-full bg-[#f4efe5]/60 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-xl px-3.5 py-2.5 text-[12.5px] outline-none focus:border-[#8A6D1F] transition"
            />
          </div>
        </div>

        <button type="submit" :disabled="isSaving" class="btn-fill relative w-full sm:w-auto sm:px-10 bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white text-[12.5px] font-bold py-2.5 rounded-full shadow-md flex items-center justify-center gap-2 disabled:opacity-60">
          <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isSaving" /><Save aria-hidden="true" v-else />
          {{ isSaving ? $t("users_page.saving_ellipsis") : $t("settings_page.save_keys_button") }}
        </button>
      </form>

      <!-- ==================== التسجيل ==================== -->
      <section v-else-if="activeTab === 'registration'" v-reveal class="glass-card p-6 space-y-1">
        <div
          v-for="field in REGISTRATION_FIELDS"
          :key="field.key"
          class="flex items-center justify-between py-3.5 border-b border-[#f0ece0] dark:border-white/5 last:border-0"
        >
          <div class="pe-4">
            <p class="text-[12.5px] font-bold">{{ fieldLabel(field) }}</p>
            <p class="text-[11px] text-[#9a9d97] dark:text-[#8f938a] mt-0.5">{{ fieldDesc(field) }}</p>
          </div>
          <button
            type="button"
            @click="toggleBoolean(field.key)"
            :aria-label="fieldLabel(field)"
            class="relative w-11 h-6 rounded-full transition-colors shrink-0"
            :class="settings[field.key] ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D]' : 'bg-[#e7e2d6] dark:bg-white/10'"
          >
            <span class="absolute top-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform" :class="settings[field.key] ? 'translate-x-0.5' : 'translate-x-5'"></span>
          </button>
        </div>

        <button
          type="button"
          @click="handleSave"
          :disabled="isSaving"
          class="btn-fill relative w-full sm:w-auto sm:px-10 mt-4 bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white text-[12.5px] font-bold py-2.5 rounded-full shadow-md flex items-center justify-center gap-2 disabled:opacity-60"
        >
          <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isSaving" /><Save aria-hidden="true" v-else />
          {{ isSaving ? $t("users_page.saving_ellipsis") : $t("settings_page.save_registration_button") }}
        </button>
      </section>

      <!-- ==================== الصيانة ==================== -->
      <section v-else-if="activeTab === 'maintenance'" v-reveal class="glass-card p-6 space-y-4 max-w-xl">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-[12.5px] font-bold">{{ $t("settings_page.enable_maintenance_mode") }}</p>
            <p class="text-[11px] text-[#9a9d97] dark:text-[#8f938a] mt-0.5">
              {{ $t("settings_page.maintenance_mode_desc") }}
            </p>
          </div>
          <button
            type="button"
            @click="toggleBoolean('maintenance_mode_enabled')"
            :aria-label="$t('settings_page.enable_maintenance_mode')"
            class="relative w-11 h-6 rounded-full transition-colors shrink-0"
            :class="settings.maintenance_mode_enabled ? 'bg-[#D9534F]' : 'bg-[#e7e2d6] dark:bg-white/10'"
          >
            <span class="absolute top-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform" :class="settings.maintenance_mode_enabled ? 'translate-x-0.5' : 'translate-x-5'"></span>
          </button>
        </div>

        <div v-if="settings.maintenance_mode_enabled" class="bg-[#D9534F]/10 rounded-lg p-3 text-[11.5px] text-[#D9534F] flex items-center gap-1.5">
          <TriangleAlert class="shrink-0" aria-hidden="true" />
          {{ $t("settings_page.maintenance_active_notice") }}
        </div>

        <div>
          <label class="text-[11.5px] font-bold block mb-1.5">{{ $t("settings_page.maintenance_message_label") }}</label>
          <textarea
            v-model="settings.maintenance_message"
            rows="3"
            :placeholder="$t('settings_page.maintenance_message_placeholder')"
            class="w-full bg-[#f4efe5]/60 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-xl px-3.5 py-2.5 text-[12.5px] resize-none outline-none focus:border-[#8A6D1F] transition"
          ></textarea>
        </div>

        <button
          type="button"
          @click="handleSave"
          :disabled="isSaving"
          class="btn-fill relative w-full sm:w-auto sm:px-10 bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white text-[12.5px] font-bold py-2.5 rounded-full shadow-md flex items-center justify-center gap-2 disabled:opacity-60"
        >
          <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isSaving" /><Save aria-hidden="true" v-else />
          {{ isSaving ? $t("users_page.saving_ellipsis") : $t("settings_page.save_maintenance_button") }}
        </button>
      </section>

      <!-- ==================== الأحياء ==================== -->
      <section v-else-if="activeTab === 'neighborhoods'" v-reveal class="glass-card p-6">
        <div v-if="neighborhoodError" class="text-[11.5px] text-[#D9534F] bg-[#D9534F]/10 rounded-lg p-3 mb-4">{{ neighborhoodError }}</div>

        <div class="flex flex-col sm:flex-row gap-2 mb-5">
          <input
            v-model="newNeighborhoodName"
            type="text"
            :placeholder="$t('settings_page.new_neighborhood_placeholder')"
            @keyup.enter="addNeighborhood"
            class="flex-1 bg-[#f4efe5]/60 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-xl px-3.5 py-2.5 text-[12.5px] outline-none focus:border-[#8A6D1F] transition"
          />
          <input
            v-model="newNeighborhoodNameEn"
            type="text"
            dir="ltr"
            :placeholder="$t('settings_page.new_neighborhood_en_placeholder')"
            @keyup.enter="addNeighborhood"
            class="flex-1 bg-[#f4efe5]/60 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-xl px-3.5 py-2.5 text-[12.5px] outline-none focus:border-[#8A6D1F] transition"
          />
          <button
            type="button"
            @click="addNeighborhood"
            :disabled="isSavingNeighborhood || !newNeighborhoodName.trim()"
            class="btn-fill relative px-5 py-2.5 rounded-full text-[12.5px] font-bold text-white bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] shadow-md disabled:opacity-60 transition shrink-0"
          >
            {{ $t("users_page.add_button") }}
          </button>
        </div>

        <div v-if="isLoadingNeighborhoods" class="space-y-2">
          <div v-for="i in 3" :key="i" class="h-12 rounded-lg thumb-loading"></div>
        </div>
        <div v-else-if="neighborhoods.length === 0" class="text-center py-10">
          <MapPinned class="text-2xl text-[#9a9d97] dark:text-[#8f938a] mb-2" aria-hidden="true" />
          <p class="text-[12.5px] text-[#9a9d97] dark:text-[#8f938a]">{{ $t("settings_page.no_neighborhoods_yet") }}</p>
        </div>
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
          <div
            v-for="n in neighborhoods"
            :key="n.id"
            class="flex items-center justify-between p-3 rounded-xl border border-[#e7e2d6] dark:border-white/10"
          >
            <div v-if="editingId === n.id" class="flex-1 flex flex-col gap-2">
              <input v-model="editingName" type="text" class="flex-1 bg-[#f4efe5]/60 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-lg px-3 py-1.5 text-[12px] outline-none focus:border-[#8A6D1F]" />
              <input
                v-model="editingNameEn"
                type="text"
                dir="ltr"
                :placeholder="$t('settings_page.new_neighborhood_en_placeholder')"
                class="flex-1 bg-[#f4efe5]/60 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-lg px-3 py-1.5 text-[12px] outline-none focus:border-[#8A6D1F]"
              />
              <div class="flex items-center gap-3">
                <button type="button" @click="saveEdit(n.id)" class="text-[11.5px] font-bold text-[#28A745] px-2">{{ $t("users_page.save_action") }}</button>
                <button type="button" @click="editingId = null" class="text-[11.5px] font-bold text-[#9a9d97] dark:text-[#8f938a] px-2">{{ $t("dashboard.cancel") }}</button>
              </div>
            </div>
            <template v-else>
              <div class="min-w-0">
                <span class="text-[12.5px] font-semibold block truncate">{{ n.name }}</span>
                <span class="block text-[10.5px] truncate" :class="n.name_en ? 'text-[#9a9d97] dark:text-[#8f938a]' : 'text-[#c9ab5c] dark:text-[#d4bd7a]'" dir="ltr">
                  {{ n.name_en || $t("settings_page.neighborhood_en_missing") }}
                </span>
              </div>
              <div class="flex items-center gap-3 shrink-0">
                <button type="button" @click="startEdit(n)" class="text-[11.5px] font-bold text-[#3E582E] dark:text-[#a8d19a] hover:underline">{{ $t("common.edit") }}</button>
                <button
                  type="button"
                  @click="deleteNeighborhood(n.id)"
                  :disabled="deletingId === n.id"
                  class="text-[11.5px] font-bold text-[#D9534F] hover:underline disabled:opacity-50"
                >
                  {{ deletingId === n.id ? $t("settings_page.deleting_ellipsis") : $t("common.delete") }}
                </button>
              </div>
            </template>
          </div>
        </div>
      </section>

      <!-- ==================== شرائح العمولة التلقائية ==================== -->
      <section v-else-if="activeTab === 'commission_tiers'" v-reveal class="glass-card p-6">
        <p class="text-[11.5px] text-[#6B6B6B] dark:text-[#a8aaa5] bg-[#EBF1E7] dark:bg-white/5 rounded-lg p-3 mb-4 flex items-center gap-1.5">
          <Info class="shrink-0" aria-hidden="true" />
          {{ $t("settings_page.commission_tiers_notice") }}
        </p>

        <div v-if="commissionTierError" class="text-[11.5px] text-[#D9534F] bg-[#D9534F]/10 rounded-lg p-3 mb-4">{{ commissionTierError }}</div>

        <!-- إضافة شريحة جديدة -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-5">
          <div>
            <label class="text-[10.5px] font-bold block mb-1">{{ $t("settings_page.tier_min_generators") }}</label>
            <input v-model="newTierForm.min_generators_count" type="number" min="0" class="w-full bg-[#f4efe5]/60 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-lg px-3 py-2 text-[12px] outline-none focus:border-[#8A6D1F]" />
          </div>
          <div>
            <label class="text-[10.5px] font-bold block mb-1">{{ $t("settings_page.tier_max_generators") }}</label>
            <input v-model="newTierForm.max_generators_count" type="number" min="0" :placeholder="$t('settings_page.tier_no_limit')" class="w-full bg-[#f4efe5]/60 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-lg px-3 py-2 text-[12px] outline-none focus:border-[#8A6D1F]" />
          </div>
          <div>
            <label class="text-[10.5px] font-bold block mb-1">{{ $t("settings_page.tier_commission_rate") }}</label>
            <input v-model="newTierForm.commission_rate" type="number" min="0" max="100" step="0.01" class="w-full bg-[#f4efe5]/60 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-lg px-3 py-2 text-[12px] outline-none focus:border-[#8A6D1F]" />
          </div>
          <div class="flex items-end">
            <button
              type="button"
              @click="addCommissionTier"
              :disabled="isSavingTier || !newTierForm.min_generators_count || !newTierForm.commission_rate"
              class="btn-fill relative w-full px-4 py-2 rounded-lg text-[12px] font-bold text-white bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] shadow-md disabled:opacity-60 transition"
            >
              {{ $t("users_page.add_button") }}
            </button>
          </div>
        </div>

        <div v-if="isLoadingCommissionTiers" class="space-y-2">
          <div v-for="i in 3" :key="i" class="h-12 rounded-lg thumb-loading"></div>
        </div>
        <div v-else-if="commissionTiers.length === 0" class="text-center py-10">
          <AppIcon name="fa-percent" class="text-2xl text-[#9a9d97] dark:text-[#8f938a] mb-2" />
          <p class="text-[12.5px] text-[#9a9d97] dark:text-[#8f938a]">{{ $t("settings_page.no_commission_tiers_yet") }}</p>
        </div>
        <div v-else class="space-y-2">
          <div
            v-for="tier in commissionTiers"
            :key="tier.id"
            class="flex items-center justify-between p-3 rounded-xl border border-[#e7e2d6] dark:border-white/10"
          >
            <template v-if="editingTierId === tier.id">
              <div class="flex-1 grid grid-cols-2 sm:grid-cols-4 gap-2">
                <input v-model="editingTierForm.min_generators_count" type="number" min="0" class="bg-[#f4efe5]/60 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-lg px-2.5 py-1.5 text-[12px] outline-none focus:border-[#8A6D1F]" />
                <input v-model="editingTierForm.max_generators_count" type="number" min="0" :placeholder="$t('settings_page.tier_no_limit')" class="bg-[#f4efe5]/60 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-lg px-2.5 py-1.5 text-[12px] outline-none focus:border-[#8A6D1F]" />
                <input v-model="editingTierForm.commission_rate" type="number" min="0" max="100" step="0.01" class="bg-[#f4efe5]/60 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-lg px-2.5 py-1.5 text-[12px] outline-none focus:border-[#8A6D1F]" />
                <label class="flex items-center gap-1.5 text-[11.5px]">
                  <input v-model="editingTierForm.is_active" type="checkbox" class="rounded border-[#e7e2d6] dark:border-white/10 text-[#3E582E]" />
                  {{ $t("settings_page.tier_active") }}
                </label>
              </div>
              <div class="flex items-center gap-3 ms-3 shrink-0">
                <button type="button" @click="saveEditTier(tier.id)" class="text-[11.5px] font-bold text-[#28A745] px-2">{{ $t("users_page.save_action") }}</button>
                <button type="button" @click="editingTierId = null" class="text-[11.5px] font-bold text-[#9a9d97] dark:text-[#8f938a] px-2">{{ $t("dashboard.cancel") }}</button>
              </div>
            </template>
            <template v-else>
              <div class="min-w-0 flex items-center gap-3">
                <span class="status-chip" :class="tier.is_active ? 'chip-success' : 'chip-neutral'">{{ tier.is_active ? $t("settings_page.tier_active") : $t("settings_page.tier_inactive") }}</span>
                <span class="text-[12.5px] font-semibold">
                  {{ tier.min_generators_count }} – {{ tier.max_generators_count ?? $t("settings_page.tier_no_limit") }} {{ $t("settings_page.tier_generators_suffix") }}
                </span>
                <span class="text-[12.5px] font-bold text-[#8A6D1F]">{{ tier.commission_rate }}%</span>
              </div>
              <div class="flex items-center gap-3 shrink-0">
                <button type="button" @click="startEditTier(tier)" class="text-[11.5px] font-bold text-[#3E582E] dark:text-[#a8d19a] hover:underline">{{ $t("common.edit") }}</button>
                <button
                  type="button"
                  @click="deleteCommissionTier(tier.id)"
                  :disabled="deletingTierId === tier.id"
                  class="text-[11.5px] font-bold text-[#D9534F] hover:underline disabled:opacity-50"
                >
                  {{ deletingTierId === tier.id ? $t("settings_page.deleting_ellipsis") : $t("common.delete") }}
                </button>
              </div>
            </template>
          </div>
        </div>
      </section>
    </template>
  </div>
</template>