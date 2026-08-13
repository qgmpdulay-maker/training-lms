<!DOCTYPE html>
<html>
<body style="font-family: sans-serif; color: #1f2937; margin: 0; padding: 24px; background: #f4f5f7;">
    <div style="max-width: 560px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e5e7eb;">
        <div style="background: #152A4E; padding: 20px 28px;">
            <p style="color: #E2762D; font-size: 12px; font-weight: 600; letter-spacing: 0.15em; text-transform: uppercase; margin: 0 0 4px;">OCD Training LMS</p>
            <h1 style="color: #ffffff; font-size: 18px; margin: 0;">We received your training request</h1>
        </div>
        <div style="padding: 24px 28px;">
            <p style="margin: 0 0 16px;">Hi {{ $trainingRequest->contact_person }},</p>
            <p style="margin: 0 0 16px;">
                Thank you for requesting <strong>{{ $trainingRequest->training_title }}</strong>. Your request has
                been sent to the Civil Defense and Disaster Management Training Institute (CDTI) for review.
            </p>

            <p style="margin: 0 0 4px; color: #6b7280; font-size: 13px;">Please keep this reference number for your records</p>
            <p style="margin: 0 0 20px; font-size: 20px; font-weight: 700; color: #152A4E; letter-spacing: 0.05em;">{{ $trainingRequest->reference_number }}</p>

            <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                <tr>
                    <td style="padding: 8px 0; color: #6b7280; width: 40%;">Preferred Date</td>
                    <td style="padding: 8px 0; font-weight: 600;">{{ $trainingRequest->preferred_date->format('F j, Y') }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #6b7280;">Venue</td>
                    <td style="padding: 8px 0; font-weight: 600;">{{ $trainingRequest->venue }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #6b7280;">Number of Participants</td>
                    <td style="padding: 8px 0; font-weight: 600;">{{ $trainingRequest->number_of_participants }}</td>
                </tr>
            </table>

            <p style="margin: 20px 0 0; font-size: 14px;">
                CDTI will get in touch using the contact details you provided. You can check the status of this
                request anytime under "My Requests" in the training portal.
            </p>
        </div>
        <div style="height: 6px; background: linear-gradient(to right, #152A4E, #E2762D);"></div>
    </div>
</body>
</html>
