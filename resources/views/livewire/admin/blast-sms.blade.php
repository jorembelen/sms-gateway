<div class="space-y-stack_gap_lg">

    {{-- Success banner --}}
    @if($dispatched)
        <div class="bg-[#D1FAE5] border border-[#6EE7B7] rounded-xl p-5 flex flex-col gap-3">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-[#065F46] text-[28px]">check_circle</span>
                <div>
                    <p class="font-headline-md text-headline-md text-[#065F46]">
                        {{ $queued }} {{ Str::plural('message', $queued) }} queued successfully
                    </p>
                    <p class="font-body-sm text-body-sm text-[#065F46]/80 mt-0.5">
                        Messages are being processed and will be sent via the active device.
                    </p>
                </div>
            </div>

            @if(count($skipped) > 0)
                <div class="bg-[#FEF3C7] border border-[#FCD34D] rounded-lg p-3">
                    <p class="font-label-md text-label-md text-[#92400E] mb-1">
                        {{ count($skipped) }} {{ Str::plural('number', count($skipped)) }} skipped (invalid format):
                    </p>
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach($skipped as $bad)
                            <li class="font-body-sm text-body-sm text-[#92400E]">{{ $bad }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="flex gap-3">
                <button
                    wire:click="resetForm"
                    class="px-4 py-2 bg-[#065F46] text-white rounded-lg font-label-md text-label-md hover:bg-[#064E3B] transition-colors"
                >
                    Send Another Blast
                </button>
                <a
                    href="{{ route('admin.messages') }}"
                    class="px-4 py-2 border border-[#065F46] text-[#065F46] rounded-lg font-label-md text-label-md hover:bg-[#065F46]/10 transition-colors"
                >
                    View Messages
                </a>
            </div>
        </div>
    @else
        <form wire:submit="send" class="grid grid-cols-1 lg:grid-cols-2 gap-stack_gap_lg">

            {{-- Message body --}}
            <section class="bg-surface border border-outline-variant rounded-xl p-5 flex flex-col gap-3">
                <div>
                    <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1 uppercase tracking-wider">
                        Message Content
                    </label>
                    <p class="font-body-sm text-body-sm text-on-surface-variant">
                        This exact text will be sent to every recipient.
                    </p>
                </div>

                <div class="relative flex-1">
                    <textarea
                        wire:model="content"
                        rows="16"
                        placeholder="Type your message here…"
                        class="w-full bg-white border border-outline-variant rounded-lg py-3 px-4 text-body-md text-on-surface focus:ring-2 focus:ring-primary/10 focus:border-primary transition-all resize-none"
                    ></textarea>
                    <span
                        x-data="{ get count() { return ($wire.content ?? '').length } }"
                        :class="count > 900 ? 'text-error' : 'text-on-surface-variant'"
                        class="absolute bottom-3 right-3 font-label-sm text-label-sm select-none"
                        x-text="count + ' / 1000'"
                    ></span>
                </div>

                @error('content')
                    <p class="font-body-sm text-body-sm text-error flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">error</span>
                        {{ $message }}
                    </p>
                @enderror
            </section>

            {{-- Recipients --}}
            <section class="bg-surface border border-outline-variant rounded-xl p-5 flex flex-col gap-3">
                <div>
                    <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1 uppercase tracking-wider">
                        Recipients
                    </label>
                    <p class="font-body-sm text-body-sm text-on-surface-variant">
                        One phone number per line. E.164 format recommended (e.g. <span class="font-mono">+9665XXXXXXXX</span>).
                    </p>
                </div>

                <textarea
                    wire:model="recipients"
                    rows="16"
                    placeholder="+9665XXXXXXXX&#10;+9735XXXXXXXX&#10;+9665XXXXXXXX"
                    class="w-full bg-white border border-outline-variant rounded-lg py-3 px-4 text-body-md font-mono text-on-surface focus:ring-2 focus:ring-primary/10 focus:border-primary transition-all resize-none"
                ></textarea>

                <div
                    x-data="{ get count() { return ($wire.recipients ?? '').split('\n').filter(l => l.trim().length > 0).length } }"
                    class="font-label-sm text-label-sm text-on-surface-variant"
                    x-text="count + ' ' + (count === 1 ? 'number' : 'numbers') + ' entered'"
                ></div>

                @error('recipients')
                    <p class="font-body-sm text-body-sm text-error flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">error</span>
                        {{ $message }}
                    </p>
                @enderror
            </section>

            {{-- Send bar --}}
            <div class="lg:col-span-2 flex items-center justify-between bg-surface border border-outline-variant rounded-xl px-5 py-4">
                <div>
                    <p class="font-label-md text-label-md text-on-surface">Ready to send?</p>
                    <p class="font-body-sm text-body-sm text-on-surface-variant">
                        Messages will be queued immediately and sent via the active device.
                    </p>
                </div>
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-primary text-white rounded-lg font-label-md text-label-md hover:bg-primary/90 active:bg-primary/80 transition-colors disabled:opacity-60 disabled:cursor-not-allowed"
                >
                    <span wire:loading.remove class="material-symbols-outlined text-[18px]">send</span>
                    <span wire:loading class="material-symbols-outlined text-[18px] animate-spin">progress_activity</span>
                    <span wire:loading.remove>Send Blast</span>
                    <span wire:loading>Sending…</span>
                </button>
            </div>

        </form>
    @endif

</div>
