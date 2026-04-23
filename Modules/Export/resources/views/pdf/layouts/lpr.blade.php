<!DOCTYPE html>
<html lang="{{ $lang ?? 'ar' }}" dir="{{ ($lang ?? 'ar') === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <title>@yield('title')</title>

    <style>
        @page {
            margin-top: 55mm;
            margin-left: 10mm;
            margin-right: 10mm;
            margin-bottom: 15mm;

            header: page-header;
            footer: page-footer;
        }

        * {
            font-family: 'al-mohanad', sans-serif;
            direction: rtl;
            font-size: 12px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: top;
            border: none;
            padding: 0;
        }

        .header-right-box {
            padding: 10px 12px;
            line-height: 1.8;
            text-align: center !important;
        }

        .header-left-box {
            line-height: 1.9;
        }

        .logo-wrap {
            text-align: center;
        }

        .logo {
            width: 120px;
            height: 120px;
            object-fit: contain;
            display: inline-block;
            margin-top: 6px !important;
        }

        .header-line {
            margin-top: 8px;
            border-top: 2px solid #000;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-top: 10px;
        }

        .report-table th,
        .report-table td {
            border: 1px solid #000;
            padding: 7px;
            vertical-align: top;
            text-align: center;
            white-space: normal;
            word-break: break-word;
            overflow-wrap: anywhere;
            line-height: 1.6;
        }

        .report-table th {
            background: #f2f2f2;
            text-align: center;
            font-weight: 700;
        }

        .cell-center {
            text-align: center;
            margin: 0 auto !important;
        }

        .badge-active {
            background: #d4edda;
            color: #155724;
            padding: 2px 6px;
            border-radius: 3px;
        }

        .badge-inactive {
            background: #f8d7da;
            color: #721c24;
            padding: 2px 6px;
            border-radius: 3px;
        }

        @yield('extra_css')
    </style>
</head>

<body>
    @php
        app()->setLocale($lang ?? 'ar');
        $isAr = app()->getLocale() === 'ar';
        $start = $start ?? null;
        $end = $end ?? null;
        $ministry = isset($settings['ministry']) && $settings['ministry'] !== 'null' ? $settings['ministry'] : '   ';
        $forces = isset($settings['forces']) && $settings['forces'] !== 'null' ? $settings['forces'] : '   ';
        $power =
            isset($settings['the_power_of_duty']) && $settings['the_power_of_duty'] !== 'null'
                ? $settings['the_power_of_duty']
                : '   ';
        $department =
            isset($settings['department']) && $settings['department'] !== 'null' ? $settings['department'] : '   ';
        $logoUrl = $settings['logo_url'] ?? public_path('GADD.png');
    @endphp

    <htmlpageheader name="page-header">
        <br>
        <table class="header-table">
            <tr>
                <td style="width: 38%; padding-left: 80px; text-align:center;">
                    <div class="header-right-box">
                        <div>{{ __('export::pdf.kingdom') }}</div>
                        <div>{{ $ministry }}</div>
                        <div>{{ $forces }}</div>
                        <div>{{ $power }}</div>
                        <div>{{ $department }}</div>
                    </div>

                    <br><br>

                    @php
                        $filledCount = collect([$ministry, $forces, $power, $department])
                            ->filter(fn($v) => trim($v) !== '')
                            ->count();
                    @endphp

                    @if ($filledCount === 0)
                        <br><br><br>
                    @elseif ($filledCount == 1)
                        <br><br>
                    @elseif ($filledCount == 2)
                        <br>
                    @endif

                    @yield('header_extra_left')
                </td>

                <td style="width: 24%; text-align:center;">
                    <div class="logo-wrap">
                        <div style="font-weight:700;">{{ __('export::pdf.bismillah') }}</div>
                        <img class="logo" src="{{ $logoUrl }}" alt="logo">
                        <div style="margin-top:4px; font-weight:700;">@yield('title')</div>
                    </div>
                </td>

                <td style="width: 38%; padding-right: 100px; text-align: right;">
                    <div class="header-left-box">
                        <div>{{ __('export::pdf.report_date') }}</div>

                        <div class="muted">
                            {{ __('export::pdf.from') }}
                            @if ($isAr && $start && class_exists(\Alkoumi\LaravelHijriDate\Hijri::class))
                                {{ \Alkoumi\LaravelHijriDate\Hijri::Date('Y-m-d', $start) }}{{ __('export::pdf.hijri_suffix') }}
                            @else
                                {{ optional($start)->format('Y-m-d') }}
                            @endif
                        </div>

                        <div class="muted">
                            {{ __('export::pdf.to') }}
                            @if ($isAr && $end && class_exists(\Alkoumi\LaravelHijriDate\Hijri::class))
                                {{ \Alkoumi\LaravelHijriDate\Hijri::Date('Y-m-d', $end) }}{{ __('export::pdf.hijri_suffix') }}
                            @else
                                {{ optional($end)->format('Y-m-d') }}
                            @endif
                        </div>
                    </div>

                    @yield('header_note')

                    <br><br><br>

                    <div>
                        {{ __('export::pdf.pages') }} (<span>{PAGENO}</span>/<span>{nbpg}</span>)
                    </div>
                </td>
            </tr>
        </table>

        <div class="header-line"></div>
    </htmlpageheader>

    <htmlpagefooter name="page-footer">
        <div style="text-align:center; font-size:11px;">
            {{ __('export::pdf.printed_at') }} {{ now()->format('Y-m-d H:i') }}
        </div>
        <br>
    </htmlpagefooter>

    @yield('table')

</body>

</html>
