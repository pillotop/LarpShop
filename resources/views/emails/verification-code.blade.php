<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('auth.mail.subject', ['code' => $code]) }}</title>
</head>
<body style="margin:0;padding:24px;background:#f4f4f7;font-family:Arial,Helvetica,sans-serif;color:#222;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center">
                <table role="presentation" width="480" cellpadding="0" cellspacing="0" style="max-width:480px;background:#ffffff;border-radius:12px;overflow:hidden;">
                    <tr>
                        <td style="background:#f5024f;padding:20px 24px;color:#ffffff;font-size:22px;font-weight:bold;">LarpShop</td>
                    </tr>
                    <tr>
                        <td style="padding:28px 24px;">
                            <p style="margin:0 0 12px;font-size:16px;">{{ __('auth.mail.greeting', ['name' => $user->username]) }}</p>
                            <p style="margin:0 0 20px;font-size:15px;line-height:1.5;">{{ __('auth.mail.intro') }}</p>
                            <p style="margin:0 0 20px;text-align:center;font-size:34px;font-weight:bold;letter-spacing:10px;background:#f4f4f7;border-radius:8px;padding:16px;">{{ $code }}</p>
                            <p style="margin:0 0 8px;font-size:13px;color:#555;">{{ __('auth.mail.expires', ['minutes' => $ttlMinutes]) }}</p>
                            <p style="margin:0;font-size:13px;color:#555;">{{ __('auth.mail.ignore') }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
