<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>{{ $brand['name'] }}</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: {{ $brand['theme']['secondary'] }};
            font-family: 'Segoe UI', Tahoma, sans-serif;
        }

        a {
            color: {{ $brand['theme']['primary'] }};
            text-decoration: none;
        }
    </style>
</head>
<body>
<table width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0"
                   style="background:#fff;border-radius:12px;overflow:hidden">

                <!-- Header -->
                <tr>
                    <td style="padding:24px;background:url('{{ asset($brand['background']) }}') no-repeat center/cover;">
                        <table width="100%">
                            <tr>
                                <td align="left"><img src="{{ asset($brand['logo']['lg']) }}" width="120"></td>
                                <td align="right"
                                    style="color:#fff;font-size:18px;font-weight:600">{{ $brand['name'] }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- Body -->
                <tr>
                    <td style="padding:40px 32px;text-align:center;color:{{ $brand['theme']['text'] }}">

                        <h2>{{ emailTrans($data['title']) }}</h2>
                        <p>{!! $data['body'] ?? emailTrans($data['msg']) !!}</p>

                        @if(!empty($data['otp']))
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding:24px 0;">
                                        <table cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="
                                                    padding:16px 24px;
                                                    background:{{ $brand['mail_otp_style']['otp_bg'] }};
                                                    border:1px dashed {{ $brand['mail_otp_style']['otp_border_color'] }};
                                                    border-radius:10px;
                                                    font-size:{{ $brand['mail_otp_style']['otp_font_size'] }};
                                                    letter-spacing:{{ $brand['mail_otp_style']['otp_letter_spacing'] }};
                                                    font-weight:700;
                                                    color:{{ $brand['mail_otp_style']['otp_text_color'] }};
                                                    text-align:center;">
                                                    {{ $data['otp'] }}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        @endif

                        @if(!empty($data['otp_expires_in']))
                            <p style="font-size:12px;color:#6B7280;">This code will expire
                                in {{ $data['otp_expires_in'] }} minutes.</p>
                        @endif

                        @if(!empty($data['cta']))
                            <a href="{{ $data['cta']['url'] }}"
                               style="display:inline-block;margin-top:24px;padding:12px 32px;background:{{ $brand['theme']['primary'] }};color:#fff;border-radius:8px;font-weight:600">
                                {{ $data['cta']['label'] }}
                            </a>
                        @endif

                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td style="padding:24px;text-align:center">
                        <img src="{{ asset($brand['logo']['sm']) }}" width="36"><br>
                        <strong>{{ $brand['name'] }}</strong><br>
                        <span
                            style="font-size:12px;color:{{ $brand['theme']['muted'] }}">{{ now()->toDayDateTimeString() }}</span>
                        <p style="font-size:13px;color:{{ $brand['theme']['muted'] }}">
                            Contact us: <a
                                href="mailto:{{ $brand['contact']['email'] }}">{{ $brand['contact']['email'] }}</a>
                        </p>
                        <p style="font-size:12px;color:{{ $brand['theme']['muted'] }}">{{ $brand['contact']['address'] }}</p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html>
