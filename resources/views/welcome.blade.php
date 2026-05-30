<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>API Service</title>
    <meta name="robots" content="noindex,nofollow">
    <style>
        body {
            margin: 0;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at top, #1e293b, #020617);
            font-family: system-ui, -apple-system, sans-serif;
            color: #e5e7eb;
        }

        .card {
            background: rgba(2, 6, 23, 0.95);
            padding: 36px 44px;
            border-radius: 16px;
            max-width: 520px;
            width: 100%;
            box-shadow: 0 25px 50px rgba(0,0,0,.65);
        }

        .badge {
            display: inline-block;
            padding: 6px 14px;
            margin-bottom: 18px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 999px;
            background: #22c55e;
            color: #052e16;
        }

        h1 {
            margin: 0 0 10px;
            font-size: 24px;
            font-weight: 600;
        }

        .subtitle {
            font-size: 14px;
            opacity: .75;
            margin-bottom: 24px;
            line-height: 1.6;
        }

        .info {
            border-top: 1px solid rgba(255,255,255,.08);
            padding-top: 18px;
            font-size: 14px;
        }

        .row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
        }

        .label {
            opacity: .6;
        }

        .value {
            font-weight: 500;
            text-align: right;
        }
    </style>
</head>
<body>

<div class="card">
    <div class="badge">API ONLINE</div>

    <h1>Welcome</h1>

    <div class="subtitle">
        This endpoint serves a backend API.<br>
        No web interface is provided.
    </div>

    <div class="info">
        <div class="row">
            <span class="label">Host</span>
            <span class="value">{{ request()->getHost() }}</span>
        </div>

        <div class="row">
            <span class="label">Client IP</span>
            <span class="value">{{ request()->ip() }}</span>
        </div>

        <div class="row">
            <span class="label">Secure Connection</span>
            <span class="value">{{ request()->isSecure() ? 'Yes (HTTPS)' : 'No' }}</span>
        </div>

        <div class="row">
            <span class="label">Server Time (UTC)</span>
            <span class="value">{{ now()->utc()->format('Y-m-d H:i:s') }}</span>
        </div>
    </div>
</div>

@aiChat
</body>
</html>
