<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Starter - Platform</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&family=Tajawal:wght@200;300;400;500;700;800;900&display=swap"
        rel="stylesheet">
    <style>

    </style>
</head>

<body style="margin: 0; padding: 0;font-family: 'Tajawal', serif;">
    <div
        style="background-image: url('{{ asset('template_design/36-Photoroom.png') }}'); background-color: #F4F7FF; background-size: 100% 50%; background-repeat: no-repeat; height: 100%; max-width: 100%; width: 100%; box-sizing: border-box;">
        <!-- Header -->
        <table cellpadding="0" cellspacing="0" style="width: 90%; margin: 0 auto 24px auto; padding-top: 2rem;
">
            <tr>
                <td align="left" style="width: 50%; padding-inline-end: 10px;">
                    <img src="{{ asset('template_design/wakebLogoLg.png') }}" alt="wakebLogoLg"
                        style="width: 120px; height: auto;">
                </td>
                <td align="right" style="width: 50%;">
                    <h3
                        style="margin: 0; color: #FFF; font-size: 1.25rem; font-weight: 500;  font-family: 'Segoe UI', serif; word-wrap: break-word; word-break: break-all; white-space: normal;">
                        Starter Platform
                    </h3>
                </td>
            </tr>
        </table>

        <!-- Content -->
        <table cellpadding="0" cellspacing="0"
            style="width: 90%; margin: 26px auto; background-color: #FFF; border-radius: 10px; padding: 2rem; text-align: center;">
            <tr>
                <td>
                    <h2
                        style="margin: 0 0 1rem; color: #1F1F1F; font-size: 1.5rem; font-weight: 700; font-family: 'Segoe UI', serif; word-wrap: break-word; word-break: break-all; white-space: normal;">
                        {!! parseKeyValueString($data['title'] ?? '---') !!}
                    </h2>

                    <p
                        style="margin: 0 0 1rem; color: #1F1F1F; font-size: 0.875rem; font-weight: 500; line-height: 1.6; font-family: 'Segoe UI', serif;">
                        @php
                            $rawMessage = $data['mailMsg'] ?? ($data['dataMsg'] ?? ($data['msg'] ?? null));
                            $parsedMessage = $rawMessage ? parseKeyValueString($rawMessage) : null;
                        @endphp

                        {!! !empty($rawMessage) ? $rawMessage : (!empty($parsedMessage) ? $parsedMessage : '---') !!}
                    </p>

                    <span
                        style="color: #8E8E93; font-size: 0.75rem; font-weight: 400; font-family: 'Segoe UI', serif;">We
                        wish you a safe and enjoyable experience with us!</span>
                </td>
            </tr>
            <tr>
                <td>
                    <div style="margin-top: 10px; border: 1px solid #C0C4CC; "></div>
                </td>
            </tr>
            <tr>
                <td>
                    <div style="margin-top: 32px; display: table; width: 100%; text-align: center;">
                        <div style="display: table-cell; vertical-align: middle;">
                            <div
                                style="width: 36px; height: 36px; border-radius: 200px; background-color: #03001B; margin: 0 auto;">
                                <img src="{{ asset('template_design/wakebLogoSm.png') }}" alt="wakebLogoSm"
                                    style="margin: 14px auto;">
                            </div>
                        </div>
                    </div>
                    <h4
                        style="margin: 0.5rem 0 0.25rem; color: #1F1F1F; font-size: 0.875rem; font-weight: 500; font-family: 'Segoe UI', serif;">
                        Starter Platform - Team
                    </h4>
                    <span
                        style="color: #8E8E93; font-size: 0.75rem; font-family: 'Segoe UI', serif;">{{ \Carbon\Carbon::now() }}</span>
                </td>
            </tr>
        </table>

        <!-- Footer -->
        <table cellpadding="0" cellspacing="0"
            style="text-align: center; background-color: transparent; width: 90%; margin: 1.5rem auto 1rem auto">
            <tr>
                <td style="padding-bottom: 1rem;">
                    <!-- Social Media Links -->
                    <table cellpadding="0" cellspacing="0" style="margin: 0 auto;">
                        <tr>
                            <td style="padding: 0 .25rem">
                                <a style="display: inline-block; width: 47px; border-radius: 100px; background: #ffffff; height: 47px; "
                                    href="https://www.instagram.com/wakeb_data" target="_blank"><img
                                        src="{{ asset('template_design/InstagramLogo.png') }}" alt="InstagramLogo"
                                        style="margin: 9px !important;"></a>
                            </td>
                            <td style="padding: 0 .25rem">
                                <a style="display: inline-block; width: 47px; border-radius: 100px; background: #ffffff; height: 47px; "
                                    href="https://www.facebook.com/Wakeb.tech" target="_blank"><img
                                        src="{{ asset('template_design/FacebookLogo.png') }}" alt="FacebookLogo"
                                        style="margin: 9px !important;"></a>
                            </td>
                            <td style="padding: 0 .25rem">
                                <a style="display: inline-block; width: 47px; border-radius: 100px; background: #ffffff; height: 47px; "
                                    href="https://www.linkedin.com/company/wakeb-data" target="_blank"><img
                                        src="{{ asset('template_design/LinkedInLogo.png') }}" alt="LinkedInLogo"
                                        style="margin: 9px !important;"></a>
                            </td>
                            <td style="padding: 0 .25rem">
                                <a style="display: inline-block; width: 47px; border-radius: 100px; background: #ffffff; height: 47px; "
                                    href="https://twitter.com/WAKEB_Data" target="_blank"><img
                                        src="{{ asset('template_design/TwitterLogo.png') }}" alt="TwitterLogo"
                                        style="margin: 9px !important;"></a>
                            </td>
                            <td style="padding: 0 .25rem">
                                <a style="display: inline-block; width: 47px; border-radius: 100px; background: #ffffff; height: 47px; "
                                    href="https://www.youtube.com/channel/UCG2IozJnWW-IzA3j2cSlhmg" target="_blank"><img
                                        src="{{ asset('template_design/YoutubeLogo.png') }}" alt="YoutubeLogo"
                                        style="margin: 9px !important;"></a>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td>
                    <p
                        style="margin: 0; color: #8E8E93; font-size: 0.875rem; line-height: 1.5; font-family: 'Segoe UI', serif; text-align: center;">

                        If you have any questions, please email us at <a href="mailto:Info@wakeb.tech"
                            style="color: #000000; text-decoration: underline; font-family: 'Segoe UI', serif; ">Info@wakeb.tech</a>.
                        All rights
                        reserved. Update your email preferences or unsubscribe.

                    </p>
                    <p
                        style="margin: 0.5rem 0; color: #8E8E93; font-size: 0.875rem; font-family: 'Segoe UI', serif; text-align: center;">

                        King Abdullah Street, Al Olaya District, Riyadh, 12212, Kingdom of Saudi Arabia

                    </p>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
