import TechnicianPortalLayout from "@/layouts/TechnicianPortalLayout.vue";

export default [
  {
    path: "/technician",
    component: TechnicianPortalLayout,
    meta: { requiresAuth: true, role: "technician" },
    children: [
      {
        path: "",
        name: "technician.dashboard",
        component: () => import("@/views/technician/TechnicianPortalView.vue"),
      },
      {
        path: "messages",
        name: "technician.messages",
        component: () => import("@/views/technician/TechnicianMessagesView.vue"),
        meta: { permission: "conversations.view" },
      },
      {
        path: "complaint",
        name: "technician.complaint",
        component: () => import("@/views/technician/QuickComplaintView.vue"),
        meta: { permission: "complaints.create" },
      },
      {
        path: "fault",
        name: "technician.fault",
        component: () => import("@/views/technician/QuickFaultView.vue"),
        meta: { permission: "faults.create" },
      },
      {
        path: "ai-assistant",
        name: "technician.ai-chat",
        component: () => import("@/views/technician/AiChatView.vue"),
        meta: { permission: "ai-chat.use" },
      },
      // ملاحظة: هذا المسار ليس من ضمن التبويبات الخمسة المطلوبة صراحةً بالمواصفة
      // (مهامي/القراءات/دفعاتي/الرسائل/المزيد)، لكنه احتياج أساسي مشترك بين كل
      // الأدوار (تغيير كلمة المرور/الاسم/الهاتف) ولا توجد أي وسيلة أخرى للفني
      // لإدارته — قرار مبرَّر بإعادته ضمن "المزيد" فقط، وليس تابًا رئيسيًا.
      {
        path: "settings",
        name: "technician.settings",
        component: () => import("@/views/shared/SettingsView.vue"),
      },
    ],
  },
];
