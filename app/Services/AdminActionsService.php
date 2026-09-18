<?php

namespace App\Services;

class AdminActionsService
{
    // Status ID constants //

    // this is more of a pseudo-code than anything to explain button behavior in request-view.

    // Pending/Tentative statuses
    public const STATUS_PENDING_APPROVAL = 1;
    public const STATUS_AWAITING_PAYMENT = 2;
    public const STATUS_VERIFYING_PAYMENT = 3;

    // Active/Ongoing Statuses
    public const STATUS_RESERVED = 4;

    // Final/Terminal statuses
    public const STATUS_COMPLETED = 5;
    public const STATUS_REJECTED = 6;
    public const STATUS_CANCELLED = 7;

    // Business rule: Statuses that can be finalized
    private const CAN_FINALIZE_STATUSES = [
        self::STATUS_PENDING_APPROVAL
    ];

    // Business rule: Final/terminal statuses (no actions)
    private const TERMINAL_STATUSES = [
        self::STATUS_COMPLETED,
        self::STATUS_REJECTED,
        self::STATUS_CANCELLED
    ];

    // Business rule: The status that ensures a form is finalized and can no longer be edited. Hides all buttons except the close form. The finalized objects in view-data should also be updated.
    private const FINALIZED_STATUSES = [
        self::STATUS_AWAITING_PAYMENT
    ];

    // Business rule: Statuses that show "Mark as Scheduled" button. It should always be this status for the button to work. 
    private const VERIFYING_PAYMENT_STATUSES = [
        self::STATUS_VERIFYING_PAYMENT
    ];


    // ====== Actions that should be put in 'More actions' dropdown ====== 
    // 1. finalize
    // 2. close form

    // ========== TLDR =============
    // Hide approve/reject/finalize/mark as scheduled buttons if status = awaiting payment. only close form is shown (not in the more options dropdown). 
    // show mark as scheduled button if status = verifying apyment, above the 'More option' button.
    // Hide all buttons on all terminal statuses

    // ======== ALTERNATIVES (for thinner and simpler features) =========
    // if hiding buttons is too complicated, just disable buttons and add a tooltip above it as to why its disabled!

}