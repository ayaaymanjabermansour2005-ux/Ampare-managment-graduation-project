import AuthLayout from "@/layouts/AuthLayout.vue";

export default [
  {
    path: "/",
    component: AuthLayout,
    meta: { guestOnly: true },
    children: [
      {
        path: "login",
        name: "login",
        component: () => import("@/views/auth/LoginView.vue"),
      },
      {
        path: "register",
        name: "register",
        component: () => import("@/views/auth/RegisterRoleChoiceView.vue"),
      },
      {
        path: "register/subscriber",
        name: "register.subscriber",
        component: () => import("@/views/auth/RegisterView.vue"),
      },
      {
        path: "register/owner",
        name: "register.owner",
        component: () => import("@/views/auth/OwnerApplicationView.vue"),
      },
      {
        path: "forgot-password",
        name: "forgot-password",
        component: () => import("@/views/auth/ForgotPasswordView.vue"),
      },
      {
        path: "reset-password",
        name: "reset-password",
        component: () => import("@/views/auth/ResetPasswordView.vue"),
      },
    ],
  },
];