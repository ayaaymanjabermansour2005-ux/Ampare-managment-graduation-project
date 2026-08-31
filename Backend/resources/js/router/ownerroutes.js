import DashboardLayout from "@/layouts/DashboardLayout.vue";

export default [
  {
    path: "/owner",
    component: DashboardLayout,
    meta: { requiresAuth: true, role: "generator_owner" },
    children: [
      {
        path: "",
        name: "owner.dashboard",
        component: () => import("@/views/owner/DashboardView.vue"),
      },
      {
        path: "generators",
        name: "owner.generators",
        component: () => import("@/views/owner/GeneratorsView.vue"),
        meta: { permission: "generators.view" },
      },
      {
        path: "subscribers",
        name: "owner.subscribers",
        component: () => import("@/views/owner/SubscribersView.vue"),
        meta: { permission: "subscriptions.view" },
      },
      {
        path: "meter-readings",
        name: "owner.meter-readings",
        component: () => import("@/views/owner/MeterReadingsView.vue"),
        meta: { permission: "meter-readings.view" },
      },
      {
        path: "invoices",
        name: "owner.invoices",
        component: () => import("@/views/owner/InvoicesView.vue"),
        meta: { permission: "invoices.view" },
      },
      {
        path: "technicians",
        name: "owner.technicians",
        component: () => import("@/views/owner/TechniciansView.vue"),
        meta: { permission: "technicians.view" },
      },
      {
        path: "reports",
        name: "owner.reports",
        component: () => import("@/views/owner/ReportsView.vue"),
        meta: { permission: "platform-commissions.view" },
      },
      {
        path: "faults",
        name: "owner.faults",
        component: () => import("@/views/owner/FaultsView.vue"),
        meta: { permission: "faults.view" },
      },
      {
        path: "complaints",
        name: "owner.complaints",
        component: () => import("@/views/owner/ComplaintsView.vue"),
        meta: { permission: "complaints.view" },
      },
      {
        path: "service-requests",
        name: "owner.service-requests",
        component: () => import("@/views/owner/ServiceRequestsView.vue"),
        meta: { permission: "service-requests.view" },
      },
      {
        path: "messages",
        name: "owner.messages",
        component: () => import("@/views/owner/MessagesView.vue"),
        meta: { permission: "conversations.view" },
      },
      {
        path: "offers",
        name: "owner.offers",
        component: () => import("@/views/owner/OffersView.vue"),
        meta: { permission: "offers.view" },
      },
      {
        path: "settings",
        name: "owner.settings",
        component: () => import("@/views/shared/SettingsView.vue"),
      },
    ],
  },
];