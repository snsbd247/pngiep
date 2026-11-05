<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $siteName }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f2fb;font-family:Arial,Helvetica,sans-serif;color:#334155;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f2fb;padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:16px;overflow:hidden;">
                <tr>
                    <td style="background:{{ $primary }};padding:22px 28px;">
                        @if ($logo)
                            <img src="{{ $logo }}" alt="{{ $siteName }}" height="36" style="display:block;height:36px;">
                        @else
                            <span style="color:#ffffff;font-size:20px;font-weight:bold;">{{ $siteName }}</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    {{-- Body comes from Admin > Email Templates; placeholder values are escaped by TemplateMailer. --}}
                    <td style="padding:28px;font-size:15px;line-height:1.6;">{!! $body !!}</td>
                </tr>
                <tr>
                    <td style="padding:18px 28px;background:#f8f7fd;font-size:12px;color:#64748b;">
                        {{ $footer }}@if ($address)<br>{{ $address }}@endif
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
