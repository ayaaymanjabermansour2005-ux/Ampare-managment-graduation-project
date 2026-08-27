<?php

/**
 * Excel export strings (column headings + status labels) — English.
 * Used via App\Support\ExportLabel and app/Exports/*.php so the actual
 * exported spreadsheet content changes with the request locale
 * (Accept-Language header, or the `?lang=` query param used for plain
 * link-based downloads) instead of always being hard-coded Arabic.
 */
return [

    'headings' => [
        'id' => 'ID',
        'name' => 'Name',
        'email' => 'Email',
        'phone' => 'Phone',
        'status' => 'Status',
        'created_at' => 'Created At',
        'notes' => 'Notes',

        // Subscriptions
        'subscription_id' => 'Subscription ID',
        'subscriber' => 'Subscriber',
        'generator' => 'Generator',
        'generator_owner' => 'Generator Owner',
        'price_per_kw' => 'Agreed Price (per kW)',
        'currency' => 'Currency',
        'start_date' => 'Start Date',
        'end_date' => 'End Date',

        // Payments
        'payment_id' => 'Payment ID',
        'invoice_id' => 'Invoice ID',
        'source' => 'Source',
        'amount' => 'Amount',
        'amount_ils' => 'Amount (ILS)',
        'exchange_rate' => 'Exchange Rate',
        'payment_method' => 'Payment Method',
        'paid_at' => 'Paid At',
        'submitted_at' => 'Submitted At',
        'admin_adjustment' => 'Admin Adjustment',

        // Subscribers (users)
        'beneficiary_type' => 'Beneficiary Type',
        'linked_generators_count' => 'Linked Generators',
        'active_subscriptions' => 'Active Subscriptions',
        'outstanding_balance_ils' => 'Outstanding Balance (ILS)',
        'joined_at' => 'Joined At',

        // Invoices
        'base_amount' => 'Base Amount',
        'discount_amount' => 'Discount',
        'final_amount' => 'Final Amount',
        'final_amount_ils' => 'Final Amount (ILS)',
        'due_date' => 'Due Date',
        'issued_at' => 'Issued At',

        // Generators
        'generator_id' => 'Generator ID',
        'generator_name' => 'Generator Name',
        'owner' => 'Owner',
        'city' => 'City',
        'capacity_kw' => 'Capacity (kW)',
        'fuel_type' => 'Fuel Type',
        'fuel_percentage' => 'Current Fuel Level (%)',
        'active_subscribers' => 'Active Subscribers',
        'monthly_revenue_ils' => 'Monthly Revenue (ILS)',
        'added_at' => 'Added At',

        // Offers
        'offer_id' => 'Offer ID',
        'title' => 'Title',
        'discount' => 'Discount',
        'target' => 'Target Audience',

        // Faults
        'fault_id' => 'Fault ID',
        'priority' => 'Priority',
        'reported_by' => 'Reported By',
        'reported_at' => 'Reported At',
        'verified_by' => 'Verified By',
        'resolved_at' => 'Resolved At',

        // Complaints
        'complaint_id' => 'Complaint ID',
        'subject' => 'Subject',
        'submitted_by' => 'Submitted By',
        'related_to' => 'Related To',
        'resolved_by' => 'Resolved By',

        // Login logs
        'log_id' => 'ID',
        'event' => 'Event',
        'user' => 'User',
        'ip' => 'IP Address',
        'date' => 'Date',

        // Meter readings
        'reading_id' => 'Reading #',
        'meter_number' => 'Meter Number',
        'reading_date' => 'Reading Date',
        'previous_reading' => 'Previous Reading',
        'current_reading' => 'Current Reading',
        'consumed_kw' => 'Consumption (kW)',
        'created_by' => 'Created By',

        // Users
        'role' => 'Role',

        // Technicians
        'technician_id' => 'Technician ID',
        'assigned_tasks_count' => 'Assigned Tasks',
    ],

    'values' => [
        'admin_adjustment' => 'Admin Adjustment',
        'unknown_user' => 'Unknown',
        'event_login_succeeded' => 'Login Succeeded',
        'event_login_failed' => 'Login Failed',
    ],

    // Same short keys as Complaint::COMPLAINABLE_TYPES (Generator/Fault/...)
    'related_to' => [
        'Generator' => 'Generator',
        'Fault' => 'Fault',
        'Subscription' => 'Subscription',
        'Invoice' => 'Invoice',
        'Payment' => 'Payment',
    ],

    // exports.enum.<EnumBasename>.<case value>
    'enum' => [
        'SubscriptionStatus' => [
            'pending' => 'Pending',
            'active' => 'Active',
            'suspended' => 'Suspended',
            'cancelled' => 'Cancelled',
            'rejected' => 'Rejected',
        ],
        'OperatingSchedule' => [
            'day' => 'Day period',
            'night' => 'Night period',
            '24h' => '24 hours',
            'custom' => 'Custom period',
        ],
        'MeterReadingStatus' => [
            'pending_approval' => 'Pending Approval',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
        ],
        'InvoiceStatus' => [
            'pending' => 'Pending',
            'partially_paid' => 'Partially Paid',
            'paid' => 'Paid',
            'overdue' => 'Overdue',
            'cancelled' => 'Cancelled',
        ],
        'PaymentStatus' => [
            'pending' => 'Pending Review',
            'needs_correction' => 'Needs Correction',
            'paid' => 'Paid',
            'rejected' => 'Rejected',
            'cancelled' => 'Cancelled',
        ],
        'OfferStatus' => [
            'active' => 'Active',
            'cancelled' => 'Cancelled',
        ],
        'FaultStatus' => [
            'pending_verification' => 'Pending Verification',
            'verified' => 'Verified',
            'rejected' => 'Rejected (invalid report)',
            'in_repair' => 'In Repair',
            'resolved' => 'Resolved',
            'closed' => 'Closed',
        ],
        'ComplaintStatus' => [
            'pending' => 'Pending',
            'in_progress' => 'In Progress',
            'resolved' => 'Resolved',
        ],
        'UserStatus' => [
            'active' => 'Active',
            'inactive' => 'Inactive',
            'suspended' => 'Suspended',
            'pending_review' => 'Pending Admin Review',
        ],
        'GeneratorStatus' => [
            'active' => 'Active',
            'inactive' => 'Inactive',
            'maintenance' => 'Under Maintenance',
            'pending_verification' => 'Pending Admin Approval',
            'rejected' => 'Rejected',
        ],
        'BeneficiaryType' => [
            'normal' => 'Normal',
            'special' => 'Donation Beneficiary',
        ],
        'PaymentSource' => [
            'subscriber' => 'Subscriber Payment',
            'adjustment' => 'Admin Adjustment',
            'gateway' => 'Payment Gateway',
        ],
        'PaymentMethodType' => [
            'bank' => 'Bank Transfer',
            'wallet' => 'E-Wallet',
            'cash' => 'Cash',
        ],
        'FuelType' => [
            'diesel' => 'Diesel',
            'gas' => 'Gas',
            'petrol' => 'Petrol',
            'dual' => 'Dual (Diesel/Gas)',
        ],
        'OfferTargetMode' => [
            'all' => 'All Subscribers',
            'beneficiary' => 'By Beneficiary Category',
            'selected' => 'Manually Selected Subscribers',
        ],
        'OfferDiscountType' => [
            'percentage' => 'Percentage',
            'fixed' => 'Fixed Amount',
        ],
        'FaultPriority' => [
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
            'critical' => 'Critical',
        ],
        'Role' => [
            'admin' => 'Admin',
            'generator_owner' => 'Generator Owner',
            'subscriber' => 'Subscriber',
            'technician' => 'Technician',
        ],
        'TechnicianStatus' => [
            'active' => 'Active',
            'inactive' => 'Inactive',
            'suspended' => 'Suspended',
        ],
    ],

];
