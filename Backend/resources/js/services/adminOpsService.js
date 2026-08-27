import http from "./http";

export default {
  sendAnnouncement(payload) {
    return http.post("/admin/announcements", payload);
  },
  runBackup() {
    return http.post("/admin/backup/run");
  },
};