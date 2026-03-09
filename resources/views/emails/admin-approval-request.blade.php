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
        }

        .info-box {
            background: white;
            border-left: 4px solid #003366;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }

        .resource-section {
            margin: 20px 0;
        }

        .resource-section h4 {
            color: #003366;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid #e0e0e0;
        }

        .resource-box {
            background: #e8f0fe;
            padding: 15px;
            margin: 15px 0;
            border-radius: 8px;
        }

        .resource-list {
            list-style: none;
            padding: 0;
            margin: 10px 0;
        }

        .resource-list li {
            padding: 8px 0;
            border-bottom: 1px solid #d0e0ff;
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
            margin-right: 8px;
            min-width: 60px;
            text-align: center;
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

        .resource-count {
            font-size: 13px;
            color: #666;
            margin-left: 8px;
        }

        .button {
            display: inline-block;
            padding: 12px 24px;
            background: #003366;
            color: white !important;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }

        .button:hover {
            background: #135ba3;
        }

        .test-banner {
            background: #ffc107;
            color: #333;
            text-align: center;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-weight: bold;
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

        .approval-note {
            background: #e7f3ff;
            border: 1px solid #b8daff;
            color: #004085;
            padding: 12px;
            border-radius: 4px;
            margin: 15px 0;
            font-size: 14px;
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

            <p>A new booking request has been submitted that requires your approval. You are receiving this email because
                you are listed as an administrator for one or more resources in this request based on:</p>
            
            <ul style="margin-bottom: 20px;">
                @if(isset($has_facilities) && $has_facilities)
                    <li>Facilities managed by your department(s)</li>
                @endif
                @if(isset($has_equipment) && $has_equipment)
                    <li>Equipment managed by your department(s)</li>
                @endif
                @if(isset($has_services) && $has_services)
                    <li>Services you directly manage</li>
                @endif
            </ul>

            <div class="info-box">
                <h3 style="margin-top: 0; color: #003366;">📋 Request Details</h3>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 5px 0;"><strong>Request ID:</strong></td>
                        <td style="padding: 5px 0;">#{{ $request_id }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 5px 0;"><strong>Access Code:</strong></td>
                        <td style="padding: 5px 0;"><code>{{ $access_code }}</code></td>
                    </tr>
                    <tr>
                        <td style="padding: 5px 0;"><strong>Requester:</strong></td>
                        <td style="padding: 5px 0;">{{ $requester_name }} ({{ $requester_email }})</td>
                    </tr>
                    <tr>
                        <td style="padding: 5px 0;"><strong>Purpose:</strong></td>
                        <td style="padding: 5px 0;">{{ $purpose }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 5px 0;"><strong>Participants:</strong></td>
                        <td style="padding: 5px 0;">{{ $participants }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 5px 0;"><strong>Schedule:</strong></td>
                        <td style="padding: 5px 0;">{{ $schedule_display }}</td>
                    </tr>
                </table>
            </div>

            <div class="resource-box">
                <h3 style="margin-top: 0; color: #003366;">📌 Resources You Manage</h3>
                
                @if(isset($grouped_resources) && (!empty($grouped_resources['facilities']) || !empty($grouped_resources['equipment']) || !empty($grouped_resources['services'])))
                    
                    @if(!empty($grouped_resources['facilities']))
                        <div class="resource-section">
                            <h4>🏛️ Facilities ({{ count($grouped_resources['facilities']) }})</h4>
                            <ul class="resource-list">
                                @foreach($grouped_resources['facilities'] as $resource)
                                    <li>
                                        <span class="badge badge-facility">Facility</span>
                                        {{ $resource['name'] }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if(!empty($grouped_resources['equipment']))
                        <div class="resource-section">
                            <h4>🔧 Equipment ({{ count($grouped_resources['equipment']) }})</h4>
                            <ul class="resource-list">
                                @foreach($grouped_resources['equipment'] as $resource)
                                    <li>
                                        <span class="badge badge-equipment">Equipment</span>
                                        {{ $resource['name'] }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if(!empty($grouped_resources['services']))
                        <div class="resource-section">
                            <h4>🛠️ Services ({{ count($grouped_resources['services']) }})</h4>
                            <ul class="resource-list">
                                @foreach($grouped_resources['services'] as $resource)
                                    <li>
                                        <span class="badge badge-service">Service</span>
                                        {{ $resource['name'] }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <p style="margin-top: 15px; font-size: 13px; color: #666;">
                        <strong>Total:</strong> {{ $total_resources ?? count($resources) }} resource(s) requiring your approval
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
                                @else
                                    <span class="badge" style="background: #6c757d; color: white;">{{ ucfirst($resource['type']) }}</span>
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

            <div class="approval-note">
                <strong>📝 Note:</strong> The booking cannot be confirmed until <strong>all responsible administrators</strong> have approved it. 
                Other administrators will be notified separately for resources they manage.
            </div>

            <p style="text-align: center;">
                <a href="{{ $admin_link }}" class="button">
                    🔍 Review & Approve Request
                </a>
            </p>

            <p><strong>Please take action on this request at your earliest convenience.</strong></p>

            <p>If you have any questions about this request, please contact the requester directly or reach out to the
                system administrator.</p>

            <p>Thank you for using the CPU Facility and Equipment Booking System.</p>

            <p>Sincerely,<br>
                <strong>CPU Booking Services Team</strong>
            </p>

            <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 20px 0;">
            
            <p style="font-size: 12px; color: #666;">
                <strong>Why am I receiving this?</strong><br>
                You are receiving this email because you are registered as an administrator for:
                @if(isset($has_facilities) && $has_facilities) facilities, @endif
                @if(isset($has_equipment) && $has_equipment) equipment, @endif
                @if(isset($has_services) && $has_services) services @endif
                in the CPU Booking System.
            </p>
        </div>

        <div class="footer">
            <p>For inquiries, please contact us at (033) 329-1971 local 1234</p>
            <p>Central Philippine University &copy; {{ date('Y') }}</p>
            <p>This is an automated message. Please do not reply to this email.</p>
        </div>
    </div>
</body>

</html>