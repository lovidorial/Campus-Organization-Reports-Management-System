<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; line-height: 1.6; color: #1f2937; }
        h1 { font-size: 20px; margin-bottom: 4px; }
        .meta { color: #64748b; margin-bottom: 24px; }
        .body { white-space: pre-wrap; }
    </style>
</head>
<body>
    <h1>{{ $activityRequest->title }}</h1>
    <p class="meta">Activity narrative report</p>
    <div class="body">{{ $body }}</div>
</body>
</html>