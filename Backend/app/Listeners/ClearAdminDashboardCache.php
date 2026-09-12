<?php

namespace App\Listeners;

use App\Services\AdminDashboardService;

class ClearAdminDashboardCache
{
    public function __construct(private readonly AdminDashboardService $dashboardService) {}

    public function handle(object $event): void
    {
        $this->dashboardService->clearCache();
    }
}
