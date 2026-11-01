<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Permit - CPU Booking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/public/official-permit.css') }}" />
</head>

<body>
    <!-- SUCCESS MESSAGE -->
    <div class="approval-banner" id="approvalBanner">
        <span class="banner-text">
            <i class="fa-solid fa-circle-check me-1"></i>
            This reservation has been officially approved and payment confirmed. Please present this permit on your
            scheduled date.
        </span>
        <button type="button" class="btn-close-banner"
            onclick="document.getElementById('approvalBanner').remove();">&times;</button>
    </div>

    <!-- Back Button -->
    <div style="max-width: 1100px; margin: 0 auto 1rem; padding: 0 1rem;">
        <a href="{{ route('home') }}" class="btn btn-sm btn-outline-secondary" style="border-radius: 6px;">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>
    </div>

    <div class="permit-layout-container">
        @if(empty($receiptData['permits']))
            <div class="permit-wrapper text-center">
                <div class="permit-header">
                    <img src="{{ asset('assets/cpu-logo.png') }}" alt="CPU Logo">
                    <h1>{{ $permit['type_label'] }} Use Permit</h1>
                    <p>Central Philippine University</p>
                </div>
                <p class="text-muted mb-0">No facilities or equipment were requested for this booking.</p>
            </div>
        @else
            <div class="permit-layout">
                <!-- Left Sidebar (Master) -->
                <div class="permit-sidebar">
                    <h5 class="sidebar-title">Permits</h5>

                    <!-- Scrollable List -->
                    <div class="permit-list-wrapper list-group" id="permitList" role="tablist">
                        @foreach($receiptData['permits'] as $i => $p)
                            <button
                                class="list-group-item list-group-item-action permit-list-item {{ $i === 0 ? 'active' : '' }}"
                                id="permit-tab-{{ $i }}" data-bs-toggle="tab" data-bs-target="#permit-pane-{{ $i }}"
                                type="button" role="tab">
                                <div class="item-content">
                                    <span class="item-badge">{{ $p['type_label'] }}</span>
                                    <span class="item-name">{{ $p['item_name'] }}</span>
                                </div>

                                <!-- Hover Download Icon -->
                                <i class="fas fa-download sidebar-download-btn download-permit-icon"
                                    data-target="permitCapture-{{ $i }}" data-filename="CPU-Permit-{{ $p['item_name'] }}.png"
                                    onclick="event.stopPropagation(); downloadImage(this);"></i>
                            </button>
                        @endforeach
                    </div>

                    <!-- Sidebar Footer Actions -->
                    <div class="mt-auto pt-3 border-top d-grid gap-2">
                        <button type="button" class="btn btn-primary btn-download-all" onclick="downloadAllPermits()">
                            <i class="fas fa-download me-1"></i> Download all
                        </button>
                        <a href="{{ url('/user-feedback') }}"
                            class="btn btn-outline-secondary text-center btn-rate-service">
                            Rate Our Services
                        </a>
                    </div>

                    <!-- Receipt Accordion -->
                    <div class="receipt-wrapper mt-3 w-100">
                        <div class="accordion" id="receiptAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingReceipt">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#collapseReceipt" aria-expanded="false"
                                        aria-controls="collapseReceipt">
                                        <i class="fas fa-receipt me-2"></i> View Receipt
                                    </button>
                                </h2>
                                <div id="collapseReceipt" class="accordion-collapse collapse"
                                    aria-labelledby="headingReceipt" data-bs-parent="#receiptAccordion">
                                    <div class="receipt-details">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h5 class="mb-0 fw-bold receipt-title">Receipt</h5>
                                            <span class="badge bg-secondary receipt-badge">
                                                Ref: #{{ $receiptData['official_receipt_num'] ?? 'N/A' }}
                                            </span>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-12">
                                                <p class="mb-1 text-muted small">Name</p>
                                                <p class="fw-medium mb-2 receipt-text">
                                                    {{ $receiptData['user_name'] ?? 'N/A' }}
                                                </p>
                                                <p class="mb-1 text-muted small">Organization</p>
                                                <p class="fw-medium receipt-text">
                                                    {{ $receiptData['organization_name'] ?? 'N/A' }}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="resources-header">Resources</div>
                                        <table class="table table-sm table-borderless mb-0">
                                            <tbody>
                                                @if(isset($receiptData['fee_breakdown']))
                                                    @foreach($receiptData['fee_breakdown'] as $fee)
                                                        <tr>
                                                            <td class="small">{{ $fee['description'] }}</td>
                                                            <td class="text-end small">
                                                                ₱{{ number_format($fee['amount'], 2) }}{{ $fee['rate_label'] ?? '' }}
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                @endif
                                                <tr class="border-top fw-bold">
                                                    <td class="pt-2 small">Total</td>
                                                    <td class="text-end pt-2 small">
                                                        ₱{{ number_format($receiptData['total_fee'] ?? 0, 2) }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Right Content (Detail) -->
                <div class="permit-content">
                    <div class="tab-content" id="permitTabsContent">
                        @foreach($receiptData['permits'] as $i => $permit)
                            <div class="tab-pane fade {{ $i === 0 ? 'show active' : '' }}" id="permit-pane-{{ $i }}"
                                role="tabpanel">
                                <div class="permit-wrapper" id="permitCapture-{{ $i }}">
                                    <div class="permit-header">
                                        <img src="{{ asset('assets/cpu-logo.png') }}" alt="CPU Logo">
                                        <h1>{{ $permit['type_label'] }} Use Permit</h1>
                                        <p>Central Philippine University</p>
                                    </div>

                                    <div class="permit-to">
                                        <span>Permit Issued To</span>
                                        <h2>
                                            {{ $receiptData['organization_name'] ?? $receiptData['user_name'] ?? 'N/A' }}
                                        </h2>
                                    </div>

                                    <div class="details-grid">
                                        <!-- Row 1 -->
                                        <div class="detail-item">
                                            <strong>Event Title</strong>
                                            <div>{{ $receiptData['event_title'] ?? 'N/A' }}</div>
                                        </div>
                                        <div class="detail-item">
                                            <strong>{{ $permit['type_label'] }}</strong>
                                            <div>{{ $permit['item_name'] }}</div>
                                        </div>

                                        <!-- Row 2 -->
                                        <div class="detail-item">
                                            <strong>Contact Person</strong>
                                            <div>{{ $receiptData['user_name'] ?? 'N/A' }}</div>
                                        </div>
                                        <div class="detail-item">
                                            <strong>Contact Number</strong>
                                            <div>{{ $receiptData['contact_number'] ?? 'N/A' }}</div>
                                        </div>

                                        <!-- Row 3 -->
                                        <div class="detail-item">
                                            <strong>Activity / Purpose</strong>
                                            <div>{{ $receiptData['purpose'] ?? 'N/A' }}</div>
                                        </div>
                                        <div class="detail-item">
                                            <strong>Participants</strong>
                                            <div>{{ $receiptData['num_participants'] ?? 'N/A' }}</div>
                                        </div>

                                        <!-- Row 4 -->
                                        <div class="detail-item">
                                            <strong>Start Schedule</strong>
                                            <div>
                                                {{ $receiptData['formatted_start_date'] ?? 'N/A' }}
                                                {{ $receiptData['formatted_start_time'] ?? '' }}
                                            </div>
                                        </div>
                                        <div class="detail-item">
                                            <strong>End Schedule</strong>
                                            <div>
                                                {{ $receiptData['formatted_end_date'] ?? 'N/A' }}
                                                {{ $receiptData['formatted_end_time'] ?? '' }}
                                            </div>
                                        </div>
                                    </div>

                                    @if(!empty($receiptData['approving_admins']))
                                        <div class="signatures-section">
                                            <div class="signatures-title">Officially Approved By</div>
                                            <div class="signature-grid">
                                                @foreach($receiptData['approving_admins'] as $admin)
                                                    <div class="signature-box">
                                                        <p class="sig-name">
                                                            {{ $admin['name'] }}
                                                        </p>
                                                        <p class="sig-title">
                                                            {{ $admin['title'] }}
                                                        </p>
                                                        <p class="sig-date">
                                                            Approved: {{ $admin['date_approved'] ?? 'N/A' }}
                                                        </p>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    @if(!empty($receiptData['control_num']))
                                        <div class="verification-footer text-center mt-4 pt-3">
                                            <p class="mb-1 text-dark"
                                                style="font-size: 0.75rem; font-weight: 600; letter-spacing: 1px;">
                                                Control Number: {{ $receiptData['control_num'] }}
                                            </p>
                                            <p class="mb-0 text-muted" style="font-size: 0.6rem;">
                                                Any alteration to this document is subject to fee penalties.
                                            </p>
                                        </div>
                                    @endif

                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- html2canvas for image download -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <!-- JSZip and FileSaver for batch zip downloads -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
    <script>
        // Helper to generate the image data
        async function generatePermitImage(btnElement) {
            const targetId = btnElement.dataset.target;

            btnElement.style.pointerEvents = 'none';
            btnElement.style.opacity = '0.3';

            const permit = document.getElementById(targetId);

            try {
                const canvas = await html2canvas(permit, {
                    scale: 2, useCORS: true, backgroundColor: "#ffffff"
                });
                // Return the raw data (base64) instead of downloading
                return canvas.toDataURL("image/png");
            } catch (err) {
                console.error("Error generating image:", err);
                alert("Failed to generate image for: " + targetId);
                return null;
            } finally {
                btnElement.style.pointerEvents = 'auto';
                btnElement.style.opacity = '';
            }
        }

        // 1. Single Download (Hover Icon)
        async function downloadImage(btnElement) {
            const filename = btnElement.dataset.filename;
            const imageData = await generatePermitImage(btnElement);

            if (imageData) {
                const link = document.createElement('a');
                link.download = filename;
                link.href = imageData;
                link.click();
            }
        }

        // 2. Batch Download (Zip)
        async function downloadAllPermits() {
            const icons = document.querySelectorAll('.download-permit-icon');
            const btn = event.currentTarget;
            const originalText = btn.innerHTML;

            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Zipping...';
            btn.disabled = true;

            // Initialize JSZip
            const zip = new JSZip();

            for (let i = 0; i < icons.length; i++) {
                const icon = icons[i];
                const filename = icon.dataset.filename;

                console.log(`Zipping permit ${i + 1} of ${icons.length}...`);

                // Switch to the tab so it renders on screen
                const tabTrigger = document.getElementById('permit-tab-' + i);
                if (tabTrigger) {
                    const tab = new bootstrap.Tab(tabTrigger);
                    tab.show();
                }

                // Wait for animation
                await new Promise(r => setTimeout(r, 500));

                // Generate image (but don't download yet)
                const imageData = await generatePermitImage(icon);

                if (imageData) {
                    // Remove the data URI prefix (e.g., "data:image/png;base64,")
                    const base64Data = imageData.replace(/^data:image\/(png|jpg);base64,/, "");
                    // Add to the zip folder
                    zip.file(filename, base64Data, { base64: true });
                }
            }

            // Compile the zip and trigger download
            console.log("Generating ZIP file...");
            const content = await zip.generateAsync({ type: "blob" });

            // Grab the Request ID from the first filename to name the zip
            let zipFilename = "CPU-Permits.zip";
            if (icons.length > 0) {
                const firstFilename = icons[0].dataset.filename; // e.g., "CPU-Permit-REQ-123-FAC-1.png"
                const parts = firstFilename.split('-');
                if (parts.length > 2) {
                    zipFilename = `CPU-Permits-${parts[2]}.zip`; // Extract REQ-123
                }
            }

            saveAs(content, zipFilename); // Trigger download using FileSaver.js

            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    </script>
</body>

</html>