<div class="w-full max-w-sm mx-auto">
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

        @if ($errors->has('form'))
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                {{ $errors->first('form') }}
            </div>
        @endif

        <form wire:submit="submit">
            <div class="mb-4">
                <label for="email" class="block text-xs font-medium text-on-surface-variant uppercase tracking-wider mb-1.5">
                    Email address
                </label>
                <input
                    id="email"
                    wire:model="email"
                    type="email"
                    required
                    autofocus
                    autocomplete="email"
                    class="w-full border border-outline-variant rounded-lg px-3 py-2.5 text-sm text-on-surface bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all @error('email') border-red-400 @enderror"
                    placeholder="admin@example.com"
                />
                @error('email')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label for="password" class="block text-xs font-medium text-on-surface-variant uppercase tracking-wider mb-1.5">
                    Password
                </label>
                <input
                    id="password"
                    wire:model="password"
                    type="password"
                    required
                    autocomplete="current-password"
                    class="w-full border border-outline-variant rounded-lg px-3 py-2.5 text-sm text-on-surface bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all @error('password') border-red-400 @enderror"
                    placeholder="Enter password"
                />
                @error('password')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                wire:loading.attr="disabled"
                class="w-full bg-primary text-white font-semibold py-2.5 px-4 rounded-lg hover:brightness-110 transition-all text-sm disabled:opacity-60"
            >
                <span wire:loading.remove>Continue</span>
                <span wire:loading>Verifying…</span>
            </button>
        </form>
    </div>

    <p class="text-center text-xs text-on-surface-variant mt-6">
        A 6-digit code will be sent to your registered phone number.
    </p>
</div>
