<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>API Service</title>
    <meta name="robots" content="noindex,nofollow">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at top, #1e293b, #020617);
            font-family: system-ui, -apple-system, sans-serif;
            color: #e5e7eb;
            padding: 24px;
        }

        .card {
            background: rgba(2, 6, 23, 0.95);
            padding: 36px 44px;
            border-radius: 16px;
            max-width: 520px;
            width: 100%;
            box-shadow: 0 25px 50px rgba(0,0,0,.65);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255,255,255,0.06);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            margin-bottom: 18px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 999px;
            background: #22c55e;
            color: #052e16;
        }

        .badge-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #052e16;
            animation: pulse-dot 1.5s ease-in-out infinite;
        }

        @keyframes pulse-dot {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
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

        .label { opacity: .6; }
        .value { font-weight: 500; text-align: right; }

        .ai-demo {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,.08);
        }

        .ai-demo-title {
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            opacity: 0.5;
            margin-bottom: 14px;
        }

        .ai-demo-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .ai-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 22px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            font-family: inherit;
            border: none;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
            text-decoration: none;
            line-height: 1;
            position: relative;
            overflow: hidden;
        }

        .ai-btn::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, transparent 50%);
            pointer-events: none;
        }

        .ai-btn-primary {
            background: linear-gradient(135deg, #1C2354, #5196F3);
            color: #fff;
            box-shadow: 0 4px 20px rgba(81, 150, 243, 0.35);
        }

        .ai-btn-primary:hover {
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 8px 30px rgba(81, 150, 243, 0.5);
        }

        .ai-btn-primary:active {
            transform: translateY(0) scale(0.98);
        }

        .ai-btn-secondary {
            background: rgba(255,255,255,0.06);
            color: #e5e7eb;
            border: 1px solid rgba(255,255,255,0.1);
            backdrop-filter: blur(8px);
        }

        .ai-btn-secondary:hover {
            background: rgba(255,255,255,0.12);
            border-color: rgba(255,255,255,0.2);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
        }

        .ai-btn-secondary:active {
            transform: translateY(0);
        }

        .ai-btn svg {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
        }

        .ai-hint {
            font-size: 12px;
            opacity: 0.35;
            margin-top: 14px;
            line-height: 1.6;
        }

        .ai-hint code {
            background: rgba(255,255,255,0.06);
            padding: 2px 7px;
            border-radius: 4px;
            font-size: 11px;
            border: 1px solid rgba(255,255,255,0.06);
        }
    </style>
</head>
<body>

<div class="card">
    <div class="badge">
        <span class="badge-dot"></span>
        API ONLINE
    </div>

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


</body>
</html>
