<!DOCTYPE html>
<html>
<body style="font-family: sans-serif; color: #1f2937; margin: 0; padding: 24px; background: #f4f5f7;">
    <div style="max-width: 560px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e5e7eb;">
        <div style="background: #152A4E; padding: 20px 28px;">
            <p style="color: #E2762D; font-size: 12px; font-weight: 600; letter-spacing: 0.15em; text-transform: uppercase; margin: 0 0 4px;">OCD Training IMS</p>
            <h1 style="color: #ffffff; font-size: 18px; margin: 0;">New account awaiting approval</h1>
        </div>
        <div style="padding: 24px 28px;">
            <p style="margin: 0 0 16px;">
                <strong>{{ $registration->name }}</strong> ({{ $registration->email }}) has verified their email and is waiting for a Super Admin to approve their account.
            </p>

            <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                <tr>
                    <td style="padding: 8px 0; color: #6b7280; width: 40%;">Participant Type</td>
                    <td style="padding: 8px 0; font-weight: 600;">{{ $registration->participant_type ?? '—' }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #6b7280;">Agency / City</td>
                    <td style="padding: 8px 0; font-weight: 600;">{{ $registration->agency ?? $registration->city ?? '—' }}</td>
                </tr>
            </table>

            <p style="margin: 20px 0 0; font-size: 14px;">
                Review and approve or reject this account under Manage Admins in the admin portal.
            </p>
        </div>
        <div style="height: 6px; background: linear-gradient(to right, #152A4E, #E2762D);"></div>
    </div>
</body>
</html>
