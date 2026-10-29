# Stage Approval Workflow

## Overview

Every requisition form passes through a three-stage approval process before it becomes a confirmed reservation. Each stage has its own set of approvers, and the form cannot move forward until the current stage is complete.

The three stages are:

1. **Approving Officers** — first-line review
2. **Final Approving Officer** — second-stage review and fee lock
3. **Issuing Officer** — final issuance after payment

A **Head Administrator** can override any stage and act at any point.

---

## The stages explained

### Stage 1 — Approving Officers

Approving Officers are typically department heads. Their job is to review the request and confirm that the resources being requested — facilities, equipment, services, and the stated purpose — fall under their department's responsibility.

**When a form is submitted:**

- One approval row is created for each relevant Approving Officer, based on which departments manage the requested resources.
- If no Approving Officers are identified for a form (for example, if none of the requested resources are department-managed), this stage is skipped automatically.

**What they can do:**

- Approve the request.
- Reject the request with an optional remark.

**What happens when the stage completes:**

- When the last Approving Officer acts, the form moves to Stage 2.
- All Final Approving Officers are notified that the form is ready for their review.

---

### Stage 2 — Final Approving Officer

Final Approving Officers review the request after Stage 1 has cleared. Their job is to confirm the request is acceptable and to lock in the final fee amount.

**What they can do:**

- Approve the request.
- Reject the request with an optional remark. Rejecting at this stage also closes the form, using the same remark as the closure reason.
- Edit fees, apply discounts, waive items, or adjust charges — up until the form is finalized.

**What happens when the stage completes:**

- When the last Final Approving Officer approves, the form is **automatically finalized**:
  - The fee is locked at its current value.
  - The status changes to **Awaiting Payment**.
  - The requester receives an email with payment instructions.
- Finalization is automatic because "Awaiting Payment" only makes sense once the amount is frozen. Allowing fee changes after the requester has been told what to pay would create confusion.

**If rejected at this stage:**

- The form moves to Rejected.
- It is closed automatically, using the same remark for both the rejection and the closure reason.

---

### Stage 3 — Issuing Officer

Issuing Officers handle the final step: confirming the reservation once payment has been verified. This stage only becomes relevant after the requester uploads proof of payment.

**What they can do:**

- Confirm the reservation by entering the official receipt number.
  - If they haven't yet approved the form, confirming the reservation also records their approval in the same action.
  - The form moves to **Reserved**.
- Close the reservation with an optional reason. This moves the form to **Completed**.

**What happens when the stage completes:**

- The form's status becomes Reserved.
- The requester receives a confirmation email with their official receipt.

---

## How the form moves between stages

The form advances automatically as each stage completes — no one has to manually push it forward.

| From | To | Trigger |
|---|---|---|
| Submitted | Pending Approval | Form is submitted |
| Stage 1 complete | Stage 2 ready | Last Approving Officer acts |
| Stage 2 complete | Awaiting Payment | Last Final Approving Officer approves (auto-finalize) |
| Payment uploaded | Verifying Payment | Requester uploads proof of payment |
| Stage 3 complete | Reserved | Issuing Officer confirms the reservation |

---

## Status lifecycle

The form's status reflects where it is in the workflow:

| Status | Meaning |
|---|---|
| **Pending Approval** | Waiting for Stage 1 or Stage 2 approvals |
| **Awaiting Payment** | Approved and finalized; waiting for the requester to pay |
| **Verifying Payment** | Payment submitted; waiting for Stage 3 to confirm |
| **Reserved** | Confirmed and scheduled |
| **Completed** | Closed successfully |
| **Rejected** | Rejected by an approver |
| **Cancelled** | Cancelled by the requester or an admin |

---

## Notifications

At each stage transition, the relevant people are notified:

- **When a form is submitted:** all Approving Officers responsible for the requested resources receive a notification.
- **When Stage 1 completes:** all Final Approving Officers receive a notification that the form is ready for their review.
- **When payment is uploaded:** all Issuing Officers receive a notification to confirm the reservation.
- **When the fee is finalized:** the requester receives an email with payment instructions.
- **When the reservation is confirmed:** the requester receives an email with the official receipt.

---

## Fee locking

The fee can be edited (added, discounted, or waived) by any admin **until the form is finalized**. Once finalized, the fee is locked and cannot be changed.

Finalization happens automatically when Stage 2 completes. This ensures the requester is only told to pay the final, agreed-upon amount.

---

## Special rules

### Head Administrator overrides

Head Administrators can act at any stage and at any status. They are effectively superadmins — they can confirm reservations, close forms, or approve requests regardless of where the form is in its lifecycle. When acting as an override, the interface displays a warning banner to make this clear.

### Rejections

- A rejection at Stage 1 only rejects the form. The form is not closed automatically.
- A rejection at Stage 2 rejects **and** closes the form in the same action.

### Approving after finalization

Approvers can still approve or reject a form after it has been finalized. Finalization locks the **fee**, not the approval workflow.

---

## Summary diagram

```
   Submit
     │
     ▼
┌─────────────────┐
│ Pending Approval │ ◄── Stage 1 (Approving Officers)
└─────────────────┘
     │  (all Stage 1 act)
     ▼
   Stage 2 (Final Approving Officers)
     │  (all Stage 2 approve)
     ▼
┌─────────────────┐
│ Awaiting Payment │  ← Fee locked, requester emailed
└─────────────────┘
     │  (requester uploads payment)
     ▼
┌──────────────────┐
│ Verifying Payment │ ◄── Stage 3 (Issuing Officers)
└──────────────────┘
     │  (issuing officer confirms)
     ▼
┌──────────┐
│ Reserved │
└──────────┘
     │  (admin closes)
     ▼
┌───────────┐
│ Completed │
└───────────┘

Side exits: Rejected (any stage), Cancelled (requester or admin)
```