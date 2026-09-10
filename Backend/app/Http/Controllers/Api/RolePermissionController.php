<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Policies\RolePermissionPolicy;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
{
    use ApiResponse;

    public function index(RolePermissionPolicy $policy): JsonResponse
    {
        abort_unless($policy->view(auth()->user()), 403, 'لا تملك صلاحية القيام بهذا الإجراء.');

        $roles = Role::with('permissions')->get()->map(fn ($role) => [
            'id' => $role->id,
            'name' => $role->name,
            'permissions' => $role->permissions->pluck('name'),
        ]);

        return $this->success(
            message: 'الأدوار والصلاحيات.',
            data: [
                'roles' => $roles,
                'all_permissions' => Permission::pluck('name'),
            ]
        );
    }

    public function sync(Request $request, Role $role, RolePermissionPolicy $policy): JsonResponse
    {
        abort_unless($policy->sync(auth()->user(), $role), 403, 'لا تملك صلاحية تعديل صلاحيات هذا الدور.');

        $validated = $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role->syncPermissions($validated['permissions']);

        activity()
            ->causedBy($request->user())
            ->performedOn($role)
            ->withProperties(['permissions' => $validated['permissions']])
            ->log('admin_updated_role_permissions');

        // FIX (تدقيق شامل — A8): كانت تُعاد نسخة Eloquent خام بدل نفس شكل
        // {id, name, permissions} المستخدَم بـ index() أعلاه لنفس المورد.
        $fresh = $role->fresh('permissions');

        return $this->success(
            message: 'تم تحديث صلاحيات الدور.',
            data: [
                'id' => $fresh->id,
                'name' => $fresh->name,
                'permissions' => $fresh->permissions->pluck('name'),
            ]
        );
    }
}
