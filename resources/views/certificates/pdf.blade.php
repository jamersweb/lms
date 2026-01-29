<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Certificate</title>
    <style>
        @page {
            margin: 0;
            size: A4 landscape;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: 'Times New Roman', serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .certificate {
            width: 11in;
            height: 8.5in;
            margin: 0 auto;
            background: white;
            position: relative;
            padding: 2in;
            box-sizing: border-box;
        }
        .border {
            border: 20px solid #059669;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
        h1 {
            font-size: 48px;
            margin: 0 0 20px 0;
            color: #059669;
            font-weight: bold;
        }
        .subtitle {
            font-size: 24px;
            color: #666;
            margin-bottom: 40px;
        }
        .name {
            font-size: 36px;
            font-weight: bold;
            color: #1f2937;
            margin: 30px 0;
            border-bottom: 3px solid #059669;
            padding-bottom: 10px;
            display: inline-block;
        }
        .description {
            font-size: 18px;
            color: #4b5563;
            margin: 20px 0;
            line-height: 1.6;
        }
        .footer {
            margin-top: 60px;
            font-size: 14px;
            color: #6b7280;
        }
        .certificate-number {
            font-size: 12px;
            color: #9ca3af;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="certificate">
        <div class="border">
            <h1>CERTIFICATE OF COMPLETION</h1>
            <div class="subtitle">This is to certify that</div>
            <div class="name">{{ $user_name }}</div>
            <div class="description">
                @if($type === 'course_completion')
                    has successfully completed the course<br>
                    <strong>{{ $course_title }}</strong>
                @elseif($type === 'level_up')
                    has advanced to the <strong>{{ ucfirst($level) }}</strong> level
                @else
                    has achieved a significant milestone in their learning journey
                @endif
            </div>
            <div class="footer">
                <div>Issued on {{ $issued_date }}</div>
                <div class="certificate-number">Certificate Number: {{ $certificate_number }}</div>
            </div>
        </div>
    </div>
</body>
</html>
