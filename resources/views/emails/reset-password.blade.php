{{-- Ticket A2 — mail dat lai mat khau. HTML don gian: mail client khong chay CSS hien dai. --}}
<!DOCTYPE html>
<html lang="vi">
<head><meta charset="utf-8"></head>
<body style="margin:0;padding:24px;background:#F6F7FC;font-family:Arial,Helvetica,sans-serif;color:#1C2340">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width:480px;background:#fff;border-radius:16px;padding:32px" cellpadding="0" cellspacing="0">
                    <tr><td>
                        <h1 style="margin:0 0 8px;font-size:20px">Đặt lại mật khẩu</h1>

                        <p style="margin:0 0 16px;font-size:14px;line-height:1.6">
                            Chào {{ $recipientName }},<br>
                            Bạn vừa yêu cầu đặt lại mật khẩu cho tài khoản hoctoanonline.
                        </p>

                        <p style="margin:0 0 24px">
                            <a href="{{ $resetUrl }}"
                               style="display:inline-block;background:#2563EB;color:#fff;text-decoration:none;padding:12px 24px;border-radius:12px;font-weight:600;font-size:14px">
                                Đặt lại mật khẩu
                            </a>
                        </p>

                        <p style="margin:0 0 8px;font-size:13px;color:#626C8A;line-height:1.6">
                            Link có hiệu lực trong <strong>{{ $ttlMinutes }} phút</strong> và chỉ dùng được một lần.
                        </p>

                        <p style="margin:0 0 16px;font-size:13px;color:#626C8A;line-height:1.6">
                            Nếu bạn không yêu cầu việc này, bỏ qua email này — mật khẩu của bạn không thay đổi.
                        </p>

                        <hr style="border:none;border-top:1px solid #E6E9F4;margin:24px 0">

                        <p style="margin:0;font-size:12px;color:#626C8A;line-height:1.6;word-break:break-all">
                            Nút không bấm được? Dán link này vào trình duyệt:<br>
                            {{ $resetUrl }}
                        </p>
                    </td></tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
