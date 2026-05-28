@extends('layouts.admin')

@section('title', 'Equipment Scanner')

@section('content')

    <style>
        /* Scanner layout */
        #scannerContainer {
            display: flex;
            flex-direction: column;
            min-height: calc(100vh - 72px);
            justify-content: flex-start;
            align-items: center;
            padding: 1rem;
            gap: 1rem;
        }

        .scanner-box {
            padding: 1.5rem;
            width: 100%;
            max-width: 700px;
            text-align: center;
        }

        #reader {
            width: 100%;
            max-width: 420px;
            height: 300px;
            margin: 0.75rem auto;
            border: 3px solid #fff;
            border-radius: 12px;
            overflow: hidden;
            background: #000;
        }

        .btn-controls {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            margin-top: 0.75rem;
        }

        .button-small {
            padding: 0.45rem 0.75rem;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
        }

        #stop-scan {
            background: #ff6b6b;
            color: white;
        }

        #resume-scan {
            background: #ffd43b;
            color: #012952;
        }

        /* Info Box */
        .info-box {
            background: #fff;
            color: #333;
            border-radius: 16px;
            padding: 1.25rem;
            width: 100%;
            max-width: 700px;
            margin-top: 1rem;
            box-shadow: 0 -6px 20px rgba(0, 0, 0, 0.1);
        }

        .info-label {
            font-weight: bold;
        }

        .info-value {
            float: right;
        }

        .info-item {
            margin: 0.35rem 0;
            display: flex;
            justify-content: space-between;
        }

        .badge-status {
            padding: 0.3rem 0.75rem;
            border-radius: 12px;
            font-weight: 700;
        }

        .status-available {
            background: #28a745;
            color: white;
        }

        .status-in-use {
            background: #ffc107;
            color: #222;
        }

        .status-maintenance {
            background: #17a2b8;
            color: white;
        }

        .status-damaged {
            background: #dc3545;
            color: white;
        }

        /* Condition badges */
        .condition-new {
            background: #28a745;
            color: white;
        }

        .condition-good {
            background: #20c997;
            color: white;
        }

        .condition-fair {
            background: #ffc107;
            color: #222;
        }

        .condition-maintenance {
            background: #fd7e14;
            color: white;
        }

        .condition-damaged {
            background: #dc3545;
            color: white;
        }

        .condition-in-use {
            background: #6f42c1;
            color: white;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            color: #012952;
        }

        .modal-content h3 {
            margin-top: 0;
        }

        .modal-content select,
        .modal-content input,
        .modal-content textarea {
            width: 100%;
            padding: 8px;
            margin: 8px 0 16px;
            border: 1px solid #ddd;
            border-radius: 6px;
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .modal-buttons button {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        #confirm-release {
            background: #28a745;
            color: white;
        }

        #confirm-return {
            background: #17a2b8;
            color: white;
        }

        .cancel-modal {
            background: #6c757d;
            color: white;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        hr {
            margin: 1rem 0;
        }
    </style>

    <main>
        <div id="scannerContainer">
            <!-- Scanner Section -->
            <div class="scanner-box">
                <h2 class="fw-bold">Equipment Scanner</h2>
                <p>Scan equipment barcode to release or return</p>

                <div id="reader"></div>

                <div class="manual-input-section"
                    style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.2);">
                    <p style="margin-bottom: 0.5rem;">Or enter barcode manually:</p>
                    <div style="display: flex; gap: 0.5rem;">
                        <input type="text" id="manual-barcode" placeholder="Enter barcode"
                            style="flex: 1; padding: 0.6rem; border-radius: 8px; border: none; font-size: 1rem;">
                        <button id="manual-submit" class="button-small"
                            style="background: #4a90e2; color: white;">Lookup</button>
                    </div>
                </div>

                <div class="btn-controls">
                    <button id="stop-scan" class="button-small" type="button">Stop Scan</button>
                    <button id="resume-scan" class="button-small" type="button" style="display:none;">Resume Scan</button>
                </div>

                <div id="scan-result" class="mt-3">
                    Scanned: <strong><span id="scanned-value">None</span></strong>
                </div>
            </div>

            <!-- Equipment Details Section -->
            <div class="info-box" id="equipment-info" style="display:none;">
                <h5>Equipment Item Details</h5>
                <div class="info-item"><span class="info-label">Item Name:</span> <span class="info-value"
                        id="item-name"></span></div>
                <div class="info-item"><span class="info-label">Equipment Type:</span> <span class="info-value"
                        id="eq-name"></span></div>
                <div class="info-item"><span class="info-label">Condition:</span> <span class="info-value"><span
                            id="item-condition" class="badge-status"></span></span></div>
                <div class="info-item"><span class="info-label">Barcode:</span> <span class="info-value"
                        id="item-barcode"></span></div>

                <div id="active-transaction-info"
                    style="display:none; background:#f8f9fa; padding:10px; border-radius:8px; margin:10px 0;">
                    <div class="info-item"><span class="info-label">Active Request:</span> <span class="info-value"
                            id="active-request-id"></span></div>
                    <div class="info-item"><span class="info-label">Released At:</span> <span class="info-value"
                            id="active-released-at"></span></div>
                    <div class="info-item"><span class="info-label">Destination:</span> <span class="info-value"
                            id="active-destination"></span></div>
                </div>

                <hr>

                <div id="current-bookings" style="margin-top: 10px; font-size: 0.85rem;">
                    <span class="info-label">Current Bookings:</span>
                    <div id="bookings-list"></div>
                </div>

                <!-- Action buttons -->
                <div style="margin-top: 15px; display: flex; gap: 10px; justify-content: center;">
                    <button id="release-btn" class="button-small" style="background: #28a745; color: white;">Release
                    </button>
                    <button id="return-btn" class="button-small" style="background: #17a2b8; color: white;">Return</button>
                </div>
            </div>
        </div>

        <!-- Release Modal -->
        <div id="release-modal" class="modal">
            <div class="modal-content">
                <h3>Release Equipment</h3>
                <p><strong>Item:</strong> <span id="release-item-name"></span></p>
                <p><strong>Barcode:</strong> <span id="release-barcode"></span></p>

                <div class="form-group">
                    <label for="request-select">Select Request Form:</label>
                    <select id="request-select">
                        <option value="">-- No request/ad-hoc release --</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="facility-id">Select Destination:</label>
                    <select id="facility-id">
                        <option value="">-- Same as request or manual entry --</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="destination-name">Manual Location:</label>
                    <input type="text" id="destination-name" placeholder="e.g., Off-campus event, Room 123">
                </div>

                <div class="form-group">
                    <label for="release-notes">Release Notes (Optional):</label>
                    <textarea id="release-notes" rows="3" placeholder="Any notes about this release..."></textarea>
                </div>

                <div class="modal-buttons">
                    <button class="cancel-modal">Cancel</button>
                    <button id="confirm-release">Confirm Release</button>
                </div>
            </div>
        </div>

        <!-- Return Modal -->
        <div id="return-modal" class="modal">
            <div class="modal-content">
                <h3>Return Equipment</h3>
                <p><strong>Item:</strong> <span id="return-item-name"></span></p>
                <p><strong>Barcode:</strong> <span id="return-barcode"></span></p>
                <p><strong>Request ID:</strong> <span id="return-request-id"></span></p>
                <p><strong>Released At:</strong> <span id="return-released-at"></span></p>
                <p><strong>Destination:</strong> <span id="return-destination"></span></p>

                <div class="form-group">
                    <label for="return-condition">Condition on Return:</label>
                    <select id="return-condition" required>
                        <option value="">-- Select condition --</option>
                        <option value="1">New</option>
                        <option value="2">Good</option>
                        <option value="3">Fair</option>
                        <option value="4">Needs Maintenance</option>
                        <option value="5">Damaged</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="return-notes">Return Notes (Optional):</label>
                    <textarea id="return-notes" rows="3" placeholder="Any notes about damage, issues, etc..."></textarea>
                </div>

                <div class="modal-buttons">
                    <button class="cancel-modal">Cancel</button>
                    <button id="confirm-return">Confirm Return</button>
                </div>
            </div>
        </div>
    </main>

