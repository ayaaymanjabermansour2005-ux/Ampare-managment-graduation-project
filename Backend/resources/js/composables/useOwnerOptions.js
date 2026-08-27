import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import userService from "@/services/userService";

/**
 * قائمة مالكي المولدات (generator_owner) للاستخدام في أي Dropdown يحتاج اختيار
 * مالك — مثل خطوة "إنشاء فني نيابةً عن مالك" في شاشة المستخدمين ولوحة الأدمن.
 */
export function useOwnerOptions() {
  const { locale } = useI18n();

  const owners = ref([]);
  const isLoadingOwners = ref(false);
  const ownerSelectOptions = computed(() =>
    owners.value.map((o) => ({ value: o.id, label: `${o.name} — ${o.email}` }))
  );

  async function fetchOwners() {
    isLoadingOwners.value = true;
    try {
      const { data } = await userService.list({ role: "generator_owner", per_page: 200 });
      const list = data.data.data ?? data.data;
      owners.value = [...list].sort((a, b) =>
        a.name.localeCompare(b.name, locale.value === "ar" ? "ar" : "en")
      );
    } finally {
      isLoadingOwners.value = false;
    }
  }

  return { owners, isLoadingOwners, ownerSelectOptions, fetchOwners };
}
