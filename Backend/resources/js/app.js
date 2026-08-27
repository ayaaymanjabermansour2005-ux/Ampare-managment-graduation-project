import "@fortawesome/fontawesome-free/css/all.min.css";
import "./bootstrap";
import "./plugins/echo";
import "../css/app.css";

import { createApp } from "vue";
import { createPinia } from "pinia";
import App from "./App.vue";
import router from "./router";
import { useConnectivityStore } from "./stores/connectivity";
import PrimeVue from "./plugins/primevue";
import i18n from "./i18n";

const app = createApp(App);

app.use(createPinia());
app.use(router);
app.use(PrimeVue);
app.use(i18n);

app.mount("#app");

useConnectivityStore().init();

if ("serviceWorker" in navigator) {
  import("virtual:pwa-register")
    .then(({ registerSW }) => registerSW({ immediate: true }))
    .catch(() => {});
}