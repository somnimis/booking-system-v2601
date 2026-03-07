<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Booking Approved - Payment Required</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background-color: #003366;
            color: white;
            padding: 20px;
            text-align: center;
        }

        .header img {
            height: 40px;
            vertical-align: middle;
            margin-right: 10px;
        }

        .header h1 {
            display: inline-block;
            vertical-align: middle;
            margin: 0;
            font-size: 24px;
        }

        .header p {
            margin: 5px 0 0 0;
            font-size: 14px;
            opacity: 0.9;
        }

        .content {
            background-color: #f9f9f9;
            padding: 20px;
        }

        .footer {
            background-color: #003366;
            color: white;
            padding: 10px;
            text-align: center;
            font-size: 12px;
        }

        .button {
            background-color: #eeaf01;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
        }

        .important {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 10px;
            margin: 15px 0;
        }

        .access-code {
            padding: 5px;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
        }

        .access-code small {
            display: block;
            font-weight: normal;
            color: #6c757d;
            font-size: 14px;
            margin-top: 5px;
            margin-bottom: 2px;
        }

        .details-section {
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
        }

        .details-section h3 {
            color: #003366;
            margin-top: 0;
            border-bottom: 2px solid #003366;
            padding-bottom: 5px;
        }

.fee-item {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;  /* Align items to the top */
    padding: 8px 0;
    border-bottom: 1px solid #eee;
    width: 100%;
}

.fee-item > span:first-child {
    flex: 1;  /* Take up available space */
    padding-right: 20px;  /* Add some spacing between left and right */
}

