<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Admin Login – SMS Gateway</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet"/>
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
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="min-h-screen bg-background flex items-center justify-center px-4">
    <div class="w-full max-w-sm">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 bg-primary-container rounded-xl mb-4">
                <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-on-surface tracking-tight">SMS Gateway</h1>
            <p class="text-sm text-on-surface-variant mt-1">Admin Console</p>
        </div>

        <div class="bg-white border border-outline-variant rounded-xl p-8 shadow-sm">
            <h2 class="text-lg font-semibold text-on-surface mb-6">Sign in</h2>

            @if ($errors->any())
                <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                    {{ $errors->first('password') }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login.post') }}">
                @csrf
                <div class="mb-6">
                    <label for="password" class="block text-xs font-medium text-on-surface-variant uppercase tracking-wider mb-1.5">
                        Admin Password
                    </label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        required
                        autofocus
                        class="w-full border border-outline-variant rounded-lg px-3 py-2.5 text-sm text-on-surface bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
                        placeholder="Enter admin password"
                    />
                </div>
                <button
                    type="submit"
                    class="w-full bg-primary text-white font-semibold py-2.5 px-4 rounded-lg hover:brightness-110 transition-all text-sm"
                >
                    Sign in
                </button>
            </form>
        </div>
    </div>
</body>
</html>
