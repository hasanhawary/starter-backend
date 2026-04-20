@extends('export::pdf.layouts.lpr')

@section('title', $data['title'] ?? '')

@section('extra_css', $data['extra_css'] ?? '')

@section('header_extra_left', $data['header_extra_left'] ?? '')

@section('header_note', $data['header_note'] ?? '')

@section('table')
    @php
        $columns = $data['columns'] ?? [];
        $rows = $data['rows'] ?? [];
    @endphp

    <table class="report-table">
        <thead>
            <tr>
                @foreach ($columns as $col)
                    <th style="width:{{ $col['width'] }}">{{ $col['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    @foreach ($row as $cell)
                        <td class="cell-center">{!! $cell !!}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
