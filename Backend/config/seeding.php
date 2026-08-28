<?php

// SEC-005: local/testing-only dev admin credentials for RoleSeeder.
// Never consumed in production — RoleSeeder skips admin creation entirely
// there. Kept as its own config file (not inlined via env() in the seeder)
// so behavior is reliably overridable in tests via config(), matching the
// rest of this codebase's convention of only calling env() from config/.
return [
    'dev_admin_email' => env('DEV_ADMIN_EMAIL', 'admin@ampare.test'),
    'dev_admin_password' => env('DEV_ADMIN_PASSWORD'),
];
