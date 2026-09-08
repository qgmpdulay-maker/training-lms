<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Certificate of {{ $type === \App\Models\TrainingRequest::CERTIFICATE_REMARKS_PARTICIPATION ? 'Participation' : 'Completion' }}</title>
    <style>
        @page { margin: 0; }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #111827;
            margin: 0;
        }

        .frame {
            margin: 26px;
            padding: 40px 56px;
            border: 3px solid #03055a;
            text-align: center;
        }

        .agency {
            font-size: 10px;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #4b5563;
        }

        .agency strong {
            color: #03055a;
        }

        .title {
            margin-top: 22px;
            font-size: 26px;
            font-weight: bold;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #03055a;
        }

        .subtitle {
            margin-top: 4px;
            font-size: 11px;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #6b7280;
        }

        .body-text {
            margin-top: 34px;
            font-size: 12px;
            line-height: 2.1;
            color: #1f2937;
        }

        .fill-value {
            display: inline-block;
            min-width: 260px;
            border-bottom: 1px solid #4b5563;
            font-weight: bold;
            color: #03055a;
        }

        .fill-value-short {
            display: inline-block;
            min-width: 140px;
            border-bottom: 1px solid #4b5563;
            font-weight: bold;
            color: #03055a;
        }

        .cert-number {
            margin-top: 26px;
            font-size: 9px;
            letter-spacing: 0.04em;
            color: #6b7280;
        }

        .signatures {
            width: 100%;
            margin-top: 56px;
            border-collapse: collapse;
        }

        .signatures td {
            width: 50%;
            text-align: center;
            font-size: 9px;
            color: #4b5563;
            padding-top: 6px;
            border-top: 1px solid #4b5563;
        }
    </style>
</head>
<body>

<div class="frame">
    <div class="agency">
        Republic of the Philippines &middot; Department of National Defense<br>
        <strong>Office of Civil Defense</strong> &middot; National Disaster Risk Reduction and Management Council
    </div>

    <div class="title">Certificate of {{ $type === \App\Models\TrainingRequest::CERTIFICATE_REMARKS_PARTICIPATION ? 'Participation' : 'Completion' }}</div>

    <div class="body-text">
        This is to certify that <span class="fill-value">{{ $participant->name }}</span><br>
        has satisfactorily {{ $type === \App\Models\TrainingRequest::CERTIFICATE_REMARKS_PARTICIPATION ? 'participated in' : 'completed' }} the training on<br>
        <span class="fill-value">{{ $trainingRequest->training_title }}</span><br>
        conducted on <span class="fill-value-short">{{ $issuedOn->format('F j, Y') }}</span> at <span class="fill-value-short">{{ $trainingRequest->venue }}</span>.
    </div>

    <div class="cert-number">Certificate No.: {{ $code }}</div>

    <table class="signatures">
        <tr>
            <td>Training Officer</td>
            <td>OCD Regional Director</td>
        </tr>
    </table>
</div>

</body>
</html>
