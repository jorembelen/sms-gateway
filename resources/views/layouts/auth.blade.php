<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $pageTitle ?? 'Admin' }} – SMS Gateway</title>
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap"
        rel="stylesheet" />
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#3525cd',
                        'primary-container': '#4f46e5',
                        'on-primary': '#ffffff',
                        surface: '#f8f9ff',
                        'on-surface': '#0b1c30',
                        'on-surface-variant': '#464555',
                        'outline-variant': '#c7c4d8',
                        'secondary-container': '#dae2fd',
                        'surface-container': '#e5eeff',
                        background: '#f8f9ff',
                        error: '#ba1a1a',
                    },
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif'],
                        mono: ['JetBrains Mono', 'ui-monospace'],
                    },
                }
            }
        }
    </script>
</head>

<body class="min-h-screen bg-background flex items-center justify-center px-4" style="font-family:'Inter',sans-serif;">
    {{ $slot }}
</body>

</html>
