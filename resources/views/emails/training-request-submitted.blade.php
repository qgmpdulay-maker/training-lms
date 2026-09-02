<!DOCTYPE html>
<html>
<body style="font-family: sans-serif; color: #1f2937; margin: 0; padding: 24px; background: #f4f5f7;">
    <div style="max-width: 560px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e5e7eb;">
        <div style="background: #152A4E; padding: 20px 28px;">
            <p style="color: #E2762D; font-size: 12px; font-weight: 600; letter-spacing: 0.15em; text-transform: uppercase; margin: 0 0 4px;">OCD Training IMS</p>
            <h1 style="color: #ffffff; font-size: 18px; margin: 0;">New Training Request</h1>
        </div>
        <div style="padding: 24px 28px;">
            <p style="margin: 0 0 16px;">Reference: <strong>{{ $trainingRequest->reference_number }}</strong></p>

            <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                <tr>
                    <td style="padding: 8px 0; color: #6b7280; width: 40%;">Training</td>
                    <td style="padding: 8px 0; font-weight: 600;">{{ $trainingRequest->training_title }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #6b7280;">Requesting Agency</td>
                    <td style="padding: 8px 0; font-weight: 600;">{{ $trainingRequest->requesting_agency }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #6b7280;">Contact Person</td>
                    <td style="padding: 8px 0; font-weight: 600;">{{ $trainingRequest->contact_person }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #6b7280;">Contact Number</td>
                    <td style="padding: 8px 0; font-weight: 600;">{{ $trainingRequest->contact_number }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #6b7280;">Email</td>
                    <td style="padding: 8px 0; font-weight: 600;">{{ $trainingRequest->contact_email }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #6b7280;">Number of Participants</td>
                    <td style="padding: 8px 0; font-weight: 600;">{{ $trainingRequest->number_of_participants }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #6b7280;">Preferred Date</td>
                    <td style="padding: 8px 0; font-weight: 600;">{{ $trainingRequest->preferred_date->format('F j, Y') }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #6b7280;">Venue</td>
                    <td style="padding: 8px 0; font-weight: 600;">{{ $trainingRequest->venue }}</td>
                </tr>
            </table>

            <p style="margin: 20px 0 4px; color: #6b7280; font-size: 13px;">Reason for request</p>
            <p style="margin: 0; font-size: 14px;">{{ $trainingRequest->purpose }}</p>

            @if ($trainingRequest->tna_file_path)
                <p style="margin: 20px 0 0; font-size: 13px;">
                    TNA file attached to this request in the portal.
                </p>
            @endif

            @if ($trainingRequest->signed_letter_path)
                <p style="margin: 8px 0 0; font-size: 13px;">
                    A separately signed request letter was also uploaded in the portal.
                </p>
            @endif
        </div>
        <div style="height: 6px; background: linear-gradient(to right, #152A4E, #E2762D);"></div>
    </div>
</body>
</html>
