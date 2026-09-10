import DashboardLayout from "@/layouts/DashboardLayout.vue";

export default [
  {
    path: "/admin",
    component: DashboardLayout,
    meta: { requiresAuth: true, role: "admin" },
    children: [
      {
        path: "",
        name: "admin.dashboard",
        component: () => import("@/views/admin/DashboardView.vue"),
      },
      {
        path: "users",
        name: "admin.users",
        component: () => import("@/views/admin/UsersView.vue"),
        meta: { permission: "users.view" },
      },
      {
        path: "generator-owners",
        name: "admin.generator-owners",
        component: () => import("@/views/admin/GeneratorOwnersView.vue"),
        meta: { permission: "generators.view" },
      },
      {
        path: "generators",
        name: "admin.generators",
        component: () => import("@/views/admin/GeneratorsManagementView.vue"),
        meta: { permission: "generators.view" },
      },
      {
        path: "complaints",
        name: "admin.complaints",
        component: () => import("@/views/admin/AdminComplaintsView.vue"),
        meta: { permission: "complaints.view" },
      },
      {
        path: "faults",
        name: "admin.faults",
        component: () => import("@/views/admin/AdminFaultsView.vue"),
        meta: { permission: "faults.view" },
      },
      {
        path: "contact-messages",
        name: "admin.contact-messages",
        component: () => import("@/views/admin/ContactMessagesView.vue"),
      },
      {
        path: "meter-readings",
        name: "admin.meter-readings",
        component: () => import("@/views/admin/AdminMeterReadingsView.vue"),
        meta: { permission: "meter-readings.view" },
      },
      {
        path: "service-requests",
        name: "admin.service-requests",
        component: () => import("@/views/admin/ServiceRequestsView.vue"),
        meta: { permission: "service-requests.view" },
      },
      {
        path: "offers",
        name: "admin.offers",
        component: () => import("@/views/admin/AdminOffersView.vue"),
        meta: { permission: "offers.view" },
      },
      {
        path: "subscribers",
        name: "admin.subscribers",
        component: () => import("@/views/admin/SubscribersView.vue"),
        meta: { permission: "subscriptions.view" },
      },
      {
        path: "technicians",
        name: "admin.technicians",
        component: () => import("@/views/admin/TechniciansView.vue"),
        meta: { permission: "technicians.view" },
      },
      {
        path: "payments",
        name: "admin.payments",
        component: () => import("@/views/admin/PaymentsView.vue"),
        meta: { permission: "payments.view" },
      },
      {
        path: "reports",
        name: "admin.reports",
        component: () => import("@/views/admin/ReportsView.vue"),
        meta: { permission: "platform-commissions.view" },
      },
      {
        path: "messages",
        name: "admin.messages",
        component: () => import("@/views/admin/MessagesView.vue"),
        meta: { permission: "conversations.view" },
      },
      {
        path: "settings",
        name: "admin.settings",
        component: () => import("@/views/admin/SettingsView.vue"),
      },
      {
        path: "account-settings",
        name: "admin.account-settings",
        component: () => import("@/views/shared/SettingsView.vue"),
      },
      {
        path: "login-logs",
        name: "admin.login-logs",
        component: () => import("@/views/admin/LoginLogsView.vue"),
      },
      {
        path: "invoices",
        name: "admin.invoices",
        component: () => import("@/views/admin/InvoicesView.vue"),
        meta: { permission: "invoices.view" },
      },
      {
        path: "articles",
        name: "admin.articles",
        component: () => import("@/views/admin/ArticlesView.vue"),
        meta: { permission: "articles.view" },
      },
            {
        path: "neighborhoods",
        name: "admin.neighborhoods",
        component: () => import("@/views/admin/NeighborhoodDashboardView.vue"),
        meta: { permission: "neighborhoods.dashboard" },
      },
    ],
  },
];