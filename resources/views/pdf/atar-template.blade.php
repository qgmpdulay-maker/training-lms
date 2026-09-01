<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>After Training Activity Report Template</title>
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
    <div class="title">After Training Activity Report</div>
    <div class="subtitle">Generic placeholder template pending OCD's branded ATAR file — fill in and upload through the Tools tab.</div>
</div>

<div class="section-title">Part I &mdash; Training Profile</div>
<table>
    <tr>
        <td width="50%">
            <div class="label">Training Title</div>
            <div class="fill"></div>
        </td>
        <td width="50%">
            <div class="label">Category</div>
            <div class="checks">
                <span class="box"></span> APB
                &nbsp;&nbsp;
                <span class="box"></span> Technical Assistance
            </div>
        </td>
    </tr>
    <tr>
        <td>
            <div class="label">Requesting Agency</div>
            <div class="fill"></div>
        </td>
        <td>
            <div class="label">Agency Type</div>
            <div class="checks">
                <span class="box"></span> LGU
                &nbsp;&nbsp;
                <span class="box"></span> NGA
            </div>
        </td>
    </tr>
    <tr>
        <td>
            <div class="label">Venue</div>
            <div class="fill"></div>
        </td>
        <td>
            <div class="label">Date Conducted</div>
            <div class="fill"></div>
        </td>
    </tr>
    <tr>
        <td>
            <div class="label">Region</div>
            <div class="fill"></div>
        </td>
        <td>
            <div class="label">LGU</div>
            <div class="fill"></div>
        </td>
    </tr>
</table>

<div class="section-title">Part II &mdash; Participation Summary</div>
<table>
    <tr>
        <td width="34%">
            <div class="label">Number of Participants</div>
            <div class="fill"></div>
        </td>
        <td width="33%">
            <div class="label">Graduates (Male / Female)</div>
            <div class="fill"></div>
        </td>
        <td width="33%">
            <div class="label">Teams Organized</div>
            <div class="fill"></div>
        </td>
    </tr>
    <tr>
        <td colspan="3">
            <div class="label">Graduates by Age Bracket (18&ndash;30 / 31&ndash;45 / 46&ndash;59 / 60+)</div>
            <div class="fill"></div>
        </td>
    </tr>
</table>

<div class="section-title">Part III &mdash; Learning Outcomes</div>
<table>
    <tr>
        <td width="34%">
            <div class="label">Pre-Test Score</div>
            <div class="fill"></div>
        </td>
        <td width="33%">
            <div class="label">Post-Test Score</div>
            <div class="fill"></div>
        </td>
        <td width="33%">
            <div class="label">Overall Trainer Rating</div>
            <div class="fill"></div>
        </td>
    </tr>
    <tr>
        <td width="6%" class="label" style="text-align: center;">No.</td>
        <td width="47%" class="label">Module</td>
        <td width="47%" class="label">Module Rating</td>
    </tr>
    @for ($row = 1; $row <= 6; $row++)
        <tr>
            <td style="text-align: center;">{{ $row }}</td>
            <td class="fill"></td>
            <td class="fill"></td>
        </tr>
    @endfor
</table>

<div class="section-title">Part IV &mdash; Narrative (Highlights, Issues, Recommendations)</div>
<table>
    <tr>
        <td style="height: 90px;"></td>
    </tr>
</table>

<div class="section-title">Part V &mdash; Certification</div>
<table class="sign">
    <tr>
        <td width="34%">
            <div class="label">Prepared By (Signature over Printed Name / Date)</div>
        </td>
        <td width="33%">
            <div class="label">Noted By (Signature over Printed Name / Date)</div>
        </td>
        <td width="33%">
            <div class="label">Approved By (Signature over Printed Name / Date)</div>
        </td>
    </tr>
</table>

<div class="footer">
    OCD &mdash; NDRRMC Training Learning Management System &nbsp;|&nbsp;
    Generated {{ now()->format('d M Y') }} &nbsp;|&nbsp;
    Generic placeholder template — replace with OCD's official branded ATAR file once available.
</div>

</body>
</html>
