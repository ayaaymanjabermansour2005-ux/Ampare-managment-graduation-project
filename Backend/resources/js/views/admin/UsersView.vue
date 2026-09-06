<script setup>
import { onMounted, ref, reactive, computed } from "vue";
import { useRoute } from "vue-router";
import { useI18n } from "vue-i18n";
import { useUserStore } from "@/stores/user";
import { usePermissions } from "@/composables/usePermissions";
import { useConfirm } from "@/composables/useConfirm";
import { useToastStore } from "@/stores/toast";
import { vReveal } from "@/directives/reveal";
import AppDropdownSelect from "@/components/ui/AppDropdownSelect.vue";
import activityLogService from "@/services/activityLogService";
import userService from "@/services/userService";
import neighborhoodService from "@/services/neighborhoodService";
import { useRolePermissions } from "@/composables/useRolePermissions";
import { normalizeApiError } from "@/utils/normalizeApiError";
import { ArrowLeft, ArrowRight, Check, ChevronLeft, ChevronRight, CircleAlert, CircleCheck, Clock, Copy, Eye, EyeOff, FileSpreadsheet, Info, Key, LoaderCircle, Lock, LockOpen, Pencil, Save, Search, Shuffle, Trash2, UserPlus, UserX, X } from "@lucide/vue";
import AppIcon from "@/components/ui/AppIcon.vue";
import StatCard from "@/components/dashboard/StatCard.vue";


const { t, locale } = useI18n();
const route = useRoute();
const userStore = useUserStore();
const { can } = usePermissions();
const { confirm } = useConfirm();
const toast = useToastStore();

/* ================================================================
 * التبويبات: المستخدمون | الأدوار والصلاحيات
 * ================================================================ */
const activeTab = ref("users"); // "users" | "roles"
const TABS = computed(() => [
  { key: "users", label: t("users_page.title"), icon: "fa-users" },
  { key: "roles", label: t("menu.roles_permissions"), icon: "fa-shield-halved" },
]);

const pageMeta = computed(() =>
  activeTab.value === "roles"
    ? { icon: "fa-shield-halved", title: t("menu.roles_permissions"), subtitle: t("roles_permissions_page.subtitle") }
    : { icon: "fa-users", title: t("users_page.title"), subtitle: t("users_page.subtitle") }
);

/* ---------------- الأدوار والصلاحيات ---------------- */
const {
  roles: rpRoles,
  allPermissions: rpAllPermissions,
  isLoading: rpIsLoading,
  error: rpError,
  isSaving: rpIsSaving,
  fetchData: fetchRolesData,
  saveRole: saveRoleFn,
} = useRolePermissions();

let rolesDataLoaded = false;
async function switchTab(tab) {
  activeTab.value = tab;
  if (tab === "roles" && !rolesDataLoaded) {
    rolesDataLoaded = true;
    await fetchRolesData();
    if (rpRoles.value.length) activeRoleId.value = rpRoles.value[0].id;
  }
}

const activeRoleId = ref(null);
const activeRole = computed(() =>
  rpRoles.value.find((r) => r.id === activeRoleId.value),
);
const rpSaveSuccess = ref(false);

const groupedPermissions = computed(() => {
  const groups = {};
  rpAllPermissions.value.forEach((p) => {
    const section = p.split(".")[0];
    if (!groups[section]) groups[section] = [];
    groups[section].push(p);
  });
  return groups;
});

const activeRolePermCount = computed(() => activeRole.value?.permissions?.length ?? 0);

function togglePermission(permission) {
  if (!activeRole.value) return;
  const idx = activeRole.value.permissions.indexOf(permission);
  if (idx === -1) activeRole.value.permissions.push(permission);
  else activeRole.value.permissions.splice(idx, 1);
}

async function handleSaveRole() {
  rpSaveSuccess.value = false;
  const ok = await saveRoleFn(activeRole.value);
  if (ok) {
    rpSaveSuccess.value = true;
    setTimeout(() => (rpSaveSuccess.value = false), 3000);
  }
}

const searchTerm = ref("");
const roleFilter = ref("");
const statusFilter = ref("");

const STATUS_META = {
  active: "users_page.status_active",
  inactive: "users_page.status_inactive",
  suspended: "users_page.status_suspended",
  pending_review: "users_page.status_pending_review",
};
function statusLabel(status) {
  const key = STATUS_META[status];
  if (!key) return status ?? "—";
  return t(key);
}
const STATUS_PILLS = computed(() => [
  { value: "", label: t("users_page.all_statuses") },
  ...Object.entries(STATUS_META).map(([value, key]) => ({ value, label: t(key) })),
]);

const ROLE_META = {
  admin: "common.role_admin",
  generator_owner: "common.role_owner",
  subscriber: "common.role_subscriber",
  technician: "common.role_technician",
};
function roleLabel(role) {
  const key = ROLE_META[role];
  if (!key) return role ?? "—";
  return t(key);
}
const ROLE_PILLS = computed(() => [
  { value: "", label: t("common.all") },
  ...Object.entries(ROLE_META).map(([value, key]) => ({ value, label: t(key) })),
]);

/* ---------------- فرز الصفحة الحالية (Backend لا يدعم فرز عام، فنفرز محليًا) ---------------- */
const sortBy = ref("name-asc");
function sortUsers(list) {
  const [key, dir] = sortBy.value.split("-");
  return [...list].sort((a, b) => {
    let av, bv;
    if (key === "created") {
      av = new Date(a.created_at).getTime();
      bv = new Date(b.created_at).getTime();
    } else {
      av = (a.name ?? "").toLowerCase();
      bv = (b.name ?? "").toLowerCase();
    }
    if (typeof av === "string") return dir === "asc" ? av.localeCompare(bv) : bv.localeCompare(av);
    return dir === "asc" ? av - bv : bv - av;
  });
}
const sortedUsers = computed(() => sortUsers(userStore.users));
function toggleSort(key) {
  const [curKey, curDir] = sortBy.value.split("-");
  const newDir = curKey === key && curDir === "desc" ? "asc" : "desc";
  sortBy.value = `${key}-${newDir}`;
}
function sortIconClass(key) {
  const [curKey, curDir] = sortBy.value.split("-");
  if (curKey !== key) return "opacity-30";
  return curDir === "desc" ? "opacity-100 text-[#8A6D1F] rotate-180" : "opacity-100 text-[#8A6D1F]";
}

/* ---------------- زر: عرض كل المستخدمين المقفولين (كل النظام، مش الصفحة الحالية) ----------------
 * FIX: كان الزر يفلتر محليًا على userStore.users (الصفحة المحمَّلة حاليًا
 * فقط بحد أقصى per_page)، فيخفي أي مستخدم مقفول موجود بصفحة أخرى غير
 * المعروضة. الباك اند يوفر GET /users/locked (UserLockoutController::
 * lockedIndex) يرجّع فعليًا كل الحسابات المقفولة بالنظام (بدون Pagination —
 * قائمة كاملة واحدة)، فاستبدلنا الفلترة المحلية باستدعاء حقيقي له.
 */
const showLockedOnly = ref(false);
const lockedUsers = ref([]);
const isLoadingLocked = ref(false);
const lockedLoadError = ref(null);

async function fetchLockedUsers() {
  isLoadingLocked.value = true;
  lockedLoadError.value = null;
  try {
    const { data } = await userService.lockedList();
    lockedUsers.value = data.data ?? [];
  } catch (err) {
    lockedLoadError.value = normalizeApiError(err, t("common.unexpected_error_retry")).message;
  } finally {
    isLoadingLocked.value = false;
  }
}

function toggleLockedOnly() {
  showLockedOnly.value = !showLockedOnly.value;
  if (showLockedOnly.value) fetchLockedUsers();
}

const sortedLockedUsers = computed(() => sortUsers(lockedUsers.value));
const displayedUsers = computed(() =>
  showLockedOnly.value ? sortedLockedUsers.value : sortedUsers.value
);

/* ---------------- KPI Cards (نفس منهجية صفحة الأعطال — الإجمالي دقيق، الباقي لهذه الصفحة فقط) ---------------- */
const countOnPage = (role) => userStore.users.filter((u) => u.roles?.[0]?.name === role).length;
const lockedOnPage = computed(() => userStore.users.filter((u) => u.is_locked).length);
const KPI_CARDS = computed(() => [
  { icon: "fa-users", label: t("users_page.total_users"), value: userStore.pagination.total, tone: "primary" },
  { icon: "fa-user-shield", label: roleLabel("admin") + t("common.this_page_suffix"), value: countOnPage("admin"), tone: "info" },
  { icon: "fa-user-gear", label: roleLabel("technician") + t("common.this_page_suffix"), value: countOnPage("technician"), tone: "secondary" },
  { icon: "fa-lock", label: t("users_page.locked_this_page"), value: lockedOnPage.value, tone: "danger" },
]);

/* ---------------- Pagination بأزرار محدودة (نفس منهجية صفحة الأعطال) ---------------- */
const paginationRange = computed(() => {
  const total = userStore.pagination.last_page;
  const current = userStore.pagination.current_page;
  const delta = 1;
  const range = [];
  const withDots = [];
  let last = null;

  for (let i = 1; i <= total; i++) {
    if (i === 1 || i === total || (i >= current - delta && i <= current + delta)) {
      range.push(i);
    }
  }
  for (const i of range) {
    if (last !== null) {
      if (i - last === 2) withDots.push(last + 1);
      else if (i - last > 2) withDots.push("...");
    }
    withDots.push(i);
    last = i;
  }
  return withDots;
});

async function loadUsers(page = 1) {
  try {
    await userStore.fetchUsers({
      page,
      search: searchTerm.value || undefined,
      role: roleFilter.value || undefined,
      status: statusFilter.value || undefined,
    });
  } catch {
    // الخطأ نفسه محفوظ بـ userStore.error/errors ومعروض بالقالب — هون فقط
    // منع الـ unhandled rejection.
  }
}

