import DashboardLayout from "@/layouts/DashboardLayout.vue";

export default [
  {
    path: "/subscriber",
    component: DashboardLayout,
    meta: { requiresAuth: true, role: "subscriber" },
    children: [
      {
        path: "",
        name: "subscriber.dashboard",
        component: () => import("@/views/subscriber/DashboardView.vue"),
      },
      {
        path: "meter-readings",
        name: "subscriber.meter-readings",
        component: () => import("@/views/subscriber/MeterReadingsView.vue"),
      },
      {
        path: "subscription",
        name: "subscriber.subscription",
        component: () => import("@/views/subscriber/SubscriptionCenterView.vue"),
      },
      {
        path: "invoices",
        name: "subscriber.invoices",
        component: () => import("@/views/subscriber/MyInvoicesView.vue"),
      },
      {
        path: "support",
        name: "subscriber.support",
        component: () => import("@/views/subscriber/SupportCenterView.vue"),
      },


      {
        path: "notifications",
        name: "subscriber.notifications",
        component: () => import("@/views/subscriber/NotificationsView.vue"),
      },
      {
        path: "messages",
        name: "subscriber.messages",
        component: () => import("@/views/subscriber/MessagesView.vue"),
        meta: { permission: "conversations.view" },
      },
      {
        path: "offers",
        name: "subscriber.offers",
        component: () => import("@/views/subscriber/OffersView.vue"),
        meta: { permission: "offers.view" },
      },
      {
        path: "settings",
        name: "subscriber.settings",
        component: () => import("@/views/shared/SettingsView.vue"),
      },
    ],
  },
];