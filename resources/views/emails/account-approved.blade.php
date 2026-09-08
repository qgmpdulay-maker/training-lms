<!DOCTYPE html>
<html>
<body style="font-family: sans-serif; color: #1f2937; margin: 0; padding: 24px; background: #f4f5f7;">
    <div style="max-width: 560px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e5e7eb;">
        <div style="background: #152A4E; padding: 20px 28px;">
            <p style="color: #E2762D; font-size: 12px; font-weight: 600; letter-spacing: 0.15em; text-transform: uppercase; margin: 0 0 4px;">OCD Training IMS</p>
            <h1 style="color: #ffffff; font-size: 18px; margin: 0;">Your account has been approved</h1>
        </div>
        <div style="padding: 24px 28px;">
            <p style="margin: 0 0 16px;">Hi {{ $user->name }},</p>
            <p style="margin: 0 0 16px;">
                Good news — a Super Admin has approved your OCD Training IMS account. You can now log in and start
                using the training portal.
            </p>
            <p style="margin: 0;">
                <a href="{{ route('login') }}" style="display: inline-block; background: #152A4E; color: #ffffff; text-decoration: none; font-size: 14px; font-weight: 600; padding: 10px 20px; border-radius: 8px;">
                    Log In
                </a>
            </p>
        </div>
        <div style="height: 6px; background: linear-gradient(to right, #152A4E, #E2762D);"></div>
    </div>
</body>
</html>