function onFilterChange() {
  loadUsers();
}

/* بحث حي أثناء الكتابة (Debounce) — بدل الاعتماد بس على ضغطة Enter */
let searchDebounce = null;
function onSearchInput() {
  clearTimeout(searchDebounce);
  searchDebounce = setTimeout(() => loadUsers(1), 400);
}

async function handleDelete(user) {
  const confirmed = await confirm({
    title: t("users_page.delete_user_title"),
    message: t("users_page.delete_user_message", { name: user.name }),
    confirmLabel: t("common.delete"),
    variant: "danger",
  });

  if (confirmed) {
    try {
      await userStore.deleteUser(user.id);
      toast.show({
        type: "success",
        title: t("users_page.deleted_toast_title"),
        message: t("users_page.deleted_message", { name: user.name }),
      });
    } catch (err) {
      // FIX: كانت بدون try/catch — فشل الحذف (مثلاً مستخدم له اشتراكات/مولدات
      // فعّالة) كان يظهر كـ unhandled rejection بصمت بدون أي رسالة للأدمن.
      const normalized = normalizeApiError(err, t("common.unexpected_error_retry"));
      toast.show({
        type: "danger",
        title: t("users_page.delete_user_title"),
        message: normalized.fieldError("user") ?? normalized.message,
      });
    }
  }
}

async function handleUnlock(user) {
  try {
    await userStore.unlockUser(user.id);
    // FIX: بعد فك القفل، لازم يختفي فورًا من لائحة "المقفولين" المحلية
    // (lockedUsers) لو كانت هذه اللائحة معروضة حاليًا — مصدرها منفصل عن
    // userStore.users اللي unlockUser() فوق بيحدّثه.
    lockedUsers.value = lockedUsers.value.filter((u) => u.id !== user.id);
    toast.show({
      type: "success",
      title: t("users_page.unlocked_toast_title"),
      message: t("users_page.unlocked_message", { name: user.name }),
    });
  } catch (err) {
    toast.show({
      type: "danger",
      title: t("users_page.unlocked_toast_title"),
      message: normalizeApiError(err, t("common.unexpected_error_retry")).message,
    });
  }
}

/* ---------------- إرسال رابط إعادة تعيين كلمة المرور (بمبادرة من الأدمن) ---------------- */
const isSendingResetLink = ref(false);

async function handleSendResetLink(user) {
  const confirmed = await confirm({
    title: t("users_page.send_reset_link_title"),
    message: t("users_page.send_reset_link_message", { email: user.email }),
    confirmLabel: t("users_page.send_action"),
    variant: "default",
  });
  if (!confirmed) return;

  isSendingResetLink.value = true;
  try {
    await userService.sendPasswordResetLink(user.id);
    toast.show({
      type: "success",
      title: t("users_page.sent_toast_title"),
      message: t("users_page.sent_message", { email: user.email }),
    });
  } catch (err) {
    toast.show({
      type: "danger",
      title: t("users_page.send_failed_title"),
      message: normalizeApiError(err, t("users_page.try_again_later")).message,
    });
  } finally {
    isSendingResetLink.value = false;
  }
}

/* ---------------- تعيين كلمة سر مؤقتة مباشرة (بديل احتياطي لو البريد معطّل) ---------------- */
const setPasswordModal = ref(null); // user object أو null
const setPasswordForm = reactive({ password: "", password_confirmation: "" });
const setPasswordErrors = ref(null);
const isSettingPassword = ref(false);
const setPasswordSuccess = ref(false);
const showSetPassword = ref(true);

function openSetPassword(user) {
  setPasswordModal.value = user;
  setPasswordErrors.value = null;
  setPasswordSuccess.value = false;
  showSetPassword.value = true;
  generatePassword();
}

function generateRandomPassword() {
  const upper = "ABCDEFGHJKLMNPQRSTUVWXYZ";
  const lower = "abcdefghijkmnpqrstuvwxyz";
  const digits = "23456789";
  const symbols = "!@#$%&*";
  const all = upper + lower + digits + symbols;
  let pwd = upper[Math.floor(Math.random() * upper.length)] + lower[Math.floor(Math.random() * lower.length)] + digits[Math.floor(Math.random() * digits.length)] + symbols[Math.floor(Math.random() * symbols.length)];
  for (let i = 0; i < 8; i++) pwd += all[Math.floor(Math.random() * all.length)];
  return pwd.split("").sort(() => Math.random() - 0.5).join("");
}

function generatePassword() {
  const pwd = generateRandomPassword();
  setPasswordForm.password = pwd;
  setPasswordForm.password_confirmation = pwd;
}

async function copyPassword() {
  try {
    await navigator.clipboard.writeText(setPasswordForm.password);
    toast.show({ type: "success", title: t("users_page.copied_toast_title"), message: t("users_page.password_copied_message") });
  } catch {
    // فشل النسخ (صلاحيات المتصفح) — تجاهل بصمت، كلمة السر ما زالت ظاهرة يدويًا للنسخ.
  }
}

async function handleSetPassword() {
  isSettingPassword.value = true;
  setPasswordErrors.value = null;
  try {
    await userService.setPassword(setPasswordModal.value.id, setPasswordForm.password, setPasswordForm.password_confirmation);
    setPasswordSuccess.value = true;
  } catch (err) {
    const normalized_setPasswordErrors = normalizeApiError(err, null); setPasswordErrors.value = Object.keys(normalized_setPasswordErrors.fieldErrors).length ? normalized_setPasswordErrors.fieldErrors : null;
  } finally {
    isSettingPassword.value = false;
  }
}

/* ---------------- عرض التفاصيل الكاملة ---------------- */
const viewingUser = ref(null);
const isLoadingDetails = ref(false);

async function openView(user) {
  viewingUser.value = user; // بيانات مختصرة فورًا، تنعوّض بالكاملة لما توصل
  isLoadingDetails.value = true;
  userActivity.value = [];
  try {
    viewingUser.value = await userStore.fetchUser(user.id);
  } finally {
    isLoadingDetails.value = false;
  }
  fetchUserActivity(user.id);
}

/* ---------------- سجل نشاط المستخدم (جوا نافذة التفاصيل) ---------------- */
const userActivity = ref([]);
const isLoadingActivity = ref(false);

async function fetchUserActivity(userId) {
  isLoadingActivity.value = true;
  try {
    const { data } = await activityLogService.index({ subject_type: "user", subject_id: userId, per_page: 6 });
    userActivity.value = data.data.data ?? data.data;
  } catch {
    userActivity.value = [];
  } finally {
    isLoadingActivity.value = false;
  }
}

function fmtDateTime(str) {
  if (!str) return "-";
  return str.slice(0, 16).replace("T", " ");
}

const editingUser = ref(null);
const editForm = reactive({ name: "", phone: "", email: "", status: "active" });
const editStatusOptions = computed(() => [
  { value: "active", label: t("users_page.status_active") },
  { value: "inactive", label: t("users_page.status_inactive") },
  { value: "suspended", label: t("users_page.status_suspended") },
  { value: "pending_review", label: t("users_page.status_pending_review") },
]);
const isSaving = ref(false);
const saveErrors = ref(null);

function openEdit(user) {
  editingUser.value = user;
  editForm.name = user.name ?? "";
  editForm.phone = user.phone ?? "";
  editForm.email = user.email ?? "";
  editForm.status = user.status ?? "active";
  saveErrors.value = null;
}

function fieldError(field) {
  return saveErrors.value?.[field]?.[0] ?? null;
}

async function handleSave() {
  isSaving.value = true;
  saveErrors.value = null;
  try {
    await userStore.updateUser(editingUser.value.id, { ...editForm });
    toast.show({
      type: "success",
      title: t("users_page.saved_toast_title"),
      message: t("users_page.user_updated_message"),
    });
    editingUser.value = null;
  } catch (err) {
    const normalized_saveErrors = normalizeApiError(err, null); saveErrors.value = Object.keys(normalized_saveErrors.fieldErrors).length ? normalized_saveErrors.fieldErrors : null;
  } finally {
    isSaving.value = false;
  }
}

/* ---------------- تصدير Excel ----------------
 * Backend export حقيقي لكل السجلات المطابقة للفلاتر الحالية (بحث/صلاحية/حالة)،
 * مو فقط الصفحة المحمّلة حاليًا.
 */
const usersExportUrl = computed(() =>
  userService.exportUrl({
    search: searchTerm.value,
    role: roleFilter.value,
    status: statusFilter.value,
  })
);

const addUserModalOpen = ref(false);
const addUserStep = ref("choice"); // "choice" | "owner" | "subscriber" | "technician"

const ADD_USER_OPTIONS = computed(() => [
  { type: "owner", icon: "fa-user-tie", label: t("users_page.option_owner_label"), desc: t("users_page.option_owner_desc"), c1: "#8A6D1F", c2: "#D4AF37" },
  { type: "subscriber", icon: "fa-user", label: t("common.role_subscriber"), desc: t("users_page.option_subscriber_desc"), c1: "#3E582E", c2: "#52733D" },
  { type: "technician", icon: "fa-screwdriver-wrench", label: t("common.role_technician"), desc: t("users_page.option_technician_desc"), c1: "#0f6c7d", c2: "#17A2B8" },
]);

const ADD_USER_STEP_TITLES = computed(() => ({
  choice: t("users_page.add_user_title"),
  owner: t("users_page.add_owner_title"),
  subscriber: t("users_page.add_subscriber_title"),
  technician: t("users_page.add_technician_title"),
}));

