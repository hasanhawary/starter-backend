@php
    $isRtl = ($lang ?? 'ar') === 'ar';
    $dir = $isRtl ? 'rtl' : 'ltr';
    $align = $isRtl ? 'right' : 'left';

    // Shared brand theme (falls back to the original hardcoded values when a
    // setting is empty, so appearance is unchanged until an admin customises it).
    $brand       = $brand ?? [];
    $themeColors = $brand['theme'] ?? [];
    $mailTheme   = $brand['mail_theme'] ?? [];

    $appName     = $brand['name'] ?? config('app.name', 'Wa Legal');
    $headerBg    = $themeColors['primary'] ?? '#0f2c4d';
    $titleColor  = $themeColors['text'] ?? '#101828';
    $buttonBg    = $mailTheme['button_bg_color'] ?? '#0f2c4d';
    $buttonText  = $mailTheme['button_text_color'] ?? '#ffffff';
    $footerText  = $mailTheme['footer_text'] ?? null;
    $headerImage = $mailTheme['header_image'] ?? null;
    $headerText  = $mailTheme['header_text'] ?? null;
    $footerImage = $mailTheme['footer_image'] ?? null;
    $website     = $brand['website'] ?? null;

    // CTA button: use the notification's own action, or fall back to the brand site.
    $ctaUrl  = $actionUrl ?: $website;
    $ctaText = $actionText ?: ($isRtl ? 'زيارة المنصة' : 'Visit Platform');

    // Notification bodies are authored as plain text, so their line breaks reach
    // us as real newlines, which HTML collapses into a single space. A plain text
    // body is escaped and its newlines turned into <br>; a body that already
    // carries markup keeps rendering as the markup it is.
    $rawBody  = (string) ($body ?? '');
    $bodyHtml = strip_tags($rawBody) === $rawBody
        ? nl2br(e($rawBody), false)
        : $rawBody;
@endphp
<!DOCTYPE html>
<html lang="{{ $lang ?? 'ar' }}" dir="{{ $dir }}" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ $title ?? $appName }}</title>
    <!--[if mso]>
    <style type="text/css">
        body, table, td, a { font-family: Arial, sans-serif !important; }
    </style>
    <![endif]-->
</head>
<body style="margin:0; padding:0; background-color:#f4f5f7; -webkit-font-smoothing:antialiased; width:100% !important;">
    <div style="display:none; max-height:0; overflow:hidden; opacity:0;">
        {{ \Illuminate\Support\Str::limit(strip_tags($body ?? ''), 120) }}
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
           style="background-color:#f4f5f7; padding:32px 0;" dir="{{ $dir }}">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0"
                       style="width:600px; max-width:600px; background-color:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 1px 3px rgba(16,24,40,0.08); font-family:'Segoe UI', Tahoma, Arial, sans-serif;">

                    {{-- Header --}}
                    <tr>
                        <td style="background-color:{{ $headerBg }}; padding:28px 40px; text-align:{{ $align }};">
                            @if(!empty($headerImage))
                                <img src="{{ $headerImage }}" alt="header" style="max-height:44px; margin-bottom:8px; display:block;">
                            @endif
                            <span style="color:#ffffff; font-size:22px; font-weight:700; letter-spacing:0.3px;">
                                {{ $appName }}
                            </span>
                            @if(!empty($headerText))
                                <div style="color:#ffffff; opacity:0.85; font-size:14px; margin-top:6px;">{{ $headerText }}</div>
                            @endif
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:40px;">
                            @if(!empty($title))
                                <h1 style="margin:0 0 20px; font-size:20px; line-height:1.4; color:{{ $titleColor }}; font-weight:600; text-align:{{ $align }};">
                                    {{ $title }}
                                </h1>
                            @endif

                            <div style="font-size:15px; line-height:1.7; color:#475467; text-align:{{ $align }};">
                                {!! $bodyHtml !!}
                            </div>

                            @if(!empty($ctaUrl))
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:32px 0 8px;">
                                    <tr>
                                        <td align="center" style="border-radius:8px; background-color:{{ $buttonBg }};">
                                            <a href="{{ $ctaUrl }}" target="_blank"
                                               style="display:inline-block; padding:12px 28px; font-size:15px; font-weight:600; color:{{ $buttonText }}; text-decoration:none; border-radius:8px;">
                                                {{ $ctaText }}
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            @endif
                        </td>
                    </tr>

                    {{-- Divider --}}
                    <tr>
                        <td style="padding:0 40px;">
                            <div style="height:1px; background-color:#eaecf0;"></div>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:24px 40px 32px; text-align:{{ $align }};">
                            @if(!empty($footerImage))
                                <img src="{{ $footerImage }}" alt="footer" style="max-height:36px; margin-bottom:12px; display:block;">
                            @endif
                            <p style="margin:0; font-size:13px; line-height:1.6; color:#98a2b3;">
                                @if(!empty($footerText))
                                    {!! $footerText !!}
                                @else
                                    {{ $isRtl
                                        ? 'هذه رسالة آلية، يُرجى عدم الرد عليها.'
                                        : 'This is an automated message, please do not reply.' }}
                                @endif
                            </p>
                            <p style="margin:8px 0 0; font-size:13px; color:#98a2b3;">
                                &copy; {{ date('Y') }} {{ $appName }}.
                                {{ $isRtl ? 'جميع الحقوق محفوظة.' : 'All rights reserved.' }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
