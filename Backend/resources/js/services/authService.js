import http from "./http";

function fetchCsrfCookie() {
  return http.get("/sanctum/csrf-cookie", {
    baseURL: "/",
    withCredentials: true,
  });
}

export default {
  async register(payload) {
    await fetchCsrfCookie();
    const { data } = await http.post("/auth/register", payload);
    return data;
  },

  async login(payload) {
    await fetchCsrfCookie();
    const { data } = await http.post("/auth/login", payload);
    return data;
  },

  async logout() {
    const { data } = await http.post("/auth/logout");
    return data;
  },

  async logoutOtherDevices(password) {
    const { data } = await http.post("/auth/logout-other-devices", {
      password,
    });
    return data;
  },

  async me() {
    const { data } = await http.get("/auth/me", { __isAuthCheck: true });
    return data;
  },

  async changePassword(payload) {
    const { data } = await http.patch("/auth/password", payload);
    return data;
  },

  async forgotPassword(email) {
    await fetchCsrfCookie();
    const { data } = await http.post("/auth/forgot-password", { email });
    return data;
  },

  async resetPassword(payload) {
    await fetchCsrfCookie();
    const { data } = await http.post("/auth/reset-password", payload);
    return data;
  },

  async resendVerificationEmail(email) {
    const { data } = await http.post("/auth/email/resend", { email });
    return data;
  },

  async sessions() {
    const { data } = await http.get("/auth/sessions");
    return data;
  },

  async revokeSession(sessionId) {
    const { data } = await http.delete(`/auth/sessions/${sessionId}`);
    return data;
  },

  async loginLog(params = {}) {
    const { data } = await http.get("/auth/login-log", { params });
    return data;
  },

  async deleteAccount(password) {
    const { data } = await http.delete("/auth/account", {
      data: { password },
    });
    return data;
  },

  async submitOwnerApplication(payload) {
    await fetchCsrfCookie();

    const formData = new FormData();

    formData.append("name", payload.name);
    formData.append("email", payload.email);
    formData.append("password", payload.password);
    formData.append("password_confirmation", payload.password_confirmation);
    if (payload.phone) formData.append("phone", payload.phone);
    if (payload.notes) formData.append("notes", payload.notes);

    formData.append("generator_name", payload.generator_name);
    formData.append("generator_price_per_kw", payload.generator_price_per_kw);
    formData.append("generator_currency", payload.generator_currency || "ILS");
    if (payload.generator_capacity_kw) {
      formData.append("generator_capacity_kw", payload.generator_capacity_kw);
    }
    formData.append("generator_city", payload.generator_city);
    if (payload.generator_neighborhood_id) {
      formData.append("generator_neighborhood_id", payload.generator_neighborhood_id);
    }
    if (payload.generator_address) {
      formData.append("generator_address", payload.generator_address);
    }
    if (payload.generator_latitude) {
      formData.append("generator_latitude", payload.generator_latitude);
    }
    if (payload.generator_longitude) {
      formData.append("generator_longitude", payload.generator_longitude);
    }

    if (payload.id_document) formData.append("id_document", payload.id_document);
    if (payload.business_license) formData.append("business_license", payload.business_license);
    if (payload.generator_photo) formData.append("generator_photo", payload.generator_photo);
    if (payload.ownership_contract) formData.append("ownership_contract", payload.ownership_contract);

    const { data } = await http.post("/auth/owner-applications", formData);
    return data;
  },
};