<div class="w-full max-w-sm mx-auto">
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 bg-primary-container rounded-xl mb-4">
            <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-on-surface tracking-tight">SMS Gateway</h1>
        <p class="text-sm text-on-surface-variant mt-1">Two-factor verification</p>
    </div>

    <div class="bg-white border border-outline-variant rounded-xl p-8 shadow-sm">
        <h2 class="text-lg font-semibold text-on-surface mb-2">Enter your code</h2>
        <p class="text-sm text-on-surface-variant mb-6">
            We sent a 6-digit code to your phone. It expires in 5 minutes.
        </p>

        @if (session('status'))
            <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">
                {{ session('status') }}
            </div>
        @endif

        @if ($errorMessage)
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                {{ $errorMessage }}
            </div>
        @endif

        @if ($rateLimited)
            <div class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-800">
                Too many code requests. Please wait {{ ceil($rateLimitSeconds / 60) }} minute(s) before requesting another.
            </div>
        @endif

        <form wire:submit="submit">
            <div class="mb-6">
                <label for="code" class="block text-xs font-medium text-on-surface-variant uppercase tracking-wider mb-1.5">
                    Verification code
                </label>
                <input
                    id="code"
                    wire:model="code"
                    type="text"
                    inputmode="numeric"
                    pattern="[0-9]{6}"
                    maxlength="6"
                    required
                    autofocus
                    autocomplete="one-time-code"
                    class="w-full border border-outline-variant rounded-lg px-3 py-2.5 text-sm text-on-surface bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all font-mono tracking-widest text-center text-lg @error('code') border-red-400 @enderror"
                    placeholder="000000"
                />
                @error('code')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                wire:loading.attr="disabled"
                class="w-full bg-primary text-white font-semibold py-2.5 px-4 rounded-lg hover:brightness-110 transition-all text-sm disabled:opacity-60"
            >
                <span wire:loading.remove>Verify</span>
                <span wire:loading>Verifying…</span>
            </button>
        </form>

        <div class="mt-4 text-center">
            <button
                wire:click="resend"
                wire:loading.attr="disabled"
                class="text-sm text-primary hover:underline disabled:opacity-40"
            >
                Resend code
            </button>
        </div>
    </div>

    <div class="mt-4 text-center">
        <a href="{{ route('admin.login') }}" class="text-xs text-on-surface-variant hover:underline">
            ← Back to login
        </a>
    </div>
</div>