function openAddUserMenu() {
  addUserModalOpen.value = true;
  addUserStep.value = "choice";
}
function selectAddUserType(type) {
  addUserStep.value = type;
  if (type === "technician" && owners.value.length === 0) fetchOwners();
  if (type === "subscriber") fetchNeighborhoods();
}
function backToAddUserChoice() {
  addUserStep.value = "choice";
}
function closeAddUserForms() {
  addUserModalOpen.value = false;
}

/* ---------------- فورم إضافة صاحب مولد ---------------- */
const ownerForm = reactive({ name: "", email: "", phone: "", password: "", password_confirmation: "" });
const showOwnerPassword = ref(false);
function generateOwnerPassword() {
  const pwd = generateRandomPassword();
  ownerForm.password = pwd;
  ownerForm.password_confirmation = pwd;
  showOwnerPassword.value = true;
}
const isCreatingOwner = ref(false);
const createOwnerErrors = ref(null);

async function handleCreateOwner() {
  isCreatingOwner.value = true;
  createOwnerErrors.value = null;
  try {
    await userService.createGeneratorOwner({ ...ownerForm });
    toast.show({ type: "success", title: t("users_page.created_toast_title"), message: t("users_page.owner_created_message") });
    Object.assign(ownerForm, { name: "", email: "", phone: "", password: "", password_confirmation: "" });
    showOwnerPassword.value = false;
    closeAddUserForms();
    loadUsers();
  } catch (err) {
    const normalized_createOwnerErrors = normalizeApiError(err, null); createOwnerErrors.value = Object.keys(normalized_createOwnerErrors.fieldErrors).length ? normalized_createOwnerErrors.fieldErrors : null;
  } finally {
    isCreatingOwner.value = false;
  }
}

/* ---------------- فورم إضافة مشترك ---------------- */
const subscriberForm = reactive({ name: "", email: "", phone: "", password: "", password_confirmation: "", address: "", neighborhood_id: "" });
const showSubscriberPassword = ref(false);
function generateSubscriberPassword() {
  const pwd = generateRandomPassword();
  subscriberForm.password = pwd;
  subscriberForm.password_confirmation = pwd;
  showSubscriberPassword.value = true;
}
const isCreatingSubscriber = ref(false);
const createSubscriberErrors = ref(null);

// FIX: نموذج "إضافة مشترك" كان بدون حي (neighborhood_id) رغم إن
// AdminCreateSubscriberRequest بيقبله (اختياري) — بيُستخدَم بلوحة تحكم
// الحي (neighborhoods/dashboard) وبمرشِّح الحي بصفحة المشتركين.
const neighborhoods = ref([]);
const isLoadingNeighborhoods = ref(false);
const neighborhoodOptions = computed(() => neighborhoods.value.map((n) => ({ value: n.id, label: n.name })));
async function fetchNeighborhoods() {
  if (neighborhoods.value.length) return;
  isLoadingNeighborhoods.value = true;
  try {
    const { data } = await neighborhoodService.list();
    neighborhoods.value = data.data ?? [];
  } catch {
    neighborhoods.value = [];
  } finally {
    isLoadingNeighborhoods.value = false;
  }
}

async function handleCreateSubscriber() {
  isCreatingSubscriber.value = true;
  createSubscriberErrors.value = null;
  try {
    await userService.createSubscriber({ ...subscriberForm, neighborhood_id: subscriberForm.neighborhood_id || undefined });
    toast.show({ type: "success", title: t("users_page.created_toast_title"), message: t("users_page.subscriber_created_message") });
    Object.assign(subscriberForm, { name: "", email: "", phone: "", password: "", password_confirmation: "", address: "", neighborhood_id: "" });
    showSubscriberPassword.value = false;
    closeAddUserForms();
    loadUsers();
  } catch (err) {
    const normalized_createSubscriberErrors = normalizeApiError(err, null); createSubscriberErrors.value = Object.keys(normalized_createSubscriberErrors.fieldErrors).length ? normalized_createSubscriberErrors.fieldErrors : null;
  } finally {
    isCreatingSubscriber.value = false;
  }
}

/* ---------------- فورم إضافة فني (نيابةً عن مالك) ---------------- */
const owners = ref([]);
const ownerSelectOptions = computed(() => owners.value.map((o) => ({ value: o.id, label: `${o.name} — ${o.email}` })));
const isLoadingOwners = ref(false);
async function fetchOwners() {
  isLoadingOwners.value = true;
  try {
    const { data } = await userService.list({ role: "generator_owner", per_page: 200 });
    const list = data.data.data ?? data.data;
    owners.value = [...list].sort((a, b) => a.name.localeCompare(b.name, locale.value === "ar" ? "ar" : "en"));
  } finally {
    isLoadingOwners.value = false;
  }
}

const technicianForm = reactive({ owner_id: "", name: "", email: "", password: "", password_confirmation: "", notes: "" });
const showTechnicianPassword = ref(false);
function generateTechnicianPassword() {
  const pwd = generateRandomPassword();
  technicianForm.password = pwd;
  technicianForm.password_confirmation = pwd;
  showTechnicianPassword.value = true;
}
const isCreatingTechnician = ref(false);
const createTechnicianErrors = ref(null);

async function handleCreateTechnician() {
  isCreatingTechnician.value = true;
  createTechnicianErrors.value = null;
  try {
    await userService.createTechnician({ ...technicianForm });
    toast.show({ type: "success", title: t("users_page.created_toast_title"), message: t("users_page.technician_created_message") });
    Object.assign(technicianForm, { owner_id: "", name: "", email: "", password: "", password_confirmation: "", notes: "" });
    showTechnicianPassword.value = false;
    closeAddUserForms();
    loadUsers();
  } catch (err) {
    const normalized_createTechnicianErrors = normalizeApiError(err, null); createTechnicianErrors.value = Object.keys(normalized_createTechnicianErrors.fieldErrors).length ? normalized_createTechnicianErrors.fieldErrors : null;
  } finally {
    isCreatingTechnician.value = false;
  }
}

