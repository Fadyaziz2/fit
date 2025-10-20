<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subjectLine ?? config('app.name') }}</title>
</head>
<body style="font-family: Arial, sans-serif; font-size: 16px; color: #111827; line-height: 1.6;">
    <div style="max-width: 640px; margin: 0 auto; padding: 24px;">
        @if(! empty($recipient?->display_name))
            <p style="margin-top: 0;">{{ __('message.hello_name', ['name' => $recipient->display_name]) }}</p>
        @endif

        <div>{!! nl2br(e($content)) !!}</div>

        <p style="margin-bottom: 0;">{{ __('message.regards') }},<br>{{ config('app.name') }}</p>
    </div>
</body>
</html>
