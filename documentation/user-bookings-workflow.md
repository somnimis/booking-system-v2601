# Technical Documentation: Your Bookings & Official Permits

This document outlines the architecture, data flow, and key implementation details for the public-facing booking lookup and the official permit generation system.

## 1. Data Flow Architecture
To keep the frontend lean and secure, formatting is done **server-side**. The browser only handles rendering and interaction.

*   **Search (`your-bookings.blade.php`)**
    *   **Controller:** `UserRequisitionController@searchForms`
    *   **Service:** `UserBookingService@search`
    *   **View Partial:** `partials/booking-card.blade.php`
    *   *Flow:* User searches by code or email -> Service queries `RequisitionForm` (eager loads relationships) -> Formats schedule and fees via `RequisitionFormatterService` -> Renders partial HTML via Blade -> Returns HTML string to JS for injection.
*   **Permit Generation (`official-receipt.blade.php`)**
    *   **Controller:** `AdminApprovalController@generateOfficialReceipt`
    *   **Service:** `ReceiptService@generateReceiptData`
    *   *Flow:* Route parameter `requestId` -> Service builds a `$receiptData` array -> Splits facilities and equipment into a `$permits` array -> Blade iterates through permits to generate individual views.

## 2. Key Services
*   **`UserBookingService`:** Handles lookup logic and pre-renders the booking card HTML.
*   **`RequisitionFormatterService`:** The "source of truth" for formatting dates, calculating fees, and mapping relationships.
*   **`ReceiptService`:** Handles the heavy lifting for permits. It maps the `requisitionApprovals` to `actedBy` relationships and dynamically builds `permit_code` identifiers.

## 3. Frontend Rendering & Image Generation
*   **Master-Detail Layout:** Uses Bootstrap tabs. The sidebar lists the facilities/equipment, and the right pane shows the active permit.
*   **Image Download (Single):** Uses `html2canvas` to snapshot the active `#permitCapture-{i}` div. The download triggers via a dynamically created `<a>` tag.
*   **Batch Download (ZIP):** Uses `JSZip` and `FileSaver.js`. 
    *   *Crucial Logic:* Because the browser cannot screenshot hidden elements, the JS loops through the tabs, forces the tab to open (`tab.show()`), waits 500ms for the DOM to render, captures the image, adds it to the JSZip instance, and triggers a single `.zip` download at the end to bypass browser download blockers.

## 4. CSS Architecture
*   **`official-permit.css`:** Dedicated stylesheet for the permit page.
    *   *Note on assets:* Since this is an external CSS file, Laravel's `{{ asset() }}` helper cannot be used. Image paths must use relative routing (e.g., `url("../../assets/cpu-pic1.jpg")`).
*   **Grid System:** The permit details use a CSS Grid (`grid-template-columns: 1fr 1fr`). It is strictly formatted to 4 rows x 2 columns to avoid awkward empty white space.

## 5. Outstanding TODOs / Future Maintenance
*   If a request has *only* services (no physical facilities or equipment), the Permit tab view handles it gracefully by showing an empty state, as permits are currently only generated for physical locations/items.
*   If the `actedBy` relationship schema changes in the future, ensure `ReceiptService::getApprovingAdmins()` is updated accordingly.