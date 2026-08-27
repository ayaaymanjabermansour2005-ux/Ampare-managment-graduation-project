import http from "./http";

export default {
  list(params = {}) {
    return http.get("/generators", { params });
  },
  
  exportUrl(params = {}) {
    const query = new URLSearchParams(params).toString();
    return `/api/v1/generators/export${query ? `?${query}` : ""}`;
  },

  cities() {
    return http.get("/generators/cities");
  },

  stats() {
    return http.get("/generators/stats");
  },

  available(params = {}) {
    return http.get("/generators/available", { params });
  },

  show(id) {
    return http.get(`/generators/${id}`);
  },
  
  timeline(id) {
  return http.get(`/generators/${id}/timeline`);
},

  create(payload) {
    return http.post("/generators", payload);
  },

  update(id, payload) {
    return http.patch(`/generators/${id}`, payload);
  },

  verify(id) {
    return http.patch(`/generators/${id}/verify`);
  },

  reject(id, reason) {
    return http.patch(`/generators/${id}/reject`, { reason });
  },

  transferOwnership(id, ownerId) {
    return http.patch(`/generators/${id}/transfer-owner`, { owner_id: ownerId });
  },

  destroy(id) {
    return http.delete(`/generators/${id}`);
  },

  qrCode(id) {
    return http.get(`/generators/${id}/qr`);
  },

  attachments(id) {
    return http.get(`/generators/${id}/attachments`);
  },

  storeAttachment(id, formData) {
    return http.post(`/generators/${id}/attachments`, formData);
  },

  destroyAttachment(attachmentId) {
    return http.delete(`/attachments/${attachmentId}`);
  },

  availableTechnicians(id) {
    return http.get(`/generators/${id}/available-technicians`);
  },

  linkedTechnicians(id) {
    return http.get(`/generators/${id}/technicians`);
  },

  linkTechnician(technicianId, generatorId) {
    return http.post(`/technicians/${technicianId}/generators/${generatorId}`);
  },

  unlinkTechnician(technicianId, generatorId) {
    return http.delete(`/technicians/${technicianId}/generators/${generatorId}`);
  },

  diagnostics(id) {
    return http.get(`/generators/${id}/diagnostics`);
  },

  storeDiagnosticReading(id, payload) {
    return http.post(`/generators/${id}/diagnostics`, payload);
  },
  healthReports(generatorId) {
    return http.get(`/generators/${generatorId}/health-reports`);
  },
  mapPoints() {
  return http.get("/generators/map-points");
},
  
};