@endsection

@section('scripts')
    <script src="{{ asset('js/admin/toast.js') }}"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // DOM Elements
            const resultSpan = document.getElementById("scanned-value");
            const infoBox = document.getElementById("equipment-info");
            const eqName = document.getElementById("eq-name");
            const itemName = document.getElementById("item-name");
            const itemCondition = document.getElementById("item-condition");
            const itemBarcode = document.getElementById("item-barcode");
            const bookingsList = document.getElementById("bookings-list");
            const activeTransactionInfo = document.getElementById("active-transaction-info");
            const activeRequestId = document.getElementById("active-request-id");
            const activeReleasedAt = document.getElementById("active-released-at");
            const activeDestination = document.getElementById("active-destination");

            const stopBtn = document.getElementById("stop-scan");
            const resumeBtn = document.getElementById("resume-scan");
            const releaseBtn = document.getElementById("release-btn");
            const returnBtn = document.getElementById("return-btn");

            // Manual barcode input
            const manualBarcodeInput = document.getElementById("manual-barcode");
            const manualSubmitBtn = document.getElementById("manual-submit");

            manualSubmitBtn.addEventListener("click", async () => {
                let barcode = manualBarcodeInput.value.trim();
                if (!barcode) {
                    showToast("Please enter a barcode", "error");
                    return;
                }

                // Only clean whitespace - no EQ- requirement
                barcode = barcode.replace(/\s/g, '');

                resultSpan.textContent = barcode;

                // Stop scanner if running
                if (scannerRunning) {
                    try {
                        await html5QrCode.stop();
                    } catch (e) { }
                    scannerRunning = false;
                    stopBtn.style.display = "none";
                    resumeBtn.style.display = "inline-block";
                }

                await fetchEquipmentDetails(barcode);
                manualBarcodeInput.value = "";
            });

            // Allow Enter key to submit
            manualBarcodeInput.addEventListener("keypress", (e) => {
                if (e.key === "Enter") {
                    e.preventDefault();
                    manualSubmitBtn.click();
                }
            });

            // Modals
            const releaseModal = document.getElementById("release-modal");
            const returnModal = document.getElementById("return-modal");

            const token = localStorage.getItem("adminToken");
            let currentItemData = null;  // Stores the full item data from scan
            let currentActiveTransaction = null;

            const html5QrCode = new Html5Qrcode("reader");
            let scannerRunning = false;

            // Condition mapping
            const conditionMap = {
                1: { name: "New", class: "condition-new" },
                2: { name: "Good", class: "condition-good" },
                3: { name: "Fair", class: "condition-fair" },
                4: { name: "Needs Maintenance", class: "condition-maintenance" },
                5: { name: "Damaged", class: "condition-damaged" },
                6: { name: "In Use", class: "condition-in-use" }
            };

            function getConditionBadge(conditionId) {
                const cond = conditionMap[conditionId] || { name: "Unknown", class: "" };
                return `<span class="badge-status ${cond.class}">${cond.name}</span>`;
            }

            // Load facilities for dropdown
            async function loadFacilities() {
                try {
                    const response = await fetch('/api/facilities', {
                        headers: { 'Authorization': `Bearer ${token}` }
                    });
                    const data = await response.json();
                    const select = document.getElementById("facility-id");

                    if (data.data && data.data.length) {
                        data.data.forEach(facility => {
                            const option = document.createElement("option");
                            option.value = facility.facility_id;
                            option.textContent = facility.facility_name;
                            select.appendChild(option);
                        });
                    }
                } catch (error) {
                    console.error("Error loading facilities:", error);
                }
            }

            // Scan endpoint - uses existing POST /api/scanner/scan
            async function fetchEquipmentDetails(barcode) {
                try {
                    const response = await fetch(`/api/scanner/scan`, {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        },
                        body: JSON.stringify({ barcode: barcode })
                    });

                    const data = await response.json();

                    if (!response.ok || data.status === 'error') {
                        throw new Error(data.message || "Equipment not found");
                    }

                    // Store data for later use
                    currentItemData = data;
                    currentActiveTransaction = data.item?.current_transaction || null;

                    if (data.reserved_requisitions && data.reserved_requisitions.length > 0) {
                        const select = document.getElementById("request-select");
                        select.innerHTML = '<option value="">-- Select a request (optional) --</option>';

                        data.reserved_requisitions.forEach(req => {
                            const option = document.createElement("option");
                            option.value = req.request_id;
                            const requester = req.organization_name || `${req.first_name} ${req.last_name}`;
                            option.textContent = `R-${req.request_id} - ${requester} (${req.start_date} to ${req.end_date}) - Qty: ${req.quantity}`;
                            select.appendChild(option);
                        });
                    } else {
                        const select = document.getElementById("request-select");
                        select.innerHTML = '<option value="">-- No reserved requests for this equipment --</option>';
                    }

                    // Populate item details
                    const item = data.item;
                    const equipment = item.equipment_details;

                    itemName.textContent = item.item_name || "N/A";
                    eqName.textContent = equipment?.name || "N/A";
                    itemCondition.innerHTML = getConditionBadge(item.condition_id);
                    itemBarcode.textContent = item.barcode_number || barcode;

                    // Show active transaction if exists
                    if (currentActiveTransaction) {
                        activeTransactionInfo.style.display = "block";
                        activeRequestId.textContent = currentActiveTransaction.request_id
                            ? `R-${currentActiveTransaction.request_id}`
                            : 'No request associated';

                        // Format the date properly
                        let releasedDate = 'N/A';
                        if (currentActiveTransaction.released_at) {
                            const date = new Date(currentActiveTransaction.released_at);
                            releasedDate = date.toLocaleString('en-US', {
                                year: 'numeric',
                                month: 'short',
                                day: 'numeric',
                                hour: 'numeric',
                                minute: '2-digit'
                            });
                        }
                        activeReleasedAt.textContent = releasedDate;

                        activeDestination.textContent = currentActiveTransaction.destination_name
                            || currentActiveTransaction.facility_name
                            || 'Not specified';

                        // Disable release button if item is already released
                        releaseBtn.disabled = true;
                        releaseBtn.style.opacity = "0.5";
                        releaseBtn.style.cursor = "not-allowed";
                        returnBtn.disabled = false;
                        returnBtn.style.opacity = "1";
                    } else {
                        // NO active transaction - item is available for release
                        activeTransactionInfo.style.display = "none";
                        releaseBtn.disabled = false;
                        releaseBtn.style.opacity = "1";
                        releaseBtn.style.cursor = "pointer";
                        returnBtn.disabled = true;
                        returnBtn.style.opacity = "0.5";
                        returnBtn.style.cursor = "pointer";  // Change from not-allowed to default
                    }

                    // Show current bookings (only active/reserved ones)
                    if (data.current_bookings && data.current_bookings.length > 0) {
                        bookingsList.innerHTML = "";
                        data.current_bookings.forEach(booking => {
                            // Only show if status is Reserved or Ongoing/Approved
                            const bookingStatus = booking.status?.toLowerCase() || '';
                            if (bookingStatus === 'reserved' || bookingStatus === 'approved' || bookingStatus === 'ongoing') {
                                const div = document.createElement("div");
                                div.style.fontSize = "0.8rem";
                                div.style.marginTop = "4px";
                                div.innerHTML = `• ${booking.requester}: ${booking.start_date} to ${booking.end_date} (Qty: ${booking.quantity})`;
                                bookingsList.appendChild(div);
                            }
                        });

                        // If no active bookings after filtering, show message
                        if (bookingsList.children.length === 0) {
                            bookingsList.innerHTML = "<em>No active bookings</em>";
                        }
                    } else {
                        bookingsList.innerHTML = "<em>No active bookings</em>";
                    }

                    infoBox.style.display = "block";
                    showToast('Equipment found successfully!', 'success');

                } catch (error) {
                    console.error("Error fetching equipment:", error);
                    itemName.textContent = "Not Found";
                    eqName.textContent = "-";
                    itemCondition.innerHTML = getConditionBadge(null);
                    itemBarcode.textContent = barcode;
                    infoBox.style.display = "block";
                    showToast(error.message || 'Equipment not found in database', 'error');
                }
            }

            // Release/Borrow - uses existing POST /api/scanner/borrow
            async function handleRelease() {
                if (!currentItemData) return;

                const requestId = document.getElementById("request-select").value;
                const facilityId = document.getElementById("facility-id").value || null;
                const destinationName = document.getElementById("destination-name").value || null;
                const notes = document.getElementById("release-notes").value || null;

                // Validate location
                if (!facilityId && !destinationName) {
                    showToast("Please select a facility or enter a destination name", "error");
                    return;
                }

                try {
                    const response = await fetch('/api/scanner/borrow', {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        },
                        body: JSON.stringify({
                            barcode: currentItemData.item.barcode_number,
                            request_id: requestId || null,  // Allow null
                            facility_id: facilityId,
                            destination_name: destinationName,
                            notes: notes
                        })
                    });

                    const data = await response.json();

                    if (data.status === 'success') {
                        showToast('Item released successfully!', 'success');
                        closeModals();
                        await fetchEquipmentDetails(currentItemData.item.barcode_number);
                    } else {
                        showToast('Error: ' + (data.message || 'Failed to release item'), 'error');
                    }
                } catch (error) {
                    console.error('Release error:', error);
                    showToast('Failed to process release request', 'error');
                }
            }

            // Get return modal data - uses existing POST /api/scanner/return
            async function prepareReturn() {
                if (!currentItemData || !currentActiveTransaction) {
                    showToast("No active transaction found for this item", "error");
                    return;
                }

                try {
                    const response = await fetch('/api/scanner/return', {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        },
                        body: JSON.stringify({
                            barcode: currentItemData.item.barcode_number
                        })
                    });

                    const data = await response.json();

                    if (data.status === 'success') {
                        // Format the released date properly
                        let formattedReleasedDate = 'N/A';
                        if (data.transaction.released_at) {
                            const date = new Date(data.transaction.released_at);
                            formattedReleasedDate = date.toLocaleString('en-US', {
                                year: 'numeric',
                                month: 'short',
                                day: 'numeric',
                                hour: 'numeric',
                                minute: '2-digit'
                            });
                        }

                        // Format request ID display
                        let requestIdDisplay = 'N/A';
                        if (data.transaction.request_id) {
                            requestIdDisplay = `R-${data.transaction.request_id}`;
                        }

                        // Format destination display
                        let destinationDisplay = data.transaction.destination_name || 'Not specified';

                        // Populate return modal
                        document.getElementById("return-item-name").textContent = data.item.item_name;
                        document.getElementById("return-barcode").textContent = data.item.barcode;
                        document.getElementById("return-request-id").textContent = requestIdDisplay;
                        document.getElementById("return-released-at").textContent = formattedReleasedDate;
                        document.getElementById("return-destination").textContent = destinationDisplay;

                        // Store transaction ID for later
                        returnModal.setAttribute("data-transaction-id", data.transaction.id);
                        returnModal.style.display = "flex";
                    } else {
                        showToast('Error: ' + (data.message || 'Failed to prepare return'), 'error');
                    }
                } catch (error) {
                    console.error('Return prepare error:', error);
                    showToast('Failed to process return request', 'error');
                }
            }

            // Complete return and update item - uses existing POST /api/scanner/update-item/{itemId}
            async function handleReturn() {
                const conditionId = document.getElementById("return-condition").value;
                if (!conditionId) {
                    showToast("Please select the item's condition on return", "error");
                    return;
                }

                const returnNotes = document.getElementById("return-notes").value || null;
                const transactionId = returnModal.getAttribute("data-transaction-id");
                const itemId = currentItemData.item.item_id;

                if (!transactionId || !itemId) {
                    showToast("Missing transaction or item information", "error");
                    return;
                }

                try {
                    const response = await fetch(`/api/scanner/update-item/${itemId}`, {
                        method: 'PUT',  // Changed from POST to PUT
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        },
                        body: JSON.stringify({
                            item_name: currentItemData.item.item_name,
                            condition_id: conditionId,
                            item_notes: returnNotes,
                            transaction_id: transactionId
                        })
                    });

                    const data = await response.json();

                    if (data.status === 'success') {
                        showToast('Item returned successfully!', 'success');
                        closeModals();
                        // Clear the active transaction reference
                        currentActiveTransaction = null;
                        // Refresh the scanned item data
                        await fetchEquipmentDetails(currentItemData.item.barcode_number);
                    } else {
                        showToast('Error: ' + (data.message || 'Failed to return item'), 'error');
                    }
                } catch (error) {
                    console.error('Return error:', error);
                    showToast('Failed to process return request', 'error');
                }
            }

            function showReleaseModal() {
                if (!currentItemData) return;

                document.getElementById("release-item-name").textContent = currentItemData.item.item_name;
                document.getElementById("release-barcode").textContent = currentItemData.item.barcode_number;

                // Reset form fields
                document.getElementById("request-select").value = "";
                document.getElementById("facility-id").value = "";
                document.getElementById("destination-name").value = "";
                document.getElementById("release-notes").value = "";

                releaseModal.style.display = "flex";
            }

            function closeModals() {
                releaseModal.style.display = "none";
                returnModal.style.display = "none";
            }

            async function onScanSuccess(decodedText) {
                if (!decodedText) {
                    showToast("No barcode data detected", "error");
                    return;
                }

                // Only clean whitespace - no EQ- requirement
                let cleanBarcode = decodedText.toString().trim().replace(/\s/g, '');

                resultSpan.textContent = cleanBarcode;

                // Stop scanner to avoid duplicate scans
                if (scannerRunning) {
                    try {
                        await html5QrCode.stop();
                    } catch (e) { }
                    scannerRunning = false;
                    stopBtn.style.display = "none";
                    resumeBtn.style.display = "inline-block";
                }

                await fetchEquipmentDetails(cleanBarcode);
            }

            async function startScanner() {
                if (scannerRunning) return;
                try {
                    await html5QrCode.start(
                        { facingMode: "environment" },
                        { fps: 10, qrbox: { width: 300, height: 200 } },
                        (decodedText) => onScanSuccess(decodedText),
                        () => { }
                    );
                    scannerRunning = true;
                    stopBtn.style.display = "inline-block";
                    resumeBtn.style.display = "none";
                } catch (err) {
                    console.error("Scanner start error:", err);
                    alert("Unable to start camera. Check permissions.");
                }
            }

            async function stopScanner() {
                if (!scannerRunning) return;
                try {
                    await html5QrCode.stop();
                } catch (err) { }
                scannerRunning = false;
                stopBtn.style.display = "none";
                resumeBtn.style.display = "inline-block";
            }

            // Event listeners
            stopBtn.addEventListener("click", stopScanner);
            resumeBtn.addEventListener("click", async () => {
                resultSpan.textContent = "None";
                infoBox.style.display = "none";
                currentItemData = null;
                await startScanner();
            });

            releaseBtn.addEventListener("click", showReleaseModal);
            returnBtn.addEventListener("click", prepareReturn);

            document.querySelectorAll(".cancel-modal").forEach(btn => {
                btn.addEventListener("click", closeModals);
            });

            document.getElementById("confirm-release")?.addEventListener("click", handleRelease);
            document.getElementById("confirm-return")?.addEventListener("click", handleReturn);

            // Close modal when clicking outside
            window.addEventListener("click", (e) => {
                if (e.target === releaseModal) closeModals();
                if (e.target === returnModal) closeModals();
            });

            // Initialize
            startScanner();
            loadFacilities();
        });
    </script>
@endsection