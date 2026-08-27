import LandingLayout from "@/layouts/LandingLayout.vue";

export default [
  {
    path: "/",
    name: "home",
    component: LandingLayout,
    children: [
      {
        path: "",
        name: "landing.home",
        component: () => import("@/views/landing/HomeView.vue"),
      },
      {
        path: "articles",
        name: "landing.articles",
        component: () => import("@/views/landing/ArticlesListView.vue"),
      },
      {
        path: "articles/:slug",
        name: "landing.articles.show",
        component: () => import("@/views/landing/ArticleDetailView.vue"),
      },
      {
        path: "live-schedule",
        name: "landing.live-schedule",
        component: () => import("@/views/landing/LiveScheduleView.vue"),
      },
    ],
  },
];