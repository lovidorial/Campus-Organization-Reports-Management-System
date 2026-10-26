<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>Confirm your identity | OrgTrack</title>
</head>
<body bgcolor="#F5F1EC" style="width:100%;margin:0;padding:0;background-color:#F5F1EC;color:#0F172A;font-family:'Inter','Segoe UI',Arial,sans-serif;-webkit-text-size-adjust:100%;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;mso-hide:all;">Confirm your identity to activate your OrgTrack account.</div>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#F5F1EC" style="width:100%;border-collapse:collapse;background-color:#F5F1EC;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="560" cellpadding="0" cellspacing="0" border="0" bgcolor="#FFFFFF" style="width:100%;max-width:560px;border-collapse:separate;border-spacing:0;background-color:#FFFFFF;border:1px solid #E8E2DC;border-radius:16px;overflow:hidden;box-shadow:0 12px 32px rgba(15,23,42,0.10);">
                    <tr>
                        <td align="center" bgcolor="#F25C05" style="padding:28px 24px 26px;background-color:#F25C05;background-image:linear-gradient(135deg,#FF6A13 0%,#F25C05 100%);border-radius:16px 16px 0 0;">
                            <img src="{{ $logoUrl }}" width="88" height="88" alt="Cagayan State University - Aparri and OSDW logo" style="display:block;width:88px;height:88px;margin:0 auto 14px;border:0;border-radius:50%;object-fit:cover;background-color:#FFFFFF;">
                            <p style="margin:0;color:#FFFFFF;font-size:11px;line-height:1.5;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;">Office of Student Development and Welfare</p>
                        </td>
                    </tr>
                    <tr>
                        <td bgcolor="#FFFFFF" style="padding:32px 32px 12px;background-color:#FFFFFF;">
                            <h1 style="margin:0 0 22px;color:#0F172A;font-size:28px;line-height:1.2;font-weight:700;">Confirm your <span style="color:#F25C05;">identity</span></h1>
                            <p style="margin:0 0 14px;color:#475569;font-size:15px;line-height:1.6;">Hello {{ $userName }},</p>
                            <p style="margin:0;color:#475569;font-size:15px;line-height:1.6;">You need to confirm your identity to activate your OrgTrack account. Select the button below to verify your email address.</p>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" bgcolor="#FFFFFF" style="padding:24px 24px 20px;background-color:#FFFFFF;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="border-collapse:separate;">
                                <tr>
                                    <td align="center" bgcolor="#F25C05" style="border-radius:10px;background-color:#F25C05;background-image:linear-gradient(135deg,#FF6A13 0%,#F25C05 100%);box-shadow:0 5px 14px rgba(242,92,5,0.25);">
                                        <a href="{{ $verificationUrl }}" style="display:inline-block;padding:14px 32px;border:1px solid #F25C05;border-radius:10px;color:#FFFFFF;font-family:'Inter','Segoe UI',Arial,sans-serif;font-size:16px;line-height:1.4;font-weight:700;text-decoration:none;">Confirm my identity &rarr;</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" bgcolor="#FFFFFF" style="padding:0 28px 22px;background-color:#FFFFFF;">
                            <p style="margin:0;color:#64748B;font-size:13px;line-height:1.6;">This link expires in {{ $expiryMinutes }} minutes. If you didn't expect this email, you can safely ignore it.</p>
                        </td>
                    </tr>
                    <tr>
                        <td bgcolor="#FFFFFF" style="padding:0 24px 28px;background-color:#FFFFFF;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#F8FAFC" style="width:100%;border-collapse:separate;border-spacing:0;background-color:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;">
                                <tr>
                                    <td style="padding:16px;color:#64748B;font-size:12px;line-height:1.6;">
                                        <p style="margin:0 0 8px;">If the button doesn't work, copy and paste this link into your browser:</p>
                                        <a href="{{ $verificationUrl }}" style="color:#C64B05;text-decoration:underline;word-break:break-all;overflow-wrap:anywhere;">{{ $verificationUrl }}</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" bgcolor="#FFFFFF" style="padding:18px 24px 22px;background-color:#FFFFFF;border-top:1px solid #EEE8E2;border-radius:0 0 16px 16px;">
                            <p style="margin:0 0 6px;color:#64748B;font-size:12px;line-height:1.5;">Orgtrack: Campus Student Organization Narrative &amp; Summary Reports</p>
                            <p style="margin:0;color:#64748B;font-size:12px;line-height:1.5;">Office of Student Development and Welfare &middot; Cagayan State University - Aparri</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
