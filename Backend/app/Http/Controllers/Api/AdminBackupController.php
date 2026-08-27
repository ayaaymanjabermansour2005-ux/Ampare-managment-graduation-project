<?php

namespace App\Http\Controllers\Api;

use App\Jobs\RunBackupJob;
use App\Policies\AdminDashboardPolicy;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;


class AdminBackupController extends Controller
{
    use ApiResponse;

    public function run(Request $request, AdminDashboardPolicy $policy): JsonResponse
    {
        abort_unless($policy->view($request->user()), 403, __('admin.unauthorized'));

        RunBackupJob::dispatch();

        return $this->success(message: __('admin.backup_started_message'));
    }
}
