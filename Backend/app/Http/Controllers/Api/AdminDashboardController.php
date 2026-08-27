<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Policies\AdminDashboardPolicy;
use App\Services\AdminDashboardService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    use ApiResponse;

    public function stats(AdminDashboardService $service, AdminDashboardPolicy $policy): JsonResponse
    {
        abort_unless($policy->view(auth()->user()), 403, __('admin.unauthorized'));

        return $this->success(message: __('admin.stats_message'), data: $service->stats());
    }

    public function paymentsFinancialSummary(AdminDashboardService $service, AdminDashboardPolicy $policy): JsonResponse
    {
        abort_unless($policy->view(auth()->user()), 403, __('admin.unauthorized'));

        return $this->success(message: __('admin.payments_financial_summary_message'), data: $service->paymentsFinancialSummary());
    }

    public function invoiceStatusBreakdown(AdminDashboardService $service, AdminDashboardPolicy $policy): JsonResponse
    {
        abort_unless($policy->view(auth()->user()), 403, __('admin.unauthorized'));

        return $this->success(message: __('admin.invoice_breakdown_message'), data: $service->invoiceStatusBreakdown());
    }

    public function alerts(AdminDashboardService $service, AdminDashboardPolicy $policy): JsonResponse
    {
        abort_unless($policy->view(auth()->user()), 403, __('admin.unauthorized'));

        return $this->success(message: __('admin.alerts_message'), data: $service->realtimeAlerts());
    }

    public function revenueChart(Request $request, AdminDashboardService $service, AdminDashboardPolicy $policy): JsonResponse
    {
        abort_unless($policy->view(auth()->user()), 403, __('admin.unauthorized'));

        return $this->success(
            message: __('admin.revenue_chart_message'),
            data: $service->revenueVsOutstandingDistribution(
                $request->integer('year') ?: null,
                $request->input('period', '6')
            )
        );
    }

    public function subscriberGrowthChart(AdminDashboardService $service, AdminDashboardPolicy $policy): JsonResponse
    {
        abort_unless($policy->view(auth()->user()), 403, __('admin.unauthorized'));

        return $this->success(message: __('admin.subscriber_growth_message'), data: $service->subscriberGrowthByMonth());
    }

    public function fuelChart(AdminDashboardService $service, AdminDashboardPolicy $policy): JsonResponse
    {
        abort_unless($policy->view(auth()->user()), 403, __('admin.unauthorized'));

        return $this->success(message: __('admin.fuel_chart_message'), data: $service->fuelPurchasesLast7Days());
    }

    public function maintenanceChart(AdminDashboardService $service, AdminDashboardPolicy $policy): JsonResponse
    {
        abort_unless($policy->view(auth()->user()), 403, __('admin.unauthorized'));

        return $this->success(message: __('admin.maintenance_chart_message'), data: $service->maintenanceAndFaultsByCity());
    }

    public function generatorsMap(AdminDashboardService $service, AdminDashboardPolicy $policy): JsonResponse
    {
        abort_unless($policy->view(auth()->user()), 403, __('admin.unauthorized'));

        return $this->success(message: __('admin.generators_map_message'), data: $service->generatorsMapPoints());
    }
}
