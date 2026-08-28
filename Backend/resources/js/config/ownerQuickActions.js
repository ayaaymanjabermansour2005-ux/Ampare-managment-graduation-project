export default [
  { icon: "fa-bolt", labelKey: "menu.generators", route: "owner.generators", c1: "#52733D", c2: "#3E582E", permission: "generators.view" },
  { icon: "fa-user-group", labelKey: "menu.subscribers", route: "owner.subscribers", c1: "#17A2B8", c2: "#0f6c7d", permission: "subscriptions.view" },
  { icon: "fa-file-invoice", labelKey: "menu.invoices", route: "owner.invoices", c1: "#8A6D1F", c2: "#D4AF37", permission: "invoices.view" },
  // FIX: "owner.payments" ما كان موجود بأي ملف راوتر — الدفعات فعليًا
  // تبويب جوا owner.invoices (راجع منطق الفواتير/الدفعات وحذف رابط
  // السلايدر المكرر بالتقرير)، مش مسار مستقل.
  { icon: "fa-wallet", labelKey: "menu.payments", route: "owner.invoices", query: { tab: "payments" }, c1: "#28A745", c2: "#1f7a37", permission: "payments.view" },
  { icon: "fa-screwdriver-wrench", labelKey: "menu.technicians", route: "owner.technicians", c1: "#FFC107", c2: "#a3760a", permission: "technicians.view" },
];