.fee-item > span:last-child {
    text-align: right;
    min-width: 200px;  /* Give enough width for the amount and calculation */
    white-space: nowrap;  /* Prevent wrapping of the amount line */
}


        .fee-label {
            font-weight: bold;
        }

        .fee-amount {
            text-align: right;
        }

        .account-num {
            background-color: #f8f9fa;
            padding: 3px 8px;
            border-radius: 3px;
            font-family: monospace;
            margin-left: 10px;
            font-size: 12px;
        }

        .waived {
            color: #969696;
            font-style: italic;
            font-size: 14px;
        }

        .total-fee {
            font-size: 18px;
            font-weight: bold;
            color: #003366;
            border-top: 2px solid #003366;
            margin-top: 10px;
            padding-top: 10px;
        }

        .subtotal {
            font-weight: bold;
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
        }

        .item-details {
            margin-left: 20px;
        }

        .item-details .fee-item {
            border-bottom: 1px dashed #ddd;
            padding: 6px 0;
            font-size: 14px;
        }

        .item-details .fee-item:last-child {
            border-bottom: none;
        }

        .section-subtitle {
            color: #555;
            font-size: 14px;
            margin-top: 5px;
            margin-bottom: 10px;
        }

        .duration-info {
            color: #666;
            font-size: 12px;
            margin-top: 2px;
        }

        .rate-type {
            color: #6c757d;
            font-style: italic;
            font-size: 12px;
        }

        .calculation {
            font-size: 12px;
            color: #666;
            display: block;
            margin-top: 2px;
            white-space: nowrap;
        }
        .rate-type {
    color: #6c757d;
    font-style: italic;
    font-size: 12px;
    margin-top: 2px;
}
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <img src="https://res.cloudinary.com/dn98ntlkd/image/upload/v1756785959/lvus0zhyldou8td35e3z.png"
                alt="CPU Logo">
            <h1>Central Philippine University</h1>
            <p style="margin: 5px 0 0 0; font-size: 14px; opacity: 0.9;">CPU Facilities and Equipment Booking System</p>
        </div>

        <div class="content">
            <p>Dear {{ $user_name }},</p>

            <p>Warm greetings from <strong>Central Philippine University</strong>!</p>

            <p>We are pleased to inform you that your <strong>Booking Request
                    #{{ str_pad($request_id, 4, '0', STR_PAD_LEFT) }}</strong> has been approved and is now awaiting
                your payment.</p>

            <div class="details-section">
                <h3>Booking Details</h3>
                <div class="section-subtitle">
                    <strong>Schedule:</strong> {{ $schedule_display }}<br>
                    <strong>Duration:</strong> {{ $booking_duration_text ?? $booking_duration . ' hours' }}
                </div>

                <!-- Simple Facilities List (for quick view) -->
                @if(isset($requested_facilities) && count($requested_facilities) > 0)
                    <p><strong>Facilities:</strong></p>
                    <ul>
                        @foreach($requested_facilities as $facility)
                            <li>
                                {{ $facility['facility_name'] }}
                                @if($facility['is_waived'])
                                    <span class="waived">(Waived)</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif

                <!-- Simple Equipment List (for quick view) -->
                @if(isset($requested_equipment) && count($requested_equipment) > 0)
                    <p><strong>Equipment:</strong></p>
                    <ul>
                        @foreach($requested_equipment as $equipment)
                            <li>
                                {{ $equipment['equipment_name'] }} (x{{ $equipment['quantity'] }})
                                @if($equipment['is_waived'])
                                    <span class="waived">(Waived)</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="details-section">
                <h3>Payment Breakdown</h3>
                <div class="section-subtitle">
                    Based on {{ $booking_duration_text ?? $booking_duration . ' hours' }} booking duration
                </div>

                <!-- Base Fee Breakdown -->
                <div style="margin-bottom: 15px;">

                    <!-- Facilities Breakdown -->
                    @if(isset($facilities_breakdown) && count($facilities_breakdown) > 0)
                        <div class="item-details">
                            <div class="fee-item" style="font-weight: bold; margin-top: 10px;">
                                <span>Facilities:</span>
                                <span></span>
                            </div>
                            @foreach($facilities_breakdown as $facility)
                                <div class="fee-item">
                                    <span>
                                        @if($facility['is_waived'] ?? false)
                                            <span style="text-decoration: line-through; color: #999;">
                                                {{ $facility['name'] ?? 'Facility' }}
                                            </span>
                                        @else
                                            {{ $facility['name'] ?? 'Facility' }}
                                        @endif
                                        <div class="rate-type">
                                            @if(($facility['rate_type'] ?? '') == 'Per Hour')
                                                × {{ $booking_duration }} hrs
                                            @else
                                                Per Event
                                            @endif
                                            @if($facility['is_waived'] ?? false)
                                                <span class="waived">(Waived)</span>
                                            @endif
                                        </div>
                                    </span>
                                    <span style="text-align: right;">
                                        @if($facility['is_waived'] ?? false)
                                            <span class="waived"
                                                style="text-decoration: line-through;">₱{{ number_format($facility['fee'] ?? 0, 2) }}</span>
                                        @else
                                            ₱{{ number_format($facility['fee'] ?? 0, 2) }}
                                            <span class="calculation">
                                                @if(($facility['rate_type'] ?? '') == 'Per Hour')
                                                    ₱{{ number_format($facility['unit_price'] ?? 0, 2) }}/hr × {{ $booking_duration }}
                                                    hrs
                                                @else
                                                    ₱{{ number_format($facility['unit_price'] ?? 0, 2) }}/event
                                                @endif
                                            </span>
                                        @endif
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <!-- Equipment Breakdown -->
                    @if(isset($equipment_breakdown) && count($equipment_breakdown) > 0)
                        <div class="item-details">
                            <div class="fee-item" style="font-weight: bold; margin-top: 10px;">
                                <span>Equipment:</span>
                                <span></span>
                            </div>
                            @foreach($equipment_breakdown as $equipment)
                                <div class="fee-item">
                                    <span>
                                        @if($equipment['is_waived'] ?? false)
                                            <span style="text-decoration: line-through; color: #999;">
                                                {{ $equipment['name'] ?? 'Equipment' }} (x{{ $equipment['quantity'] ?? 1 }})
                                            </span>
                                        @else
                                            {{ $equipment['name'] ?? 'Equipment' }} (x{{ $equipment['quantity'] ?? 1 }})
                                        @endif
                                        <div class="rate-type">
                                            @if(($equipment['rate_type'] ?? '') == 'Per Hour')
                                                × {{ $booking_duration }} hrs
                                            @else
                                                Per Event
                                            @endif
                                            @if($equipment['is_waived'] ?? false)
                                                <span class="waived">(Waived)</span>
                                            @endif
                                        </div>
                                    </span>
                                    <span style="text-align: right;">
                                        @if($equipment['is_waived'] ?? false)
                                            <span class="waived"
                                                style="text-decoration: line-through;">₱{{ number_format($equipment['fee'] ?? 0, 2) }}</span>
                                        @else
                                            ₱{{ number_format($equipment['fee'] ?? 0, 2) }}
                                            <span class="calculation">
                                                @if(($equipment['rate_type'] ?? '') == 'Per Hour')
                                                    ₱{{ number_format($equipment['unit_price'] ?? 0, 2) }}/hr × {{ $booking_duration }}
                                                    hrs × {{ $equipment['quantity'] ?? 1 }}
                                                @else
                                                    ₱{{ number_format($equipment['unit_price'] ?? 0, 2) }}/event ×
                                                    {{ $equipment['quantity'] ?? 1 }}
                                                @endif
                                            </span>
                                        @endif
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    <!-- Additional Fees -->
                    @if(isset($additional_fees) && count($additional_fees) > 0)
                        <div style="margin-top: 20px; border-top: 1px solid #eee; padding-top: 15px;">
                            <div class="fee-item" style="font-weight: bold; margin-bottom: 10px;">
                                <span>Additional Charges</span>
                                <span></span>
                            </div>

                            @foreach($additional_fees as $fee)
                                <div class="fee-item">
                                    <span class="fee-label">
                                        {{ $fee['label'] ?? 'Fee' }}

                                        <!-- Display account_num only if it exists and is not empty -->
                                        @if(!empty($fee['account_num']))
                                            <span class="account-num">Acct: {{ $fee['account_num'] }}</span>
                                        @endif

                                        <!-- Display discount information if applicable -->
                                        @if(isset($fee['discount_amount']) && $fee['discount_amount'] > 0)
                                            <br><small style="color: #000000;">
                                                Discount: ₱{{ number_format($fee['discount_amount'], 2) }}
                                                @if(isset($fee['discount_type']) && $fee['discount_type'] == 'Percentage')
                                                    ({{ $fee['discount_percentage'] ?? $fee['discount_amount'] }}%)
                                                @endif
                                            </small>
                                        @endif
                                    </span>

                                    <span class="fee-amount">
                                        @php
                                            $feeAmount = $fee['fee_amount'] ?? 0;
                                            $discountAmount = $fee['discount_amount'] ?? 0;
                                            $finalAmount = $feeAmount - $discountAmount;
                                        @endphp

                                        @if($feeAmount > 0 && $discountAmount > 0)
                                            <s style="color: #999;">₱{{ number_format($feeAmount, 2) }}</s><br>
                                            <span style="color: #7b141e;">-₱{{ number_format($discountAmount, 2) }}</span><br>
                                            <strong>₱{{ number_format($finalAmount, 2) }}</strong>
                                        @elseif($feeAmount > 0)
                                            ₱{{ number_format($feeAmount, 2) }}
                                        @elseif($discountAmount > 0)
                                            <span style="color: #7b141e;">-₱{{ number_format($discountAmount, 2) }}</span>
                                        @endif
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <!-- Late Penalty -->
                    @if(isset($late_penalty_fee) && $late_penalty_fee > 0)
                        <div class="fee-item" style="margin-top: 15px;">
                            <span class="fee-label" style="color: #7b141e;">Late Penalty:</span>
                            <span class="fee-amount"
                                style="color: #7b141e;">₱{{ number_format($late_penalty_fee, 2) }}</span>
                        </div>
                    @endif

                </div> <!-- Close the margin-bottom div -->

                <!-- Total Amount Due -->
                <div class="fee-item total-fee">
                    <span>TOTAL AMOUNT DUE:</span>
                    <span> ₱{{ number_format($approved_fee, 2) }}</span>
                </div>

                <p>To complete your booking, please:</p>

                <ol>
                    <li>Settle your payment of <strong>₱{{ number_format($approved_fee, 2) }}</strong> at the
                        <strong>CPU Business Office</strong> within campus.
                    </li>
                    <li>When paying, <strong>provide the Account Numbers</strong> shown above for each fee item to
                        ensure proper allocation.</li>
                    <li>After payment, kindly <strong>upload a clear photo of your receipt as Proof of Payment</strong>
                        through the Booking website using your access code.</li>
                </ol>

                <div class="access-code">
                    Your Access Code:<br>
                    <strong>{{ $access_code }}</strong>
                    <small>Use this code to upload your proof of payment on the booking website.</small>
                </div>

                <div class="important">
                    <p>⚠️ <strong>Important Reminder:</strong><br>
                        You have until <strong>{{ $payment_deadline }}</strong> to complete the payment process. If
                        payment is not made within this period, your booking request will be <strong>automatically
                            cancelled</strong>.</p>
                </div>

                <p>Thank you for using our booking system. We look forward to serving you soon.</p>

                <p>Best regards,<br>
                    Central Philippine University Administration</p>
            </div> <!-- Close the details-section div -->

            <div class="footer">
                <p>For inquiries, please contact us at (033) 329-1971 local 1234</p>
                <p>Central Philippine University &copy; {{ date('Y') }}</p>
                <p>This is an automated message. Please do not reply to this email.</p>
            </div>
        </div> <!-- Close the content div -->
    </div> <!-- Close the container div -->
</body>

</html>