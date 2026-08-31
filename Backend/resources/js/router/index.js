import { createRouter, createWebHistory } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import { resolveHomeRouteName } from "@/utils/roleRedirect";
import DashboardLayout from "@/layouts/DashboardLayout.vue";

import landingRoutes from "./landingroutes";
import authRoutes from "./authroutes";
import adminRoutes from "./adminroutes";
import ownerRoutes from "./ownerroutes";
import subscriberRoutes from "./subscriberroutes";
import technicianRoutes from "./technicianroutes";

const routes = [
  ...landingRoutes,
  ...authRoutes,
  ...adminRoutes,
  ...ownerRoutes,
  ...subscriberRoutes,
  ...technicianRoutes,
  {
    path: "/invoices/:id/pay",
    component: DashboardLayout,
    meta: { requiresAuth: true },
    children: [
      {
        path: "",
        name: "payments.gateway",
        component: () => import("@/views/payments/PaymentGatewayView.vue"),
      },
    ],
  },
  {
    path: "/generators/:id",
    component: DashboardLayout,
    meta: { requiresAuth: true },
    children: [
      {
        path: "",
        name: "generators.show",
        component: () => import("@/views/generators/GeneratorDetailView.vue"),
      },
      {
        path: "quick-scan",
        name: "generators.quick-scan",
        component: () => import("@/views/generators/GeneratorQuickScanView.vue"),
      },
    ],
  },
  {
    path: "/subscriber-meters/:id",
    component: DashboardLayout,
    meta: { requiresAuth: true },
    children: [
      {
        path: "",
        name: "subscriber-meters.show",
        component: () => import("@/views/meters/SubscriberMeterDetailView.vue"),
      },
    ],
  },
  {
    path: "/403",
    name: "forbidden",
    component: () => import("@/views/errors/ForbiddenView.vue"),
  },
  {
    path: "/:pathMatch(.*)*",
    name: "not-found",
    component: () => import("@/views/errors/NotFoundView.vue"),
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach(async (to) => {
  const authStore = useAuthStore();

  if (!authStore.initialized) {
    await authStore.fetchUser();
  }

  if (to.meta.guestOnly && authStore.isAuthenticated) {
    return { name: resolveHomeRouteName(authStore) };
  }

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return { name: "login", query: { redirect: to.fullPath } };
  }

  if (to.meta.role && !authStore.hasRole(to.meta.role)) {
    return { name: "forbidden" };
  }

  if (to.meta.permission && !authStore.can(to.meta.permission)) {
    return { name: "forbidden" };
  }
});

export default router;
