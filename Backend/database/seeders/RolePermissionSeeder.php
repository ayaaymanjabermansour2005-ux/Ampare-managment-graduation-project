<?php

namespace Database\Seeders;

use App\Enums\Role as RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    private const ROLE_PERMISSIONS = [
        RoleEnum::GENERATOR_OWNER->value => [
            'users.view',
            'users.update',
            'generators.view',
            'generators.create',
            'generators.update',
            'generators.delete',
            'generators.record',
            'generator-schedules.view',
            'generator-schedules.create',
            'generator-schedules.update',
            'generator-schedules.delete',
            'subscriptions.view',
            'subscriptions.view',
            'subscriptions.create',
            'subscriptions.updateStatus',
            'subscriptions.transfer',
            'service-requests.view',
            'service-requests.review',
            'subscribers.updateBeneficiaryType',
            'meter-readings.view',
            'meter-readings.create',
            'meter-readings.approve',
            'invoices.view',
            'payments.view',
            'payments.approve',
            'payments.reject',
            'attachments.view',
            'attachments.delete',
            'payment-methods.view',
            'payment-methods.create',
            'payment-methods.update',
            'payment-methods.delete',
            'platform-commissions.view',
            'faults.view',
            'faults.create',
            'faults.updateStatus',
            'fault-predictions.view',
            'fault-predictions.confirm',
            'fault-predictions.dismiss',
            'complaints.view',
            'complaints.create',
            'complaints.resolve',
            'offers.view',
            'offers.create',
            'offers.update',
            'offers.cancel',
            'conversations.view',
            'conversations.start',
            'conversations.send-message',
            'conversations.delete',
            'ai-chat.use',
            'technicians.view',
            'technicians.create',
            'technicians.update',
            'technicians.delete',
            'technician-tasks.view',
            'technician-tasks.create',
            'technician-tasks.assign',
            'technician-tasks.review',
            'technician-tasks.cancel',
            'technician-tasks.submit',
            'technician-ratings.create',
            'owner-ratings.view',
            'technician-payments.view',
            'technician-payments.create',
            'subscription-meter-transfers.view',
            'subscription-meter-transfers.approve',
            'subscription-meter-transfers.reject',

        ],

        RoleEnum::SUBSCRIBER->value => [
            'users.view',
            'users.update',
            'generators.view',
            'generators.view',
            'generator-schedules.view',
            'subscriber-meters.view',
            'subscriber-meters.view',
            'subscriber-meters.create',
            'subscriber-meters.update',
            'subscriber-meters.delete',
            'subscriptions.view',
            'subscriptions.create',
            'service-requests.view',
            'service-requests.create',
            'service-requests.cancel',
            'invoices.view',
            'payments.view',
            'payments.create',
            'meter-readings.view',
            'attachments.view',
            'attachments.delete',
            'attachments.create',
            'faults.view',
            'faults.create',
            'complaints.view',
            'complaints.create',
            'offers.view',
            'conversations.view',
            'conversations.start',
            'conversations.send-message',
            'conversations.delete',
            'ai-chat.use',
            'owner-ratings.create',
            'subscription-meter-transfers.view',
            'subscription-meter-transfers.create',
        ],

        RoleEnum::TECHNICIAN->value => [
            'users.view',
            'users.update',
            'generators.view',
            'generators.record',
            'technicians.view',
            'attachments.view',
            'technician-tasks.view',
            'technician-tasks.start',
            'technician-tasks.submit',
            'subscriptions.view',
            'meter-readings.view',
            'meter-readings.create',
            'conversations.view',
            'conversations.start',
            'conversations.send-message',
            'conversations.delete',
            'ai-chat.use',
            'technician-payments.view',
            'technician-payments.approve',
            'technician-payments.reject',
            'payment-methods.view',
            'payment-methods.create',
            'payment-methods.update',
            'payment-methods.delete',
            'complaints.view',
            'complaints.create',
            'faults.view',
            'faults.create',
        ],
    ];

    public function run(): void
    {
        $admin = Role::updateOrCreate(
            [
                'name' => RoleEnum::ADMIN->value,
                'guard_name' => 'sanctum',
            ]
        );

        $admin->syncPermissions(Permission::where('guard_name', 'sanctum')->pluck('name'));

        foreach (self::ROLE_PERMISSIONS as $roleName => $permissions) {
            $role = Role::where('name', $roleName)
                ->where('guard_name', 'sanctum')
                ->firstOrFail();

            $role->syncPermissions($permissions);
        }
    }
}
