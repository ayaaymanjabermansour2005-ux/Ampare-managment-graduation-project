import axios from "axios";
import { enqueueRequest } from "@/utils/offlineQueue";
import i18n, { getLocale } from "@/i18n";

const http = axios.create({
  baseURL: "/api/v1",
  withCredentials: true,
  withXSRFToken: true,
  headers: {
    Accept: "application/json",
  },
});

http.interceptors.request.use((config) => {
  config.headers["Accept-Language"] = getLocale();
  return config;
});

http.interceptors.request.use((config) => {
  if (config.idempotent) {
    config.headers["Idempotency-Key"] =
      config.idempotencyKey || crypto.randomUUID();
  }
  return config;
});

function formDataToPlainObject(formData) {
  const obj = {};
  for (const [key, value] of formData.entries()) {
    obj[key] = value;
  }
  return obj;
}

let isRefreshingCsrf = false;

http.interceptors.response.use(
  (response) => response,
  async (error) => {
    const { config, response } = error;
    const status = response?.status;
    const isNetworkError = !response;
    const isAuthCheck = config?.__isAuthCheck;

    if (isNetworkError && config?.queueOffline) {
      const idempotencyKey =
        config.headers?.["Idempotency-Key"] || crypto.randomUUID();
      const { useAuthStore } = await import("@/stores/auth");
      const userId = useAuthStore().user?.id;

      if (!userId) {
        return Promise.reject(error);
      }

      await enqueueRequest(
        {
          idempotencyKey,
          url: config.url,
          method: config.method,
          data:
            config.data instanceof FormData
              ? formDataToPlainObject(config.data)
              : config.data,
          isFormData: config.data instanceof FormData,
        },
        userId,
      );

      return Promise.resolve({
        status: 202,
        queued: true,
        data: {
          success: true,
          queued: true,
          message: i18n.global.t("common.offline_operation_queued_message"),
          data: null,
          errors: null,
        },
      });
    }

    if (isNetworkError) {
      return Promise.reject(error);
    }

    if (status === 419 && !config?.__isRetryAfterCsrf) {
      if (!isRefreshingCsrf) {
        isRefreshingCsrf = true;
        try {
          await axios.get("/sanctum/csrf-cookie", { withCredentials: true });
        } finally {
          isRefreshingCsrf = false;
        }
      }

      return http({ ...config, __isRetryAfterCsrf: true });
    }

    if (status === 401 && !isAuthCheck) {
      const { useAuthStore } = await import("@/stores/auth");
      const { default: router } = await import("@/router");

      useAuthStore().clearLocalSession();

      if (router.currentRoute.value.meta?.requiresAuth) {
        router.push({
          name: "login",
          query: { redirect: router.currentRoute.value.fullPath },
        });
      }
    }

    return Promise.reject(error);
  },
);

export default http;
