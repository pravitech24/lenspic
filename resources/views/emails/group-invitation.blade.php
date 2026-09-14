<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;background:#f4f6fb;font-family:Arial,sans-serif;color:#172033;">
  <div style="max-width:560px;margin:30px auto;background:#fff;border-radius:16px;padding:32px;box-shadow:0 8px 28px rgba(15,23,42,.08);">
    <div style="font-size:13px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:#6366f1;">LensPic invitation</div>
    <h1 style="margin:12px 0 8px;font-size:26px;">Join {{ $group->name }}</h1>
    <p style="color:#64748b;line-height:1.6;">{{ $group->creator->studio_name }} invited you to view and share event photos.</p>
    <div style="margin:24px 0;padding:18px;text-align:center;background:#f4f3ff;border-radius:12px;">
      <div style="font-size:12px;color:#64748b;text-transform:uppercase;letter-spacing:.1em;">Invitation code</div>
      <div style="margin-top:8px;font-size:28px;font-weight:800;letter-spacing:.2em;color:#4338ca;">{{ $invite->access_code }}</div>
    </div>
    <p style="text-align:center;"><a href="{{ $invite->url }}" style="display:inline-block;padding:13px 24px;background:#6366f1;color:#fff;text-decoration:none;border-radius:9px;font-weight:700;">Open invitation</a></p>
    <p style="margin-top:24px;color:#98a2b3;font-size:12px;line-height:1.5;">If you were not expecting this invitation, you can ignore this email.</p>
  </div>
</body>
</html>