onMounted(() => {
  if (route.query.role) roleFilter.value = String(route.query.role);
  if (route.query.status) statusFilter.value = String(route.query.status);
  loadUsers();
  if (route.query.tab === "roles") switchTab("roles");
});
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
        <span class="text-[#52733D] dark:text-[#8cc35a] font-bold">{{ pageMeta.title }}</span>
      </nav>
      <div class="relative flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-xl lg:text-2xl font-extrabold mb-1.5 flex items-center gap-2.5">
            <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#3E582E] to-[#52733D] text-white flex items-center justify-center text-base">
              <AppIcon :name="pageMeta.icon" />
            </span>
            {{ pageMeta.title }}
          </h1>
          <p class="text-[12.5px] text-[#6B6B6B] dark:text-[#aeb1ab] max-w-lg">
            {{ pageMeta.subtitle }}
          </p>
        </div>

        <!-- ===== تبديل الواجهات (المستخدمون / الأدوار والصلاحيات) ===== -->
        <div class="flex items-center gap-1 bg-[#f4efe5]/70 dark:bg-white/5 rounded-full p-1 shrink-0 print-hidden">
          <button
            v-for="tab in TABS"
            :key="tab.key"
            type="button"
            @click="switchTab(tab.key)"
            class="relative flex items-center gap-1.5 px-4 py-2 rounded-full text-[12px] font-bold transition-colors"
            :class="
              activeTab === tab.key
                ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white shadow-sm'
                : 'text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-white/60 dark:hover:bg-white/5'
            "
          >
            <AppIcon :name="tab.icon" />
            {{ tab.label }}
          </button>
        </div>
      </div>

      <div v-if="activeTab === 'users'" class="relative flex flex-wrap gap-2.5 mt-3">
        <button
          v-if="can('users.create')"
          type="button"
          @click="openAddUserMenu"
          class="btn-fill relative text-[12.5px] font-bold px-4 py-2.5 rounded-full shadow-md text-white bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] flex items-center gap-2"
        >
          <UserPlus aria-hidden="true" />
          {{ $t("users_page.add_button") }}
        </button>
        <a
          :href="usersExportUrl"
          target="_blank"
          class="btn-fill relative text-[12.5px] font-bold px-4 py-2.5 rounded-full border border-[#D4AF37]/50 text-[#3E582E] dark:text-[#F4E0A5] hover:text-white dark:hover:text-white hover:border-transparent transition-colors duration-300 flex items-center gap-2"
        >
          <FileSpreadsheet aria-hidden="true" />
          {{ $t("users_page.export_csv") }}
        </a>
      </div>
    </section>

    <!-- ===== KPI CARDS ===== -->
    <section v-if="activeTab === 'users'" v-reveal>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3.5">
        <StatCard
          v-for="c in KPI_CARDS" :key="c.label"
          :label="c.label" :value="c.value" :icon="c.icon" :tone="c.tone"
        />
      </div>
    </section>

    <!-- ===== TOOLBAR ===== -->
    <section v-if="activeTab === 'users'" v-reveal class="glass-card p-4">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="relative">
          <Search class="absolute top-1/2 -translate-y-1/2 start-3 text-[#9a9d97] dark:text-[#8f938a] text-[10px]" aria-hidden="true" />
          <input
            v-model="searchTerm"
            type="text"
            @input="onSearchInput"
            @keyup.enter="loadUsers()"
            :placeholder="$t('users_page.search_placeholder')"
            class="bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-full py-2 ps-8 pe-3 text-[11.5px] outline-none focus:border-[#8A6D1F] w-56 md:w-72"
          />
        </div>
        <div class="flex items-center gap-2 flex-wrap">
          <div class="flex items-center gap-1 bg-[#f4efe5]/70 dark:bg-white/5 rounded-full p-1 flex-wrap">
            <button
              v-for="pill in ROLE_PILLS"
              :key="pill.value"
              type="button"
              @click="roleFilter = pill.value; onFilterChange()"
              class="px-3 py-1.5 rounded-full text-[11px] font-bold transition-colors"
              :class="roleFilter === pill.value ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white' : 'text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-white/60 dark:hover:bg-white/5'"
            >{{ pill.label }}</button>
          </div>
          <div class="w-px h-6 bg-[#e0dccf] dark:bg-white/10"></div>
          <div class="flex items-center gap-1 bg-[#f4efe5]/70 dark:bg-white/5 rounded-full p-1 flex-wrap">
            <button
              v-for="pill in STATUS_PILLS"
              :key="pill.value"
              type="button"
              @click="statusFilter = pill.value; onFilterChange()"
              class="px-3 py-1.5 rounded-full text-[11px] font-bold transition-colors"
              :class="statusFilter === pill.value ? 'bg-gradient-to-l from-[#8A6D1F] to-[#D4AF37] text-white' : 'text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-white/60 dark:hover:bg-white/5'"
            >{{ pill.label }}</button>
          </div>
          <div class="w-px h-6 bg-[#e0dccf] dark:bg-white/10"></div>
          <button
            type="button"
            @click="toggleLockedOnly"
            class="px-3 py-1.5 rounded-full text-[11px] font-bold transition-colors flex items-center gap-1.5"
            :class="showLockedOnly ? 'bg-gradient-to-l from-[#D9534F] to-[#8A2E2A] text-white' : 'bg-[#f4efe5]/70 dark:bg-white/5 text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-white/60 dark:hover:bg-white/5'"
          >
            <LoaderCircle class="animate-spin text-[10px]" aria-hidden="true" v-if="showLockedOnly && isLoadingLocked" /><Lock class="text-[10px]" aria-hidden="true" v-else /> {{ $t("users_page.locked_only") }}
          </button>
        </div>
      </div>
    </section>

    <!-- ===== LIST ===== -->
    <section v-if="activeTab === 'users'" v-reveal class="glass-card p-4 overflow-hidden">
      <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
        <h3 class="text-[13.5px] font-bold">{{ $t("users_page.users_list") }}</h3>
        <div class="flex items-center gap-1">
          <span class="text-[10.5px] text-[#9a9d97] dark:text-[#8f938a] me-1">{{ $t("users_page.sort_label") }}</span>
          <button type="button" @click="toggleSort('name')" class="px-2.5 py-1 rounded-full text-[10.5px] font-bold text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-[#f4efe5]/60 dark:hover:bg-white/5 flex items-center gap-1">
            {{ $t("dashboard.name") }} <AppIcon :name="sortIconClass('name')" class="text-[9px] transition-all" />
          </button>
          <button type="button" @click="toggleSort('created')" class="px-2.5 py-1 rounded-full text-[10.5px] font-bold text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-[#f4efe5]/60 dark:hover:bg-white/5 flex items-center gap-1">
            {{ $t("users_page.joined_label") }} <AppIcon :name="sortIconClass('created')" class="text-[9px] transition-all" />
          </button>
        </div>
      </div>

      <div v-if="showLockedOnly ? isLoadingLocked : userStore.isLoading" class="space-y-2">
        <div v-for="i in 6" :key="i" class="h-16 rounded-lg thumb-loading"></div>
      </div>

      <!-- FIX: ما كان في حالة خطأ منفصلة أصلًا — فشل التحميل (صلاحيات/شبكة/500)
           كان يظهر بصمت كـ "لا يوجد مستخدمون مطابقون" بدل رسالة خطأ حقيقية. -->
      <div v-else-if="showLockedOnly ? lockedLoadError : userStore.error" class="text-center py-8 text-[12px] text-[#D9534F]">{{ showLockedOnly ? lockedLoadError : userStore.error }}</div>

      <div v-else-if="!showLockedOnly && !userStore.users.length" class="text-center py-12">
        <UserX class="text-2xl text-[#9a9d97] dark:text-[#8f938a] mb-2" aria-hidden="true" />
        <p class="text-[12.5px] text-[#9a9d97] dark:text-[#8f938a]">{{ $t("users_page.no_matching_users") }}</p>
      </div>

      <div v-else-if="displayedUsers.length === 0" class="text-center py-12">
        <LockOpen class="text-2xl text-[#9a9d97] dark:text-[#8f938a] mb-2" aria-hidden="true" />
        <p class="text-[12.5px] text-[#9a9d97] dark:text-[#8f938a]">{{ $t("users_page.no_locked_users") }}</p>
      </div>

      <div v-else class="divide-y divide-[#f0ece0] dark:divide-white/5">
        <div
          v-for="user in displayedUsers"
          :key="user.id"
          class="flex items-center gap-3.5 py-3.5 px-1.5"
        >
          <img
            v-if="user.avatar_url"
            :src="user.avatar_url"
            :alt="user.name"
            class="w-9 h-9 rounded-full object-cover shrink-0"
          />
          <div v-else class="w-9 h-9 rounded-full bg-gradient-to-br from-[#3E582E] to-[#52733D] flex items-center justify-center shrink-0 text-white text-[12px] font-bold">
            {{ user.name?.charAt(0) ?? "?" }}
          </div>

          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
              <p class="text-[12.5px] font-bold truncate">{{ user.name }}</p>
              <span class="status-chip shrink-0 chip-info">{{ roleLabel(user.roles?.[0]?.name) }}</span>
              <span
                class="status-chip shrink-0"
                :class="user.is_locked ? 'chip-danger' : 'chip-success'"
              >
                {{ user.is_locked ? $t("users_page.locked_label") : user.status_label }}
              </span>
            </div>
            <p class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-0.5 truncate">{{ user.email }}</p>
            <p v-if="user.is_locked && user.locked_until" class="text-[10px] text-[#D9534F] mt-0.5">
              <Clock class="text-[9px]" aria-hidden="true" />
              {{ $t("users_page.auto_unlocks_line", { date: fmtDateTime(user.locked_until) }) }}
            </p>
          </div>

          <div class="flex items-center gap-1 shrink-0">
            <button
              type="button"
              @click="openView(user)"
              class="w-8 h-8 rounded-full flex items-center justify-center text-[#9a9d97] dark:text-[#8f938a] hover:text-[#17A2B8] hover:bg-[#17A2B8]/10 transition"
              :title="$t('users_page.view_details')"
              :aria-label="$t('users_page.view_details')"
            >
              <Eye class="text-[12px]" aria-hidden="true" />
            </button>
            <button
              v-if="can('users.update')"
              type="button"
              @click="openEdit(user)"
              class="w-8 h-8 rounded-full flex items-center justify-center text-[#9a9d97] dark:text-[#8f938a] hover:text-[#8A6D1F] hover:bg-[#8A6D1F]/10 transition"
              :title="$t('common.edit')"
              :aria-label="$t('common.edit')"
            >
              <Pencil class="text-[11px]" aria-hidden="true" />
            </button>
            <button
              v-if="can('users.unlock') && user.is_locked"
              type="button"
              @click="handleUnlock(user)"
              class="w-8 h-8 rounded-full flex items-center justify-center text-[#9a9d97] dark:text-[#8f938a] hover:text-[#17A2B8] hover:bg-[#17A2B8]/10 transition"
              :title="$t('users_page.unlock_action')"
              :aria-label="$t('users_page.unlock_action')"
            >
              <LockOpen class="text-[12px]" aria-hidden="true" />
            </button>
            <button
              v-if="can('users.delete')"
              type="button"
              @click="handleDelete(user)"
              class="w-8 h-8 rounded-full flex items-center justify-center text-[#9a9d97] dark:text-[#8f938a] hover:text-[#D9534F] hover:bg-[#D9534F]/10 transition"
              :title="$t('common.delete')"
              :aria-label="$t('common.delete')"
            >
              <Trash2 class="text-[12px]" aria-hidden="true" />
            </button>
          </div>
        </div>
      </div>

      <!-- Pagination بأزرار محدودة -->
      <!-- FIX: GET /users/locked (lockedList) يرجّع قائمة كاملة غير مُصفَّحة —
           أزرار الصفحات هون مرتبطة حصرًا بترقيم userStore (القائمة العادية)
           ولا معنى لعرضها في وضع "المقفولين فقط". -->
      <div v-if="!showLockedOnly && userStore.pagination.last_page > 1" class="flex items-center justify-between mt-3.5">
        <span class="text-[11px] text-[#9a9d97] dark:text-[#8f938a]">
          {{ $t("users_page.pagination_text", { current: userStore.pagination.current_page, last: userStore.pagination.last_page, total: userStore.pagination.total }) }}
        </span>
        <div class="flex items-center gap-1">
          <button :aria-label="$t('common.previous_page')"
            type="button"
            :disabled="userStore.pagination.current_page <= 1"
            @click="loadUsers(userStore.pagination.current_page - 1)"
            class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40"
          >
            <ChevronRight class="rtl:block ltr:hidden text-[10px]" aria-hidden="true" />
            <ChevronLeft class="ltr:block rtl:hidden text-[10px]" aria-hidden="true" />
          </button>

          <template v-for="(page, i) in paginationRange" :key="i">
            <span v-if="page === '...'" class="w-7 h-7 flex items-center justify-center text-[11px] text-[#9a9d97] dark:text-[#8f938a]">…</span>
            <button
              v-else
              type="button"
              @click="loadUsers(page)"
              class="w-7 h-7 rounded-lg text-[11px] font-bold transition-colors"
              :class="
                page === userStore.pagination.current_page
                  ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white'
                  : 'hover:bg-[#EBF1E7] dark:hover:bg-white/5 text-[#6B6B6B] dark:text-[#a8aaa5]'
              "
            >
              {{ page }}
            </button>
          </template>

          <button :aria-label="$t('common.next_page')"
            type="button"
            :disabled="userStore.pagination.current_page >= userStore.pagination.last_page"
            @click="loadUsers(userStore.pagination.current_page + 1)"
            class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40"
          >
            <ChevronLeft class="rtl:block ltr:hidden text-[10px]" aria-hidden="true" />
            <ChevronRight class="ltr:block rtl:hidden text-[10px]" aria-hidden="true" />
          </button>
        </div>
      </div>
    </section>

    <!-- ===== ROLES & PERMISSIONS TAB ===== -->
    <template v-if="activeTab === 'roles'">
      <div v-if="rpError" v-reveal class="glass-card p-4 text-[12.5px] text-[#D9534F] border border-[#D9534F]/30">
        <CircleAlert class="me-1.5" aria-hidden="true" />{{ rpError }}
      </div>
      <div v-if="rpSaveSuccess" v-reveal class="glass-card p-4 text-[12.5px] text-[#28A745] border border-[#28A745]/30">
        <CircleCheck class="me-1.5" aria-hidden="true" />
        {{ $t("roles_permissions_page.saved") }}
      </div>

      <div v-if="rpIsLoading" v-reveal class="glass-card h-64 thumb-loading"></div>

      <template v-else>
        <!-- ===== ROLE SUB-TABS ===== -->
        <section v-reveal class="glass-card p-3">
          <div class="flex gap-1.5 overflow-x-auto">
            <button
              v-for="role in rpRoles"
              :key="role.id"
              type="button"
              @click="activeRoleId = role.id"
              class="shrink-0 px-4 py-2 rounded-full text-[12.5px] font-bold transition-colors"
              :class="
                activeRoleId === role.id
                  ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white shadow-md'
                  : 'text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-[#EBF1E7] dark:hover:bg-white/5'
              "
            >
              {{ roleLabel(role.name) }}
            </button>
          </div>
        </section>

        <!-- ===== ADMIN LOCKED NOTICE ===== -->
        <section
          v-if="activeRole?.name === 'admin'"
          v-reveal
          class="glass-card p-4 text-[12.5px] text-[#8A6D1F] dark:text-[#D4AF37] border border-[#D4AF37]/40"
        >
          <Lock class="me-1.5" aria-hidden="true" />
          {{ $t("roles_permissions_page.admin_locked_notice") }}
        </section>

        <!-- ===== PERMISSIONS PANEL ===== -->
        <section v-else-if="activeRole" v-reveal class="glass-card p-5 space-y-5">
          <div class="flex items-center justify-between flex-wrap gap-2">
            <h3 class="text-[13.5px] font-bold">{{ $t("roles_permissions_page.role_permissions_title") }}</h3>
            <span class="status-chip chip-info">
              <Key aria-hidden="true" />
              {{ $t("roles_permissions_page.enabled_count", { count: activeRolePermCount }) }}
            </span>
          </div>

          <div v-for="(perms, section) in groupedPermissions" :key="section" class="border-t border-[#f0ece0] dark:border-white/5 pt-4 first:border-t-0 first:pt-0">
            <h4 class="text-[11px] font-extrabold text-[#8A6D1F] dark:text-[#D4AF37] mb-2.5 uppercase tracking-wide">
              {{ section }}
            </h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
              <label
                v-for="perm in perms"
                :key="perm"
                class="flex items-center gap-2 text-[12px] rounded-lg px-2.5 py-2 cursor-pointer transition-colors"
                :class="
                  activeRole.permissions.includes(perm)
                    ? 'bg-[#EBF1E7] dark:bg-white/5 text-[#3E582E] dark:text-[#a8d19a] font-semibold'
                    : 'text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-[#f4efe5]/60 dark:hover:bg-white/5'
                "
              >
                <input
                  type="checkbox"
                  :checked="activeRole.permissions.includes(perm)"
                  @change="togglePermission(perm)"
                  class="rounded border-[#e7e2d6] dark:border-white/10 text-[#3E582E] focus:ring-[#3E582E]/30"
                />
                {{ perm }}
              </label>
            </div>
          </div>

          <button
            type="button"
            :disabled="rpIsSaving"
            @click="handleSaveRole"
            class="btn-fill relative w-full bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white text-[12.5px] font-bold py-2.5 rounded-full shadow-md flex items-center justify-center gap-2 disabled:opacity-60"
          >
            <LoaderCircle class="animate-spin" aria-hidden="true" v-if="rpIsSaving" /><Save aria-hidden="true" v-else />
            {{ rpIsSaving ? $t("roles_permissions_page.saving") : $t("roles_permissions_page.save") }}
          </button>
        </section>
      </template>
    </template>

    <!-- ===================== نافذة عرض التفاصيل ===================== -->
    <Teleport to="body">
      <div v-if="viewingUser" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" @click.self="viewingUser = null">
        <div class="glass-card !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-lg max-h-[88vh] flex flex-col shadow-2xl">
          <div class="flex items-center justify-between px-5 py-4 border-b border-[#eee8da] dark:border-white/10 shrink-0">
            <h3 class="text-[15px] font-extrabold flex items-center gap-2"><Info class="text-[#8A6D1F]" aria-hidden="true" /> {{ $t("users_page.user_details_title") }}</h3>
            <button :aria-label="$t('common.close')" type="button" @click="viewingUser = null" class="icon-btn !w-8 !h-8"><X aria-hidden="true" /></button>
          </div>

          <div class="p-5 overflow-y-auto space-y-4">
            <div v-if="isLoadingDetails" class="space-y-2">
              <div v-for="i in 4" :key="i" class="h-10 rounded-lg thumb-loading"></div>
            </div>
            <template v-else>
              <div class="glass-card p-4 text-center">
                <img
                  v-if="viewingUser.avatar_url"
                  :src="viewingUser.avatar_url"
                  :alt="viewingUser.name"
                  class="w-16 h-16 rounded-full object-cover mx-auto mb-3"
                />
                <div v-else class="w-16 h-16 rounded-full flex items-center justify-center text-white text-xl font-bold mx-auto mb-3 bg-gradient-to-br from-[#3E582E] to-[#52733D]">
                  {{ viewingUser.name?.charAt(0) ?? "?" }}
                </div>
                <h4 class="text-[14.5px] font-extrabold mb-1.5">{{ viewingUser.name }}</h4>
                <div class="flex items-center justify-center gap-2 flex-wrap">
                  <span class="status-chip chip-info">{{ roleLabel(viewingUser.roles?.[0]?.name) }}</span>
                  <span class="status-chip" :class="viewingUser.is_locked ? 'chip-danger' : 'chip-success'">
                    {{ viewingUser.is_locked ? $t("users_page.locked_label") : viewingUser.status_label }}
                  </span>
                  <span v-if="viewingUser.email_verified === false" class="status-chip chip-warning">{{ $t("users_page.email_unverified") }}</span>
                </div>
              </div>

              <div class="glass-card p-4 space-y-2.5 text-[12px]">
                <div class="flex justify-between"><span class="text-[#9a9d97] dark:text-[#8f938a]">{{ $t("users_page.email_label") }}</span><b class="truncate max-w-[12rem]">{{ viewingUser.email }}</b></div>
                <div class="flex justify-between"><span class="text-[#9a9d97] dark:text-[#8f938a]">{{ $t("users_page.phone_label") }}</span><b dir="ltr">{{ viewingUser.phone ?? "-" }}</b></div>
                <div class="flex justify-between"><span class="text-[#9a9d97] dark:text-[#8f938a]">{{ $t("users_page.whatsapp_label") }}</span><b dir="ltr">{{ viewingUser.whatsapp ?? "-" }}</b></div>
                <div class="flex justify-between"><span class="text-[#9a9d97] dark:text-[#8f938a]">{{ $t("users_page.birth_date_label") }}</span><b>{{ viewingUser.birth_date ?? "-" }}</b></div>
                <div class="flex justify-between"><span class="text-[#9a9d97] dark:text-[#8f938a]">{{ $t("users_page.address_label") }}</span><b class="truncate max-w-[12rem]">{{ viewingUser.address ?? "-" }}</b></div>
                <div class="flex justify-between"><span class="text-[#9a9d97] dark:text-[#8f938a]">{{ $t("users_page.joined_label") }}</span><b>{{ fmtDateTime(viewingUser.created_at) }}</b></div>
              </div>

              <div v-if="viewingUser.bio" class="glass-card p-4">
                <h5 class="text-[12px] font-bold mb-1.5">{{ $t("users_page.bio_label") }}</h5>
                <p class="text-[11.5px] text-[#6B6B6B] dark:text-[#a8aaa5]">{{ viewingUser.bio }}</p>
              </div>

              <div v-if="viewingUser.outstanding_balance_ils !== undefined || viewingUser.generators_count !== undefined" class="grid grid-cols-2 gap-2.5">
                <div v-if="viewingUser.generators_count !== undefined" class="glass-card p-3 text-center">
                  <div class="text-base font-extrabold">{{ viewingUser.generators_count ?? 0 }}</div>
                  <div class="text-[10px] text-[#9a9d97] dark:text-[#8f938a]">{{ $t("users_page.generators_label") }}</div>
                </div>
                <div v-if="viewingUser.outstanding_balance_ils !== undefined" class="glass-card p-3 text-center">
                  <div class="text-sm font-extrabold">₪ {{ Number(viewingUser.outstanding_balance_ils ?? 0).toLocaleString() }}</div>
                  <div class="text-[10px] text-[#9a9d97] dark:text-[#8f938a]">{{ $t("users_page.outstanding_balance_label") }}</div>
                </div>
              </div>

              <div v-if="viewingUser.is_locked" class="glass-card p-4 text-[12px] space-y-2 !border-[#D9534F]/30">
                <div class="flex justify-between"><span class="text-[#9a9d97] dark:text-[#8f938a]">{{ $t("users_page.auto_unlocks_label") }}</span><b class="text-[#D9534F]">{{ fmtDateTime(viewingUser.locked_until) }}</b></div>
                <div class="flex justify-between"><span class="text-[#9a9d97] dark:text-[#8f938a]">{{ $t("users_page.lockout_count_label") }}</span><b>{{ viewingUser.lockout_count ?? 0 }}</b></div>
              </div>

              <div class="glass-card p-4 text-start">
                <h5 class="text-[12px] font-bold mb-2.5">{{ $t("users_page.recent_activity") }}</h5>
                <div v-if="isLoadingActivity" class="space-y-2">
                  <div v-for="i in 3" :key="i" class="h-8 rounded-lg thumb-loading"></div>
                </div>
                <p v-else-if="userActivity.length === 0" class="text-[11px] text-[#9a9d97] dark:text-[#8f938a] text-center py-4">{{ $t("users_page.no_activity") }}</p>
                <div v-else class="space-y-2">
                  <div v-for="log in userActivity" :key="log.id" class="text-[11.5px] pb-2 border-b border-[#f0ece0] dark:border-white/5 last:border-0 last:pb-0">
                    <p class="font-semibold">
                      {{ log.causer?.name ?? $t("users_page.system_label") }}
                      <span class="font-normal text-[#6B6B6B] dark:text-[#a8aaa5]">— {{ log.description }}</span>
                    </p>
                    <span class="text-[10px] text-[#9a9d97] dark:text-[#8f938a]">{{ fmtDateTime(log.created_at) }}</span>
                  </div>
                </div>
              </div>
            </template>
          </div>

          <div class="flex items-center justify-between gap-2.5 px-5 py-4 border-t border-[#eee8da] dark:border-white/10 shrink-0 flex-wrap">
            <!-- إجراءات استرجاع الحساب — أيقونات مدمجة مع تلميح نصي، لتفادي تكدّس النصوص الطويلة -->
            <div class="flex items-center gap-1.5">
              <button
                v-if="can('users.update')"
                type="button"
                :disabled="isSendingResetLink"
                @click="handleSendResetLink(viewingUser)"
                class="w-9 h-9 rounded-full flex items-center justify-center text-[#8A6D1F] border border-[#8A6D1F]/40 hover:bg-[#8A6D1F]/10 disabled:opacity-50 shrink-0"
                :title="$t('users_page.send_reset_link_hint')"
              >
                <LoaderCircle class="text-[13px] animate-spin" aria-hidden="true" v-if="isSendingResetLink" /><Key class="text-[13px]" aria-hidden="true" v-else />
              </button>
              <button
                v-if="can('users.update')"
                type="button"
                @click="openSetPassword(viewingUser); viewingUser = null"
                class="w-9 h-9 rounded-full flex items-center justify-center text-[#D9534F] border border-[#D9534F]/40 hover:bg-[#D9534F]/10 shrink-0"
                :title="$t('users_page.set_password_hint')"
              >
                <Lock class="text-[13px]" aria-hidden="true" />
              </button>
            </div>

            <!-- الإجراءات الأساسية -->
            <div class="flex items-center gap-2.5">
              <button type="button" @click="viewingUser = null" class="text-[12.5px] font-bold px-4 py-2.5 rounded-full border border-[#e7e2d6] dark:border-white/10 hover:bg-[#f4efe5]/60 dark:hover:bg-white/5">{{ $t("common.close") }}</button>
              <button v-if="can('users.update')" type="button" @click="openEdit(viewingUser); viewingUser = null" class="btn-fill relative bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white text-[12.5px] font-bold px-5 py-2.5 rounded-full shadow-md flex items-center gap-2">
                <Pencil aria-hidden="true" /> {{ $t("common.edit") }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ===================== نافذة تعيين كلمة سر مؤقتة ===================== -->
    <Teleport to="body">
      <div v-if="setPasswordModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" @click.self="setPasswordModal = null">
        <div class="glass-card !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-sm p-5 shadow-2xl">
          <h3 class="text-[14px] font-extrabold mb-1.5 flex items-center gap-2">
            <Lock class="text-[#D9534F]" aria-hidden="true" />
            {{ $t("users_page.set_password_for", { name: setPasswordModal.name }) }}
          </h3>
          <p class="text-[11px] text-[#9a9d97] dark:text-[#8f938a] mb-4">
            {{ $t("users_page.set_password_desc") }}
          </p>

          <div v-if="setPasswordSuccess" class="text-center space-y-3 py-2">
            <CircleCheck class="text-2xl text-[#28A745]" aria-hidden="true" />
            <p class="text-[12.5px] font-bold">{{ $t("users_page.password_set_success") }}</p>
            <p class="text-[10.5px] text-[#9a9d97] dark:text-[#8f938a]">{{ $t("users_page.sessions_logged_out") }}</p>
            <button type="button" @click="setPasswordModal = null" class="btn-fill relative bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white text-[12.5px] font-bold px-6 py-2.5 rounded-full shadow-md">
              {{ $t("common.close") }}
            </button>
          </div>

          <form v-else @submit.prevent="handleSetPassword" class="space-y-3">
            <div>
              <label class="text-[11px] font-bold block mb-1.5">{{ $t("users_page.temp_password_label") }}</label>
              <div class="relative">
                <input
                  v-model="setPasswordForm.password"
                  :type="showSetPassword ? 'text' : 'password'"
                  required
                  class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-lg px-3 py-2 ps-16 text-[13px] font-mono outline-none focus:border-[#8A6D1F]"
                />
                <div class="absolute top-1/2 -translate-y-1/2 start-2 flex items-center gap-1">
                  <button type="button" @click="showSetPassword = !showSetPassword" class="w-6 h-6 rounded-md flex items-center justify-center text-[#9a9d97] dark:text-[#8f938a] hover:text-[#8A6D1F]" :aria-label="showSetPassword ? $t('common.hide_password') : $t('common.show_password')">
                    <EyeOff class="text-[11px]" aria-hidden="true" v-if="showSetPassword" /><Eye class="text-[11px]" aria-hidden="true" v-else />
                  </button>
                  <button type="button" @click="copyPassword" class="w-6 h-6 rounded-md flex items-center justify-center text-[#9a9d97] dark:text-[#8f938a] hover:text-[#8A6D1F]" :aria-label="$t('common.copy_password')">
                    <Copy class="text-[11px]" aria-hidden="true" />
                  </button>
                </div>
              </div>
              <p v-if="setPasswordErrors?.password" class="text-[10.5px] text-[#D9534F] mt-1">{{ setPasswordErrors.password[0] }}</p>
              <button type="button" @click="generatePassword" class="text-[10.5px] font-bold text-[#8A6D1F] mt-1.5">
                <Shuffle aria-hidden="true" /> {{ $t("users_page.generate_new_password") }}
              </button>
            </div>

            <div class="flex gap-2.5 pt-2">
              <button type="button" @click="setPasswordModal = null" class="flex-1 text-[12.5px] font-bold py-2.5 rounded-full border border-[#e7e2d6] dark:border-white/10 hover:bg-[#f4efe5]/60 dark:hover:bg-white/5">{{ $t("dashboard.cancel") }}</button>
              <button type="submit" :disabled="isSettingPassword" class="flex-1 btn-fill relative bg-gradient-to-l from-[#D9534F] to-[#8A2E2A] text-white text-[12.5px] font-bold py-2.5 rounded-full shadow-md flex items-center justify-center gap-2 disabled:opacity-60">
                <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isSettingPassword" /><Check aria-hidden="true" v-else />
                {{ isSettingPassword ? $t("users_page.setting_ellipsis") : $t("users_page.set_action") }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- ===================== نافذة إضافة مستخدم موحَّدة (Wizard خطوة داخل نفس النافذة) ===================== -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition duration-250 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div v-if="addUserModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" @click.self="closeAddUserForms">
          <div class="add-user-menu-card glass-card !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-sm shadow-2xl relative overflow-hidden max-h-[90vh] flex flex-col">
            <div class="absolute -start-14 -top-14 w-40 h-40 bg-[#D4AF37]/15 dark:bg-[#D4AF37]/20 rounded-full blur-[60px] pointer-events-none"></div>
            <div class="absolute -end-10 -bottom-14 w-40 h-40 bg-[#52733D]/15 dark:bg-[#8cc35a]/15 rounded-full blur-[60px] pointer-events-none"></div>

            <!-- رأس ثابت: عنوان الخطوة الحالية + زر رجوع (إن لم تكن خطوة الاختيار) + زر إغلاق -->
            <div class="relative flex items-center justify-between px-5 pt-5 pb-1 shrink-0">
              <div class="flex items-center gap-2.5 min-w-0">
                <button v-if="addUserStep !== 'choice'" type="button" @click="backToAddUserChoice" class="icon-btn !w-8 !h-8 shrink-0" :title="$t('common.back')">
                  <ArrowLeft class="ltr:inline-block rtl:hidden" aria-hidden="true" />
                  <ArrowRight class="rtl:inline-block ltr:hidden" aria-hidden="true" />
                </button>
                <span v-else class="w-9 h-9 rounded-xl bg-gradient-to-br from-[#3E582E] to-[#8A6D1F] text-white flex items-center justify-center text-[13px] shadow-md shrink-0">
                  <UserPlus aria-hidden="true" />
                </span>
                <h3 class="text-[15px] font-extrabold truncate">{{ ADD_USER_STEP_TITLES[addUserStep] }}</h3>
              </div>
              <button :aria-label="$t('common.close')" type="button" @click="closeAddUserForms" class="icon-btn !w-8 !h-8 shrink-0"><X aria-hidden="true" /></button>
            </div>

            <div class="relative px-5 pb-5 overflow-y-auto">
              <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 translate-x-3 rtl:-translate-x-3"
                enter-to-class="opacity-100 translate-x-0"
                leave-active-class="transition duration-120 ease-in absolute inset-x-5"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
                mode="out-in"
              >
                <!-- ========== خطوة 1: اختيار نوع المستخدم ========== -->
                <div v-if="addUserStep === 'choice'" key="choice">
                  <p class="text-[11px] text-[#9a9d97] dark:text-[#8f938a] mb-4">{{ $t("users_page.choose_account_type") }}</p>
                  <div class="space-y-2">
                    <button
                      v-for="(opt, i) in ADD_USER_OPTIONS" :key="opt.type" type="button"
                      @click="selectAddUserType(opt.type)"
                      class="add-user-option-btn group w-full flex items-center gap-3 p-3.5 rounded-xl border border-[#eee8da] dark:border-white/5 transition-all duration-300 text-start hover:-translate-y-0.5 hover:shadow-lg"
                      :style="{ '--opt-color': opt.c1, '--opt-color2': opt.c2, animationDelay: `${i * 60}ms` }"
                    >
                      <span class="w-11 h-11 rounded-xl text-white flex items-center justify-center shrink-0 shadow-md transition-transform duration-300 group-hover:scale-110" :style="{ background: `linear-gradient(135deg, ${opt.c1}, ${opt.c2})` }">
                        <AppIcon :name="opt.icon" />
                      </span>
                      <div class="min-w-0 flex-1">
                        <p class="text-[12.5px] font-bold">{{ opt.label }}</p>
                        <p class="text-[10.5px] text-[#9a9d97] dark:text-[#8f938a] leading-relaxed">{{ opt.desc }}</p>
                      </div>
                      <ArrowRight class="text-[11px] text-[#c9cdc2] dark:text-[#565952] shrink-0 transition-all duration-300 group-hover:text-[var(--opt-color)] ltr:inline-block rtl:hidden" aria-hidden="true" />
                      <ArrowLeft class="text-[11px] text-[#c9cdc2] dark:text-[#565952] shrink-0 transition-all duration-300 group-hover:text-[var(--opt-color)] rtl:inline-block ltr:hidden" aria-hidden="true" />
                    </button>
                  </div>
                </div>

                <!-- ========== خطوة 2أ: فورم إضافة صاحب مولد ========== -->
                <form v-else-if="addUserStep === 'owner'" key="owner" @submit.prevent="handleCreateOwner" class="space-y-3">
                  <div>
                    <label class="text-[11px] font-bold block mb-1.5">{{ $t("dashboard.name") }}</label>
                    <input v-model="ownerForm.name" required type="text" autocomplete="off" :placeholder="$t('users_page.name_placeholder_example')" class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-lg px-3 py-2 text-[12.5px] outline-none focus:border-[#8A6D1F]" />
                    <p v-if="createOwnerErrors?.name" class="text-[10.5px] text-[#D9534F] mt-1">{{ createOwnerErrors.name[0] }}</p>
                  </div>
                  <div>
                    <label class="text-[11px] font-bold block mb-1.5">{{ $t("users_page.email_label") }}</label>
                    <input v-model="ownerForm.email" required type="email" dir="ltr" autocomplete="off" placeholder="example@email.com" class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-lg px-3 py-2 text-[12.5px] outline-none focus:border-[#8A6D1F]" />
                    <p v-if="createOwnerErrors?.email" class="text-[10.5px] text-[#D9534F] mt-1">{{ createOwnerErrors.email[0] }}</p>
                  </div>
                  <div>
                    <label class="text-[11px] font-bold block mb-1.5">{{ $t("users_page.phone_optional_label") }}</label>
                    <input v-model="ownerForm.phone" type="text" dir="ltr" autocomplete="off" placeholder="+970 5X XXX XXXX" class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-lg px-3 py-2 text-[12.5px] outline-none focus:border-[#8A6D1F]" />
                    <p v-if="createOwnerErrors?.phone" class="text-[10.5px] text-[#D9534F] mt-1">{{ createOwnerErrors.phone[0] }}</p>
                  </div>
                  <div>
                    <label class="text-[11px] font-bold block mb-1.5">{{ $t("dashboard.password") }}</label>
                    <div class="relative">
                      <input v-model="ownerForm.password" required :type="showOwnerPassword ? 'text' : 'password'" autocomplete="new-password" class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-lg px-3 py-2 ps-8 text-[12.5px] font-mono outline-none focus:border-[#8A6D1F]" />
                      <button type="button" @click="showOwnerPassword = !showOwnerPassword" class="absolute top-1/2 -translate-y-1/2 start-2 text-[#9a9d97] dark:text-[#8f938a] hover:text-[#8A6D1F]" :aria-label="showOwnerPassword ? $t('common.hide_password') : $t('common.show_password')">
                        <EyeOff class="text-[11px]" aria-hidden="true" v-if="showOwnerPassword" /><Eye class="text-[11px]" aria-hidden="true" v-else />
                      </button>
                    </div>
                    <p v-if="createOwnerErrors?.password" class="text-[10.5px] text-[#D9534F] mt-1">{{ createOwnerErrors.password[0] }}</p>
                    <button type="button" @click="generateOwnerPassword" class="text-[10.5px] font-bold text-[#8A6D1F] mt-1.5"><Shuffle aria-hidden="true" /> {{ $t("users_page.generate_random_password") }}</button>
                  </div>
                  <div>
                    <label class="text-[11px] font-bold block mb-1.5">{{ $t("dashboard.confirm_password") }}</label>
                    <input v-model="ownerForm.password_confirmation" required :type="showOwnerPassword ? 'text' : 'password'" dir="ltr" autocomplete="new-password" class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-lg px-3 py-2 text-[12.5px] font-mono outline-none focus:border-[#8A6D1F]" />
                  </div>
                  <div class="flex gap-2.5 pt-2">
                    <button type="button" @click="backToAddUserChoice" class="flex-1 text-[12.5px] font-bold py-2.5 rounded-full border border-[#e7e2d6] dark:border-white/10 hover:bg-[#f4efe5]/60 dark:hover:bg-white/5">{{ $t("common.back") }}</button>
                    <button type="submit" :disabled="isCreatingOwner" class="flex-1 btn-fill relative bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white text-[12.5px] font-bold py-2.5 rounded-full shadow-md flex items-center justify-center gap-2 disabled:opacity-60">
                      <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isCreatingOwner" /><Check aria-hidden="true" v-else />
                      {{ isCreatingOwner ? $t("users_page.creating_ellipsis") : $t("users_page.create_action") }}
                    </button>
                  </div>
                </form>

                <!-- ========== خطوة 2ب: فورم إضافة مشترك ========== -->
                <form v-else-if="addUserStep === 'subscriber'" key="subscriber" @submit.prevent="handleCreateSubscriber" class="space-y-3">
                  <div>
                    <label class="text-[11px] font-bold block mb-1.5">{{ $t("dashboard.name") }}</label>
                    <input v-model="subscriberForm.name" required type="text" autocomplete="off" :placeholder="$t('users_page.name_placeholder_example')" class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-lg px-3 py-2 text-[12.5px] outline-none focus:border-[#8A6D1F]" />
                    <p v-if="createSubscriberErrors?.name" class="text-[10.5px] text-[#D9534F] mt-1">{{ createSubscriberErrors.name[0] }}</p>
                  </div>
                  <div>
                    <label class="text-[11px] font-bold block mb-1.5">{{ $t("users_page.email_label") }}</label>
                    <input v-model="subscriberForm.email" required type="email" dir="ltr" autocomplete="off" placeholder="example@email.com" class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-lg px-3 py-2 text-[12.5px] outline-none focus:border-[#8A6D1F]" />
                    <p v-if="createSubscriberErrors?.email" class="text-[10.5px] text-[#D9534F] mt-1">{{ createSubscriberErrors.email[0] }}</p>
                  </div>
                  <div>
                    <label class="text-[11px] font-bold block mb-1.5">{{ $t("users_page.phone_optional_label") }}</label>
                    <input v-model="subscriberForm.phone" type="text" dir="ltr" autocomplete="off" placeholder="+970 5X XXX XXXX" class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-lg px-3 py-2 text-[12.5px] outline-none focus:border-[#8A6D1F]" />
                    <p v-if="createSubscriberErrors?.phone" class="text-[10.5px] text-[#D9534F] mt-1">{{ createSubscriberErrors.phone[0] }}</p>
                  </div>
                  <div>
                    <label class="text-[11px] font-bold block mb-1.5">{{ $t("users_page.address_optional_label") }}</label>
                    <input v-model="subscriberForm.address" type="text" class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-lg px-3 py-2 text-[12.5px] outline-none focus:border-[#8A6D1F]" />
                  </div>
                  <div>
                    <label class="text-[11px] font-bold block mb-1.5">{{ $t("users_page.neighborhood_optional_label") }}</label>
                    <AppDropdownSelect
                      v-model="subscriberForm.neighborhood_id"
                      :options="neighborhoodOptions"
                      :disabled="isLoadingNeighborhoods"
                      :placeholder="isLoadingNeighborhoods ? $t('common.loading') : $t('users_page.neighborhood_placeholder')"
                      variant="field"
                      width-class="w-full"
                      match-trigger-width
                    />
                  </div>
                  <div>
                    <label class="text-[11px] font-bold block mb-1.5">{{ $t("dashboard.password") }}</label>
                    <div class="relative">
                      <input v-model="subscriberForm.password" required :type="showSubscriberPassword ? 'text' : 'password'" autocomplete="new-password" class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-lg px-3 py-2 ps-8 text-[12.5px] font-mono outline-none focus:border-[#8A6D1F]" />
                      <button type="button" @click="showSubscriberPassword = !showSubscriberPassword" class="absolute top-1/2 -translate-y-1/2 start-2 text-[#9a9d97] dark:text-[#8f938a] hover:text-[#8A6D1F]" :aria-label="showSubscriberPassword ? $t('common.hide_password') : $t('common.show_password')">
                        <EyeOff class="text-[11px]" aria-hidden="true" v-if="showSubscriberPassword" /><Eye class="text-[11px]" aria-hidden="true" v-else />
                      </button>
                    </div>
                    <p v-if="createSubscriberErrors?.password" class="text-[10.5px] text-[#D9534F] mt-1">{{ createSubscriberErrors.password[0] }}</p>
                    <button type="button" @click="generateSubscriberPassword" class="text-[10.5px] font-bold text-[#8A6D1F] mt-1.5"><Shuffle aria-hidden="true" /> {{ $t("users_page.generate_random_password") }}</button>
                  </div>
                  <div>
                    <label class="text-[11px] font-bold block mb-1.5">{{ $t("dashboard.confirm_password") }}</label>
                    <input v-model="subscriberForm.password_confirmation" required :type="showSubscriberPassword ? 'text' : 'password'" dir="ltr" autocomplete="new-password" class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-lg px-3 py-2 text-[12.5px] font-mono outline-none focus:border-[#8A6D1F]" />
                  </div>
                  <div class="flex gap-2.5 pt-2">
                    <button type="button" @click="backToAddUserChoice" class="flex-1 text-[12.5px] font-bold py-2.5 rounded-full border border-[#e7e2d6] dark:border-white/10 hover:bg-[#f4efe5]/60 dark:hover:bg-white/5">{{ $t("common.back") }}</button>
                    <button type="submit" :disabled="isCreatingSubscriber" class="flex-1 btn-fill relative bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white text-[12.5px] font-bold py-2.5 rounded-full shadow-md flex items-center justify-center gap-2 disabled:opacity-60">
                      <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isCreatingSubscriber" /><Check aria-hidden="true" v-else />
                      {{ isCreatingSubscriber ? $t("users_page.creating_ellipsis") : $t("users_page.create_action") }}
                    </button>
                  </div>
                </form>

                <!-- ========== خطوة 2ج: فورم إضافة فني (نيابةً عن مالك) ========== -->
                <form v-else-if="addUserStep === 'technician'" key="technician" @submit.prevent="handleCreateTechnician" class="space-y-3">
                  <p class="text-[11px] text-[#9a9d97] dark:text-[#8f938a] -mt-1 mb-2">{{ $t("users_page.technician_form_hint") }}</p>
                  <div>
                    <label class="text-[11px] font-bold block mb-1.5">{{ $t("users_page.owner_field_label") }}</label>
                    <AppDropdownSelect
                      v-model="technicianForm.owner_id"
                      :options="ownerSelectOptions"
                      :disabled="isLoadingOwners"
                      :placeholder="isLoadingOwners ? $t('common.loading') : $t('users_page.select_owner_placeholder')"
                      variant="field"
                      width-class="w-full"
                      match-trigger-width
                    />
                    <p v-if="createTechnicianErrors?.owner_id" class="text-[10.5px] text-[#D9534F] mt-1">{{ createTechnicianErrors.owner_id[0] }}</p>
                  </div>
                  <div>
                    <label class="text-[11px] font-bold block mb-1.5">{{ $t("users_page.technician_name_label") }}</label>
                    <input v-model="technicianForm.name" required type="text" autocomplete="off" :placeholder="$t('users_page.name_placeholder_example')" class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-lg px-3 py-2 text-[12.5px] outline-none focus:border-[#8A6D1F]" />
                    <p v-if="createTechnicianErrors?.name" class="text-[10.5px] text-[#D9534F] mt-1">{{ createTechnicianErrors.name[0] }}</p>
                  </div>
                  <div>
                    <label class="text-[11px] font-bold block mb-1.5">{{ $t("users_page.email_label") }}</label>
                    <input v-model="technicianForm.email" required type="email" dir="ltr" autocomplete="off" placeholder="example@email.com" class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-lg px-3 py-2 text-[12.5px] outline-none focus:border-[#8A6D1F]" />
                    <p v-if="createTechnicianErrors?.email" class="text-[10.5px] text-[#D9534F] mt-1">{{ createTechnicianErrors.email[0] }}</p>
                  </div>
                  <div>
                    <label class="text-[11px] font-bold block mb-1.5">{{ $t("dashboard.password") }}</label>
                    <div class="relative">
                      <input v-model="technicianForm.password" required :type="showTechnicianPassword ? 'text' : 'password'" autocomplete="new-password" class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-lg px-3 py-2 ps-8 text-[12.5px] font-mono outline-none focus:border-[#8A6D1F]" />
                      <button type="button" @click="showTechnicianPassword = !showTechnicianPassword" class="absolute top-1/2 -translate-y-1/2 start-2 text-[#9a9d97] dark:text-[#8f938a] hover:text-[#8A6D1F]" :aria-label="showTechnicianPassword ? $t('common.hide_password') : $t('common.show_password')">
                        <EyeOff class="text-[11px]" aria-hidden="true" v-if="showTechnicianPassword" /><Eye class="text-[11px]" aria-hidden="true" v-else />
                      </button>
                    </div>
                    <p v-if="createTechnicianErrors?.password" class="text-[10.5px] text-[#D9534F] mt-1">{{ createTechnicianErrors.password[0] }}</p>
                    <button type="button" @click="generateTechnicianPassword" class="text-[10.5px] font-bold text-[#8A6D1F] mt-1.5"><Shuffle aria-hidden="true" /> {{ $t("users_page.generate_random_password") }}</button>
                  </div>
                  <div>
                    <label class="text-[11px] font-bold block mb-1.5">{{ $t("dashboard.confirm_password") }}</label>
                    <input v-model="technicianForm.password_confirmation" required :type="showTechnicianPassword ? 'text' : 'password'" dir="ltr" autocomplete="new-password" class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-lg px-3 py-2 text-[12.5px] font-mono outline-none focus:border-[#8A6D1F]" />
                  </div>
                  <div>
                    <label class="text-[11px] font-bold block mb-1.5">{{ $t("users_page.notes_optional_label") }}</label>
                    <textarea v-model="technicianForm.notes" rows="2" maxlength="1000" class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-lg px-3 py-2 text-[12.5px] outline-none focus:border-[#8A6D1F] resize-none"></textarea>
                  </div>
                  <div class="flex gap-2.5 pt-2">
                    <button type="button" @click="backToAddUserChoice" class="flex-1 text-[12.5px] font-bold py-2.5 rounded-full border border-[#e7e2d6] dark:border-white/10 hover:bg-[#f4efe5]/60 dark:hover:bg-white/5">{{ $t("common.back") }}</button>
                    <button type="submit" :disabled="isCreatingTechnician" class="flex-1 btn-fill relative bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white text-[12.5px] font-bold py-2.5 rounded-full shadow-md flex items-center justify-center gap-2 disabled:opacity-60">
                      <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isCreatingTechnician" /><Check aria-hidden="true" v-else />
                      {{ isCreatingTechnician ? $t("users_page.creating_ellipsis") : $t("users_page.create_action") }}
                    </button>
                  </div>
                </form>
              </Transition>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ===================== نافذة التعديل ===================== -->
    <Teleport to="body">
      <div v-if="editingUser" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" @click.self="editingUser = null">
        <div class="glass-card !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-sm p-5 shadow-2xl">
          <h3 class="text-[14px] font-extrabold mb-4">{{ $t("users_page.edit_user_title", { name: editingUser.name }) }}</h3>

          <div class="space-y-3">
            <div>
              <label class="text-[11px] font-bold block mb-1.5">{{ $t("dashboard.name") }}</label>
              <input v-model="editForm.name" type="text" class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-lg px-3 py-2 text-[12.5px] outline-none focus:border-[#8A6D1F]" />
              <p v-if="fieldError('name')" class="text-[10.5px] text-[#D9534F] mt-1">{{ fieldError("name") }}</p>
            </div>
            <div>
              <label class="text-[11px] font-bold block mb-1.5">{{ $t("users_page.email_label") }}</label>
              <input v-model="editForm.email" type="email" dir="ltr" class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-lg px-3 py-2 text-[12.5px] outline-none focus:border-[#8A6D1F]" />
              <p v-if="fieldError('email')" class="text-[10.5px] text-[#D9534F] mt-1">{{ fieldError("email") }}</p>
            </div>
            <div>
              <label class="text-[11px] font-bold block mb-1.5">{{ $t("users_page.phone_label") }}</label>
              <input v-model="editForm.phone" type="text" dir="ltr" class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-lg px-3 py-2 text-[12.5px] outline-none focus:border-[#8A6D1F]" />
              <p v-if="fieldError('phone')" class="text-[10.5px] text-[#D9534F] mt-1">{{ fieldError("phone") }}</p>
            </div>
            <div>
              <label class="text-[11px] font-bold block mb-1.5">{{ $t("users_page.account_status_label") }}</label>
              <AppDropdownSelect v-model="editForm.status" :options="editStatusOptions" variant="field" width-class="w-full" match-trigger-width />
              <p v-if="fieldError('status')" class="text-[10.5px] text-[#D9534F] mt-1">{{ fieldError("status") }}</p>
            </div>
          </div>

          <div class="flex gap-2.5 mt-5">
            <button type="button" @click="editingUser = null" class="flex-1 text-[12.5px] font-bold py-2.5 rounded-full border border-[#e7e2d6] dark:border-white/10 hover:bg-[#f4efe5]/60 dark:hover:bg-white/5">{{ $t("dashboard.cancel") }}</button>
            <button type="button" @click="handleSave" :disabled="isSaving" class="flex-1 btn-fill relative bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white text-[12.5px] font-bold py-2.5 rounded-full shadow-md flex items-center justify-center gap-2 disabled:opacity-60">
              <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isSaving" /><Check aria-hidden="true" v-else />
              {{ isSaving ? $t("users_page.saving_ellipsis") : $t("users_page.save_action") }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>