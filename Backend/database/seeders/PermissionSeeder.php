<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public const PERMISSIONS = [
        // Users
        'users.view',
        'users.update',
        'users.create',
        'users.delete',
        'users.unlock',

        // Neighborhoods
        'neighborhoods.dashboard',

        // Activity Logs
        'activity-logs.view',

        // Owner Ratings
        'owner-ratings.create',
        'owner-ratings.view',

        // Generators
        'generators.view',
        'generators.create',
        'generators.update',
        'generators.delete',
        'generators.record',

        // Generator Schedules
        'generator-schedules.view',
        'generator-schedules.create',
        'generator-schedules.update',
        'generator-schedules.delete',

        // Subscriptions
        'subscriptions.view',
        'subscriptions.create',
        'subscriptions.updateStatus',
        'subscriptions.updateNotes',
        'subscriptions.transfer',

        // Subscription Service Requests
        'service-requests.view',
        'service-requests.create',
        'service-requests.review',
        'service-requests.cancel',

        // Subscriber Meters
        'subscriber-meters.view',
        'subscriber-meters.create',
        'subscriber-meters.update',
        'subscriber-meters.delete',

        // Subscribers
        'subscribers.updateBeneficiaryType',

        // Meter Readings
        'meter-readings.view',
        'meter-readings.create',
        'meter-readings.approve',
        'meter-readings.update',
        'meter-readings.delete',

        // Invoices
        'invoices.view',
        'invoices.cancel',
        'invoices.correct',
        'invoices.reissue',

        // Payments
        'payments.view',
        'payments.create',
        'payments.approve',
        'payments.reject',

        // Attachments
        'attachments.view',
        'attachments.create',
        'attachments.update',
        'attachments.delete',
        'attachments.restore',
        'attachments.force-delete',

        // Payment Methods
        'payment-methods.view',
        'payment-methods.create',
        'payment-methods.update',
        'payment-methods.delete',

        // Platform Commissions
        'platform-commissions.view',
        'platform-commissions.updateStatus',
        'platform-commissions.manage-settings',
        'commission-tiers.manage',

        // Faults
        'faults.view',
        'faults.create',
        'faults.updateStatus',
        'faults.override_status',
        'faults.delete',

        // Fault Predictions
        'fault-predictions.view',
        'fault-predictions.create',
        'fault-predictions.confirm',
        'fault-predictions.dismiss',

        // Complaints
        'complaints.view',
        'complaints.create',
        'complaints.resolve',
        'complaints.delete',

        // Offers
        'offers.view',
        'offers.create',
        'offers.update',
        'offers.cancel',
        'offers.delete',

        // Conversations & Messages
        'conversations.view',
        'conversations.start',
        'conversations.send-message',
        'conversations.delete',

        // AI Chat
        'ai-chat.use',

        // Technicians
        'technicians.view',
        'technicians.create',
        'technicians.update',
        'technicians.delete',

        // Technician Tasks
        'technician-tasks.view',
        'technician-tasks.create',
        'technician-tasks.assign',
        'technician-tasks.start',
        'technician-tasks.submit',
        'technician-tasks.review',
        'technician-tasks.cancel',

        // Technician Ratings
        'technician-ratings.create',

        // Technician Payments (Owner <-> Technician, excluded from platform commission)
        'technician-payments.view',
        'technician-payments.create',
        'technician-payments.approve',
        'technician-payments.reject',

        // Subscription Meter Transfer Requests
        'subscription-meter-transfers.view',
        'subscription-meter-transfers.create',
        'subscription-meter-transfers.approve',
        'subscription-meter-transfers.reject',

    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'sanctum',
            ]);
        }
    }
}
