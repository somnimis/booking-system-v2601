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
        }

        .badge-facility {
            background: #004283;
            color: white;
        }

        .badge-equipment {
            background: #17a2b8;
            color: white;
        }

        .badge-service {
            background: #28a745;
            color: white;
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
        }
    </style>
</head>

<body>
    <div class="container">
        @if($is_test ?? false)
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
                you are listed as an administrator for one or more resources in this request.</p>

            <div class="info-box">
                <h3 style="margin-top: 0;">Request Details</h3>
                <p><strong>Request ID:</strong> #{{ $request_id }}</p>
                <p><strong>Access Code:</strong> <code>{{ $access_code }}</code></p>
                <p><strong>Requester:</strong> {{ $requester_name }} ({{ $requester_email }})</p>
                <p><strong>Purpose:</strong> {{ $purpose }}</p>
                <p><strong>Participants:</strong> {{ $participants }}</p>
                <p><strong>Schedule:</strong> {{ $schedule_display }}</p>
            </div>

            <div class="resource-box">
                <h3 style="margin-top: 0;">Resources You Manage in This Request</h3>
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
            </div>

            <p style="text-align: center;">
                <a href="{{ $admin_link }}" class="button">
                    Review & Approve Request
                </a>
            </p>

            <p><strong>Please take action on this request at your earliest convenience.</strong> The booking cannot be
                confirmed until all responsible administrators have approved it.</p>

            <p>If you have any questions about this request, please contact the requester directly or reach out to the
                system administrator.</p>

            <p>Thank you for using the CPU Facility and Equipment Booking System.</p>

            <p>Sincerely,<br>
                <strong>CPU Booking Services Team</strong>
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