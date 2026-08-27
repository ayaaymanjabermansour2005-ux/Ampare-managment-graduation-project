export default {
  downloadUrl(params = {}) {
    const query = new URLSearchParams(params).toString();
    return `/api/v1/owner-monthly-report/download${query ? `?${query}` : ""}`;
  },
};
