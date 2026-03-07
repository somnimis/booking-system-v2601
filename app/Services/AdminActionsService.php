<?php

namespace App\Services;

class AdminActionsService
{
    // Status ID constants //

    // Pending/Tentative statuses
    public const STATUS_PENCIL_BOOKED = 1;
    public const STATUS_PENDING_APPROVAL = 2;
    public const STATUS_AWAITING_PAYMENT = 3;

    // Active/Ongoing Statuses
    public const STATUS_SCHEDULED = 4;
    public const STATUS_ONGOING = 5;
    public const STATUS_OVERDUE = 6;

    // Final/Terminal statuses
    public const STATUS_COMPLETED = 7;
    public const STATUS_REJECTED = 8;
    public const STATUS_CANCELLED = 9;

    // Business rule: Statuses that can be finalized
    private const CAN_FINALIZE_STATUSES = [
        self::STATUS_PENCIL_BOOKED,
        self::STATUS_PENDING_APPROVAL
    ];

    // Business rule: Final/terminal statuses (no actions)
    private const TERMINAL_STATUSES = [
        self::STATUS_COMPLETED,
        self::STATUS_REJECTED,
        self::STATUS_CANCELLED
    ];

    // Business rule: Statuses that show "Mark as Scheduled" button
    private const AWAITING_PAYMENT_STATUSES = [
        self::STATUS_AWAITING_PAYMENT
    ];

    // Business rule: Statuses that show dropdown with multiple options
    private const DROPDOWN_STATUSES = [
        self::STATUS_SCHEDULED => [
            'type' => 'dropdown',
            'label' => 'Mark as',
            'icon' => 'bi-chevron-down',
            'color' => 'primary',
            'options' => [
                ['label' => 'Ongoing', 'value' => 'ongoing', 'icon' => 'bi-play-circle'],
                ['label' => 'Overdue', 'value' => 'overdue', 'icon' => 'bi-exclamation-circle']
            ]
        ]
    ];

    // Individual button configurations
    private const BUTTON_CONFIGS = [
        self::STATUS_ONGOING => [
            'type' => 'button',
            'label' => 'Mark as Overdue',
            'icon' => 'bi-exclamation-circle',
            'modal' => 'markOverdueModal',
            'color' => 'warning'
        ],
        self::STATUS_OVERDUE => [
            'type' => 'button',
            'label' => 'Add Penalty Fee',
            'icon' => 'bi-cash-stack',
            'modal' => 'penaltyFeeModal',
            'color' => 'danger'
        ]
    ];

    // Default empty config
    private const DEFAULT_CONFIG = [
        'type' => 'none',
        'label' => '',
        'icon' => '',
        'modal' => null,
        'color' => 'secondary',
        'options' => []
    ];

    // Config for finalizable statuses
    private const FINALIZE_BUTTON = [
        'type' => 'button',
        'label' => 'Finalize',
        'icon' => 'bi-check-circle',
        'modal' => 'finalizeModal',
        'color' => 'primary',
        'options' => []
    ];

    // Config for awaiting payment status
    private const MARK_SCHEDULED_BUTTON = [
        'type' => 'button',
        'label' => 'Mark as Scheduled',
        'icon' => 'bi-calendar-event',
        'modal' => 'markScheduledModal',
        'color' => 'primary',
        'options' => []
    ];

    /**
     * Get action button configuration based on status ID
     * 
     * @param int $statusId
     * @return array
     */
    public function getConfig(int $statusId): array
    {
        // Check if status is terminal (no actions)
        if (in_array($statusId, self::TERMINAL_STATUSES)) {
            return self::DEFAULT_CONFIG;
        }

        // Check if status can be finalized
        if (in_array($statusId, self::CAN_FINALIZE_STATUSES)) {
            return self::FINALIZE_BUTTON;
        }

        // Check if status is awaiting payment
        if (in_array($statusId, self::AWAITING_PAYMENT_STATUSES)) {
            return self::MARK_SCHEDULED_BUTTON;
        }

        // Check if status has dropdown configuration
        if (array_key_exists($statusId, self::DROPDOWN_STATUSES)) {
            return self::DROPDOWN_STATUSES[$statusId];
        }

        // Check if status has button configuration
        if (array_key_exists($statusId, self::BUTTON_CONFIGS)) {
            return self::BUTTON_CONFIGS[$statusId];
        }

        // Fallback to default
        return self::DEFAULT_CONFIG;
    }

    /**
     * Get all available actions for a status (useful for debugging)
     */
    public function getAvailableActions(int $statusId): array
    {
        $config = $this->getConfig($statusId);

        if ($config['type'] === 'none') {
            return [];
        }

        if ($config['type'] === 'dropdown') {
            return array_column($config['options'], 'value');
        }

        return [$config['label']];
    }

    /**
     * Check if a status has any actions
     */
    public function hasActions(int $statusId): bool
    {
        return $this->getConfig($statusId)['type'] !== 'none';
    }
}