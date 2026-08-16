<?php
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 Not Found</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f8f9fa;
            color: #212529;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        .error-container {
            max-width: 600px;
            width: 100%;
            background: #ffffff;
            padding: 40px 30px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border: 1px solid #e9ecef;
            text-align: center;
        }
        .error-code {
            font-size: 80px;
            font-weight: 800;
            color: #dc3545;
            line-height: 1;
            margin-bottom: 10px;
            letter-spacing: -2px;
        }
        .error-title {
            font-size: 24px;
            font-weight: 700;
            color: #343a40;
            margin-bottom: 15px;
        }
        .error-message {
            font-size: 15px;
            color: #6c757d;
            line-height: 1.6;
            margin-bottom: 25px;
        }
        .server-status {
            display: inline-block;
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 25px;
        }
        .btn-home {
            display: inline-block;
            background-color: #0d6efd;
            color: #ffffff;
            text-decoration: none;
            padding: 10px 24px;
            border-radius: 5px;
            font-weight: 600;
            font-size: 14px;
            transition: background-color 0.2s ease;
        }
        .btn-home:hover {
            background-color: #0b5ed7;
        }
        .server-footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 12px;
            color: #adb5bd;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">404</div>
        <h1 class="error-title">Page Not Found / Site Unavailable</h1>
        <div class="server-status">
            <span style="display:inline-block; width:8px; height:8px; background-color:#ffc107; border-radius:50%; margin-right:6px;"></span>
            Service Temporarily Unavailable
        </div>
        <p class="error-message">
            The requested page is currently unavailable or could not be found on this server. It may be under maintenance, experiencing temporary connectivity issues, or removed.
        </p>
        <a href="/" class="btn-home">Return to Home</a>
        <div class="server-footer">
            Server Status: 404 HTTP_NOT_FOUND &bull; Hostinger Web Server
        </div>
    </div>
</body>
</html>
