<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Action Required: New Booking Request</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
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
            border-radius: 8px 8px 0 0;
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

        .content {
            background: #f9f9f9;
            padding: 30px;
            border: 1px solid #e0e0e0;
            border-top: none;
            border-bottom: none;
            font-size: 15px;
        }

        .test-banner {
            background: #ffc107;
            color: #333;
            text-align: center;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-weight: bold;
            font-size: 13px;
        }

        /* Resource block – inline, no background box */
        .resource-section {
            margin: 16px 0 8px;
        }

        .resource-section h4 {
            color: #003366;
            margin: 0 0 6px;
            font-size: 15px;
            padding-bottom: 4px;
            border-bottom: 1px solid #d0d7e0;
        }

        .resource-list {
            list-style: none;
            padding: 0;
            margin: 6px 0 0;
        }

        .resource-list li {
            padding: 6px 0;
            font-size: 15px;
            border-bottom: 1px solid #ececec;
            display: flex;
            align-items: center;
        }

        .resource-list li:last-child {
            border-bottom: none;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            margin-right: 10px;
            min-width: 70px;
            text-align: center;
            letter-spacing: 0.3px;
        }

        .badge-facility {
            background: #004283;
            color: white;
        }

        .badge-equipment {
            background: {{ $equipment_badge_color ?? '#17a2b8' }};
            color: white;
        }

        .badge-service {
            background: {{ $service_badge_color ?? '#28a745' }};
            color: white;
        }

        .badge-purpose {
            background: {{ $purpose_badge_color ?? '#6f42c1' }};
            color: white;
        }

        .resource-total {
            font-size: 14px;
            color: #555;
            margin: 10px 0 0;
            font-weight: normal;
        }

        /* Info box – still slightly boxed for request details */
        .info-box {
            background: #ffffff;
            border-left: 4px solid #003366;
            padding: 15px;
            margin: 20px 0;
            border-radius: 0 6px 6px 0;
        }

        .info-box h3 {
            margin: 0 0 10px;
            color: #003366;
            font-size: 16px;
        }

        .info-box table {
            width: 100%;
            border-collapse: collapse;
            font-size: 15px;
        }

        .info-box td {
            padding: 4px 0;
        }

        /* Inline note – no background, no border */
        .approval-note {
            background: transparent;
            border: none;
            padding: 0;
            margin: 16px 0;
            font-size: 14.5px;
            color: #555;
            border-left: 4px solid #ebbe39;
            padding-left: 14px;
        }

        /* Button wrapper – uniform top/bottom spacing */
        .button-wrapper {
            text-align: center;
            margin: 24px 0;
        }

        .button {
            display: inline-block;
            padding: 12px 24px;
            background: #003366;
            color: white !important;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            font-size: 15px;
            line-height: 1.4;
        }

        .button:hover {
            background: #135ba3;
        }

        .no-resources-message {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
            font-style: italic;
        }

        .footer {
            background: #003366;
            color: white;
            padding: 15px;
            text-align: center;
            font-size: 12px;
            border-radius: 0 0 8px 8px;
        }

        .footer p {
            margin: 5px 0;
        }

        .footer a {
            color: white;
            text-decoration: underline;
        }

        code {
            background: #f0f0f0;
            padding: 3px 6px;
            border-radius: 4px;
            font-size: 14px;
            font-family: monospace;
        }

        p {
            margin: 10px 0;
            font-size: 15px;
        }

        hr {
            border: none;
            border-top: 1px solid #e0e0e0;
            margin: 20px 0;
        }

        /* Compact spacing for the "why receiving" section */
        .why-box {
            font-size: 13px;
            color: #666;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <div class="container">
        @if(isset($is_test) && $is_test)
            <div class="test-banner">
                ⚠️ THIS IS A TEST EMAIL - Originally intended for {{ $admin_name }}
            </div>
        @endif

        <div class="header">
            <img src="https://res.cloudinary.com/dn98ntlkd/image/upload/v1756785959/lvus0zhyldou8td35e3z.png"
                alt="CPU Logo">
            <h1>Central Philippine University</h1>
        </div>

        <div class="content">
            <p>Dear {{ $admin_name }},</p>

            <p>Greetings from Central Philippine University!</p>

            <p>A new booking request requires your approval. You are receiving this because you administer one or more
                resources in this request.</p>

            <!-- Merged resource list -->
            <div class="resource-section">
                <h4>Resources You Manage</h4>

                @if(isset($grouped_resources) && (!empty($grouped_resources['facilities']) || !empty($grouped_resources['equipment']) || !empty($grouped_resources['services']) || !empty($grouped_resources['purposes'])))

                    <ul class="resource-list">
                        @if(!empty($grouped_resources['facilities']))
                            @foreach($grouped_resources['facilities'] as $resource)
                                <li>
                                    <span class="badge badge-facility">Facility</span>
                                    {{ $resource['name'] }}
                                </li>
                            @endforeach
                        @endif

                        @if(!empty($grouped_resources['equipment']))
                            @foreach($grouped_resources['equipment'] as $resource)
                                <li>
                                    <span class="badge badge-equipment">Equipment</span>
                                    {{ $resource['name'] }}
                                </li>
                            @endforeach
                        @endif

                        @if(!empty($grouped_resources['services']))
                            @foreach($grouped_resources['services'] as $resource)
                                <li>
                                    <span class="badge badge-service">Service</span>
                                    {{ $resource['name'] }}
                                </li>
                            @endforeach
                        @endif

                        @if(!empty($grouped_resources['purposes']))
                            @foreach($grouped_resources['purposes'] as $resource)
                                <li>
                                    <span class="badge badge-purpose">Purpose</span>
                                    {{ $resource['name'] }}
                                </li>
                            @endforeach
                        @endif
                    </ul>

                    <p class="resource-total">
                        <strong>Total:</strong> {{ $total_resources ?? count($resources) }} resource(s) requiring your
                        approval
                    </p>

                @elseif(isset($resources) && count($resources) > 0)
                    <!-- Fallback to simple list if grouped resources aren't available -->
                    <ul class="resource-list">
                        @foreach($resources as $resource)
                            <li>
                                @if($resource['type'] == 'facility')
                                    <span class="badge badge-facility">Facility</span>
                                @elseif($resource['type'] == 'equipment')
                                    <span class="badge badge-equipment">Equipment</span>
                                @elseif($resource['type'] == 'service')
                                    <span class="badge badge-service">Service</span>
                                @elseif($resource['type'] == 'purpose')
                                    <span class="badge badge-purpose">Purpose</span>
                                @else
                                    <span class="badge"
                                        style="background: #6c757d; color: white;">{{ ucfirst($resource['type']) }}</span>
                                @endif
                                {{ $resource['name'] }}
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="no-resources-message">
                        ⚠️ No specific resources found for your approval. Please check the request details manually.
                    </div>
                @endif
            </div>

            <!-- Request details – still boxed lightly -->
            <div class="info-box">
                <h3>Request Details</h3>
                <table>
                    <tr>
                        <td><strong>Request ID:</strong></td>
                        <td>#{{ $request_id }}</td>
                    </tr>
                    <tr>
                        <td><strong>Access Code:</strong></td>
                        <td><code>{{ $access_code }}</code></td>
                    </tr>
                    <tr>
                        <td><strong>Requester:</strong></td>
                        <td>{{ $requester_name }} ({{ $requester_email }})</td>
                    </tr>
                    <tr>
                        <td><strong>Purpose:</strong></td>
                        <td>{{ $purpose }}</td>
                    </tr>
                    <tr>
                        <td><strong>Participants:</strong></td>
                        <td>{{ $participants }}</td>
                    </tr>
                    <tr>
                        <td><strong>Schedule:</strong></td>
                        <td>{{ $schedule_display }}</td>
                    </tr>
                </table>
            </div>

            <!-- Note now inline, no background -->
            <div class="approval-note">
                <strong>Note:</strong> The booking cannot be confirmed until <strong>all responsible
                    administrators</strong> have approved it. Other administrators will be notified separately. Please
                take action on this request at your earliest convenience.
            </div>

            <!-- Button wrapper for uniform spacing -->
            <div class="button-wrapper">
                <a href="{{ $admin_link }}" class="button">
                    Review &amp; Approve Request
                </a>
            </div>

            <p style="margin-bottom: 4px;">
                <strong>CPU Booking System</strong><br>
                <small>This is an automated message. Please do not reply to this email.</small>
            </p>

            <hr>

            <div class="why-box">
                <strong>Why am I receiving this?</strong><br>
                You are registered as an administrator for:
                @if(isset($has_facilities) && $has_facilities) facilities, @endif
                @if(isset($has_equipment) && $has_equipment) equipment, @endif
                @if(isset($has_services) && $has_services) services, @endif
                @if(isset($has_purposes) && $has_purposes) purpose routing @endif
                in the CPU Booking System. If you have any questions, please contact the system administrator.
            </div>
        </div>

        <div class="footer">
            <p>For inquiries, please contact us at (033) 329-1971 local 1234</p>
            <p>Central Philippine University &copy; {{ date('Y') }}</p>
        </div>
    </div>
</body>

</html>