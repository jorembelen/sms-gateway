<div class="space-y-stack_gap_lg">

    {{-- Filters --}}
    <section class="bg-surface border border-outline-variant rounded-xl p-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">

            {{-- Sender search --}}
            <div class="col-span-1 md:col-span-2">
                <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1 uppercase tracking-wider">
                    Search Sender
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-outline pointer-events-none">
                        <span class="material-symbols-outlined text-[18px]">phone</span>
                    </span>
                    <input
                        wire:model.live.debounce.400ms="search"
                        type="text"
                        placeholder="+1 (555) 000-0000"
                        class="w-full bg-white border border-outline-variant rounded-lg py-2 pl-10 pr-4 text-body-md focus:ring-2 focus:ring-primary/10 focus:border-primary transition-all"
                    />
                </div>
            </div>

            {{-- Filter chips --}}
            <div>
                <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1 uppercase tracking-wider">
                    Link Status
                </label>
                <div class="flex gap-2">
                    @foreach(['all' => 'All', 'linked' => 'Linked', 'unlinked' => 'Unlinked'] as $val => $label)
                        <button
                            wire:click="$set('filter', '{{ $val }}')"
                            @class([
                                'px-3 py-1.5 rounded-lg font-label-sm text-label-sm border transition-colors',
                                'bg-primary text-white border-primary' => $filter === $val,
                                'bg-white text-on-surface-variant border-outline-variant hover:bg-surface-container-high' => $filter !== $val,
                            ])
                        >{{ $label }}</button>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- Table --}}
    <div class="bg-surface border border-outline-variant rounded-xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-surface-container-low border-b border-outline-variant">
                    <tr>
                        <th class="px-gutter py-3 font-label-md text-label-md text-on-surface-variant uppercase">Received At</th>
                        <th class="px-gutter py-3 font-label-md text-label-md text-on-surface-variant uppercase">From</th>
                        <th class="px-gutter py-3 font-label-md text-label-md text-on-surface-variant uppercase">Message Preview</th>
                        <th class="px-gutter py-3 font-label-md text-label-md text-on-surface-variant uppercase">Linked Outbound</th>
                        <th class="px-gutter py-3 font-label-md text-label-md text-on-surface-variant uppercase">Device</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant">
                    @forelse($messages as $message)
                        <tr
                            wire:click="showDetail({{ $message->id }})"
                            class="hover:bg-surface-container-low transition-colors cursor-pointer"
                        >
                            <td class="px-gutter py-3 text-body-sm text-on-surface-variant whitespace-nowrap">
                                {{ $message->received_at->format('M d, Y H:i') }}
                            </td>
                            <td class="px-gutter py-3 font-label-md text-label-md">{{ $message->sender }}</td>
                            <td class="px-gutter py-3 text-body-md text-on-surface max-w-[260px] truncate">
                                {{ \Illuminate\Support\Str::limit($message->body, 60) }}
                            </td>
                            <td class="px-gutter py-3">
                                @if($message->outbound_message_id)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-[#DBEAFE] text-[#1E40AF]">
                                        #{{ $message->outbound_message_id }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-[#F3F4F6] text-[#6B7280]">
                                        Unlinked
                                    </span>
                                @endif
                            </td>
                            <td class="px-gutter py-3 font-label-sm text-label-sm text-primary">
                                {{ $message->device ? '#' . $message->device->id : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-gutter py-10 text-center text-body-sm text-on-surface-variant">
                                No incoming messages found.
                                @if($search || $filter !== 'all')
                                    Try adjusting your filters.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="px-gutter py-4 bg-surface border-t border-outline-variant flex flex-col sm:flex-row justify-between items-center gap-4">
            <span class="text-body-sm text-on-surface-variant">
                Showing <span class="font-semibold text-on-surface">{{ $messages->firstItem() ?? 0 }}–{{ $messages->lastItem() ?? 0 }}</span>
                of <span class="font-semibold text-on-surface">{{ $messages->total() }}</span>
            </span>
            {{ $messages->links() }}
        </div>
    </div>

    <p class="text-body-sm text-on-surface-variant">
        Click any row to view full message details.
    </p>

    {{-- Detail Modal --}}
    @if($selectedMessage)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
            wire:click.self="closeDetail"
        >
            <div class="bg-surface rounded-xl shadow-xl w-full max-w-lg mx-4 overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-outline-variant">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary" style="font-variation-settings:'FILL' 1">move_to_inbox</span>
                        <h3 class="font-headline-md text-headline-md">Incoming Message</h3>
                    </div>
                    <button wire:click="closeDetail" class="text-on-surface-variant hover:text-on-surface transition-colors">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <div class="px-6 py-4 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider mb-1">From</p>
                            <p class="font-label-md text-label-md text-on-surface">{{ $selectedMessage->sender }}</p>
                        </div>
                        <div>
                            <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider mb-1">Received At</p>
                            <p class="font-body-sm text-body-sm text-on-surface">{{ $selectedMessage->received_at->format('M d, Y H:i:s') }}</p>
                        </div>
                    </div>

                    <div>
                        <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider mb-1">Device</p>
                        <p class="font-body-md text-body-md text-on-surface">
                            {{ $selectedMessage->device ? 'Device #' . $selectedMessage->device->id : '—' }}
                        </p>
                    </div>

                    <div>
                        <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider mb-1">Message Body</p>
                        <div class="bg-surface-container-low border border-outline-variant rounded-lg p-3">
                            <p class="font-body-md text-body-md text-on-surface whitespace-pre-wrap break-words">{{ $selectedMessage->body }}</p>
                        </div>
                    </div>

                    <div>
                        <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider mb-1">Linked Outbound Message</p>
                        @if($selectedMessage->outboundMessage)
                            <div class="bg-[#EFF6FF] border border-[#BFDBFE] rounded-lg p-3 space-y-1.5">
                                <p class="font-label-sm text-label-sm text-[#1E40AF]">ID: #{{ $selectedMessage->outboundMessage->id }}</p>
                                <p class="font-body-sm text-body-sm text-on-surface">To: <span class="font-medium">{{ $selectedMessage->outboundMessage->to }}</span></p>
                                <p class="font-body-sm text-body-sm text-on-surface">
                                    Status:
                                    <span class="font-medium">{{ ucfirst($selectedMessage->outboundMessage->status) }}</span>
                                </p>
                                <p class="font-body-sm text-body-sm text-on-surface-variant truncate">{{ $selectedMessage->outboundMessage->content }}</p>
                            </div>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-[#F3F4F6] text-[#6B7280]">
                                Unlinked
                            </span>
                        @endif
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-outline-variant flex justify-end">
                    <button
                        wire:click="closeDetail"
                        class="px-4 py-2 bg-primary text-white font-label-md text-label-md rounded-lg hover:bg-primary/90 transition-colors"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
