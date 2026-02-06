<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>Sunnah & Dua - {{ $lesson->title }}</title>
    <style>
        @page {
            margin: 0;
            size: A4;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Times New Roman', serif;
            background: linear-gradient(135deg, #8B0000 0%, #D4AF37 100%);
            padding: 20px;
            color: #1f2937;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #8B0000;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #8B0000;
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 10px;
            font-family: 'Times New Roman', serif;
        }
        .header .lesson-title {
            color: #D4AF37;
            font-size: 20px;
            font-weight: 600;
            margin-top: 10px;
        }
        .section {
            margin-bottom: 30px;
        }
        .section-title {
            color: #8B0000;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 15px;
            border-bottom: 2px solid #D4AF37;
            padding-bottom: 8px;
            font-family: 'Times New Roman', serif;
        }
        .content {
            font-size: 16px;
            line-height: 1.8;
            color: #374151;
            white-space: pre-wrap;
            text-align: justify;
        }
        .arabic-text {
            font-family: 'Arial', 'Tahoma', 'DejaVu Sans', sans-serif;
            font-size: 18px;
            direction: rtl;
            text-align: right;
            line-height: 2.5;
            unicode-bidi: bidi-override;
        }
        .dua-section {
            background: #fef3c7;
            padding: 20px;
            border-radius: 8px;
            border-right: 4px solid #D4AF37;
            margin: 15px 0;
        }
        .sunnah-section {
            background: #fef2f2;
            padding: 20px;
            border-radius: 8px;
            border-right: 4px solid #8B0000;
            margin: 15px 0;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #D4AF37;
            text-align: center;
            color: #6b7280;
            font-size: 12px;
        }
        .decoration {
            text-align: center;
            color: #D4AF37;
            font-size: 24px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="decoration">✦ ✦ ✦</div>
            <h1>Sunnah & Dua</h1>
            <div class="lesson-title">{{ $lesson->title }}</div>
            <div class="decoration">✦ ✦ ✦</div>
        </div>

        @if($resource->sunnah_pointers)
        <div class="section">
            <div class="section-title">Sunnah Pointers</div>
            <div class="sunnah-section">
                <div class="content">{{ $resource->sunnah_pointers }}</div>
            </div>
        </div>
        @endif

        @if($resource->duas_text)
        <div class="section">
            <div class="section-title">Duas</div>
            <div class="dua-section">
                <div class="content arabic-text">{{ $resource->duas_text }}</div>
            </div>
        </div>
        @endif

        <div class="footer">
            <p>Generated on {{ now()->format('F j, Y') }}</p>
            <p>Tazkiyah LMS - Journey to Purity</p>
        </div>
    </div>
</body>
</html>
