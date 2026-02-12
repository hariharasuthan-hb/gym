<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name', 'Gym Management'))</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .email-container {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .email-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            padding: 30px 20px;
            text-align: center;
        }
        .email-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .email-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 14px;
        }
        .email-body {
            padding: 30px 20px;
        }
        .info-section {
            background-color: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-row {
            margin-bottom: 15px;
        }
        .info-row:last-child {
            margin-bottom: 0;
        }
        .info-label {
            font-weight: 600;
            color: #495057;
            display: inline-block;
            min-width: 100px;
            margin-bottom: 5px;
        }
        .info-value {
            color: #212529;
            display: block;
            margin-left: 0;
        }
        .message-box {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 20px;
            margin-top: 20px;
        }
        .message-box p {
            margin: 0;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .email-footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #6c757d;
            font-size: 12px;
            border-top: 1px solid #dee2e6;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #667eea;
            color: #ffffff;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 20px;
            font-weight: 600;
        }
        .button:hover {
            background-color: #5568d3;
        }
        a {
            color: #667eea;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="email-container">
        @hasSection('header')
            <div class="email-header">
                @yield('header')
            </div>
        @else
            <div class="email-header">
                <h1>@yield('header-title', config('app.name', 'Gym Management'))</h1>
                @hasSection('header-subtitle')
                    <p>@yield('header-subtitle')</p>
                @endif
            </div>
        @endif
        
        <div class="email-body">
            @yield('content')
        </div>
        
        <div class="email-footer">
            @hasSection('footer')
                @yield('footer')
            @else
                <p style="margin: 0;">This is an automated notification from {{ config('app.name', 'Gym Management System') }}</p>
                <p style="margin: 5px 0 0 0;">Please do not reply to this email unless otherwise specified.</p>
            @endif
        </div>
    </div>
</body>
</html>
