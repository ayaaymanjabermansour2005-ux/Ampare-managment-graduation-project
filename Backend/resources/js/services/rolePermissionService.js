import http from "./http";

export default {
  index() {
    return http.get("/admin/roles-permissions");
  },
  sync(roleId, permissions) {
    return http.patch(`/admin/roles/${roleId}/permissions`, { permissions });
  },
};
