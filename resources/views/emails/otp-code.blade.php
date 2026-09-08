<!DOCTYPE html>
<html>
<body style="font-family: sans-serif; color: #1f2937; margin: 0; padding: 24px; background: #f4f5f7;">
    <div style="max-width: 560px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e5e7eb;">
        <div style="background: #152A4E; padding: 20px 28px;">
            <p style="color: #E2762D; font-size: 12px; font-weight: 600; letter-spacing: 0.15em; text-transform: uppercase; margin: 0 0 4px;">OCD Training IMS</p>
            <h1 style="color: #ffffff; font-size: 18px; margin: 0;">Verify your email address</h1>
        </div>
        <div style="padding: 24px 28px;">
            <p style="margin: 0 0 16px;">Hi {{ $registration->name }},</p>
            <p style="margin: 0 0 20px;">
                Use the code below to verify your email address and continue creating your account.
            </p>

            <p style="margin: 0 0 4px; color: #6b7280; font-size: 13px;">Your verification code</p>
            <p style="margin: 0 0 20px; font-size: 32px; font-weight: 700; color: #152A4E; letter-spacing: 0.3em;">{{ $code }}</p>

            <p style="margin: 0; font-size: 14px; color: #6b7280;">
                This code expires in 10 minutes. If you didn't try to create an account, you can safely ignore this email.
            </p>
        </div>
        <div style="height: 6px; background: linear-gradient(to right, #152A4E, #E2762D);"></div>
    </div>
</body>
</html>
