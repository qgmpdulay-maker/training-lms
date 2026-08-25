<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Training Needs Assessment Form</title>
    <style>
        @page { margin: 28px 34px; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #111827;
        }

        .header {
            border-bottom: 2px solid #03055a;
            padding-bottom: 8px;
            margin-bottom: 14px;
        }

        .header .agency {
            font-size: 9px;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #4b5563;
        }

        .header .title {
            margin-top: 3px;
            font-size: 15px;
            font-weight: bold;
            color: #03055a;
        }

        .header .subtitle {
            margin-top: 2px;
            font-size: 9px;
            color: #6b7280;
        }

        .section-title {
            margin: 14px 0 6px;
            padding: 4px 6px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #ffffff;
            background-color: #03055a;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            padding: 6px 5px;
            border: 1px solid #9ca3af;
            vertical-align: top;
        }

        .label {
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #4b5563;
        }

        .fill {
            height: 16px;
        }

        .checks {
            font-size: 9px;
            line-height: 1.6;
        }

        .box {
            display: inline-block;
            width: 9px;
            height: 9px;
            border: 1px solid #4b5563;
            margin-right: 3px;
        }

        .note {
            margin-top: 4px;
            font-size: 8px;
            font-style: italic;
            color: #6b7280;
        }

        .sign td {
            height: 46px;
        }

        .footer {
            margin-top: 14px;
            padding-top: 6px;
            border-top: 1px solid #d1d5db;
            font-size: 8px;
            color: #6b7280;
        }
    </style>
</head>
<body>

<div class="header">
    <div class="agency">Republic of the Philippines &middot; Department of National Defense</div>
    <div class="agency">Office of Civil Defense &middot; NDRRMC</div>
    <div class="title">Training Needs Assessment Form</div>
    <div class="subtitle">Submit the accomplished form together with the PDF copy of the TNA results to your OCD Regional Office.</div>
</div>

<div class="section-title">Part I &mdash; Organization Profile</div>
<table>
    <tr>
        <td width="50%">
            <div class="label">Name of LGU / Organization</div>
            <div class="fill"></div>
        </td>
        <td width="50%">
            <div class="label">Region</div>
            <div class="fill"></div>
        </td>
    </tr>
    <tr>
        <td>
            <div class="label">Type of Organization</div>
            <div class="checks">
                <span class="box"></span> LGU (Local Government Unit)
                &nbsp;&nbsp;
                <span class="box"></span> NGA (National Government Agency)
            </div>
        </td>
        <td>
            <div class="label">Date Assessed</div>
            <div class="fill"></div>
        </td>
    </tr>
    <tr>
        <td>
            <div class="label">Number of Personnel Assessed</div>
            <div class="fill"></div>
        </td>
        <td>
            <div class="label">Submitted By (Name &amp; Position)</div>
            <div class="fill"></div>
        </td>
    </tr>
    <tr>
        <td>
            <div class="label">Contact Number</div>
            <div class="fill"></div>
        </td>
        <td>
            <div class="label">Email Address</div>
            <div class="fill"></div>
        </td>
    </tr>
</table>

<div class="section-title">Part II &mdash; Identified Training Needs</div>
<table>
    <tr>
        <td width="6%" class="label" style="text-align: center;">No.</td>
        <td width="40%" class="label">Training Topic / Need</td>
        <td width="18%" class="label">Personnel to be Trained</td>
        <td width="18%" class="label">Priority (High / Medium / Low)</td>
        <td width="18%" class="label">Target Schedule</td>
    </tr>
    @for ($row = 1; $row <= 8; $row++)
        <tr>
            <td style="text-align: center;">{{ $row }}</td>
            <td class="fill"></td>
            <td class="fill"></td>
            <td class="fill"></td>
            <td class="fill"></td>
        </tr>
    @endfor
</table>
<div class="note">Attach additional sheets if the identified needs exceed the rows provided.</div>

<div class="section-title">Part III &mdash; Justification and Remarks</div>
<table>
    <tr>
        <td style="height: 70px;">
            <div class="label">State the gaps, hazards, or mandates that these training needs respond to</div>
        </td>
    </tr>
</table>

<div class="section-title">Part IV &mdash; Certification</div>
<table class="sign">
    <tr>
        <td width="50%">
            <div class="label">Prepared By (Signature over Printed Name / Date)</div>
        </td>
        <td width="50%">
            <div class="label">Approved By (Signature over Printed Name / Date)</div>
        </td>
    </tr>
</table>

<div class="footer">
    OCD &mdash; NDRRMC Training Learning Management System &nbsp;|&nbsp;
    Generated {{ now()->format('d M Y') }} &nbsp;|&nbsp;
    Upload the accomplished form and the PDF copy of the TNA results through the Training Needs Assessment module.
</div>

</body>
</html>
