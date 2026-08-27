const ROLE_HOME_ROUTES = {
  admin: "admin.dashboard",
  generator_owner: "owner.dashboard",
  subscriber: "subscriber.dashboard",
  technician: "technician.dashboard",
};

export function resolveHomeRouteName(authStore) {
  for (const [role, routeName] of Object.entries(ROLE_HOME_ROUTES)) {
    if (authStore.hasRole(role)) {
      return routeName;
    }
  }
  return "landing.home";
}
