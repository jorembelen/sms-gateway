<div class="space-y-stack_gap_lg">

    {{-- Alert banner (shown when there are failures today) --}}
    @if($failedToday > 0)
        <div class="bg-error-container border-l-4 border-error p-4 flex items-center justify-between rounded shadow-sm">
            <div class="flex items-center gap-4">
                <div class="bg-error text-white p-2 rounded-full flex items-center justify-center flex-shrink-0">
                    <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1">error</span>
                </div>
                <div>
                    <h3 class="font-headline-md text-on-error-container leading-none">
                        {{ number_format($failedToday) }} failed message{{ $failedToday !== 1 ? 's' : '' }} today
                    </h3>
                    <p class="font-body-sm text-on-error-container opacity-80 mt-1">
                        Review the table below to identify failure reasons.
                    </p>
                </div>
            </div>
            @if($offlineDevices > 0)
                <span class="ml-4 text-body-sm text-on-error-container font-bold whitespace-nowrap">
                    {{ $offlineDevices }} device{{ $offlineDevices !== 1 ? 's' : '' }} offline
                </span>
            @endif
        </div>
    @endif

    {{-- Stats grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-stack_gap_md">
        <div class="bg-white border border-outline-variant p-4 rounded-lg relative overflow-hidden">
            <div class="flex flex-col">
                <span class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Failed Today</span>
                <span class="font-display-sm text-display-sm {{ $failedToday > 0 ? 'text-error' : 'text-on-surface' }}">
                    {{ number_format($failedToday) }}
                </span>
                <span class="font-body-sm {{ $failedToday > 0 ? 'text-error' : 'text-green-600' }}">
                    {{ $failedToday === 0 ? 'No failures today' : 'Check failure reasons below' }}
                </span>
            </div>
            <div class="absolute top-4 right-4 w-10 h-10 bg-error/10 text-error rounded flex items-center justify-center">
                <span class="material-symbols-outlined">sms_failed</span>
            </div>
        </div>

        <div class="bg-white border border-outline-variant p-4 rounded-lg relative overflow-hidden">
            <div class="flex flex-col">
                <span class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Devices Offline</span>
                <span class="font-display-sm text-display-sm text-on-surface">{{ number_format($offlineDevices) }}</span>
                <span class="font-body-sm text-on-surface-variant">Inactive / not seen recently</span>
            </div>
            <div class="absolute top-4 right-4 w-10 h-10 bg-secondary-container text-primary rounded flex items-center justify-center">
                <span class="material-symbols-outlined">portable_wifi_off</span>
            </div>
        </div>
    </div>

    {{-- Search --}}
    <div class="flex items-center gap-4">
        <div class="relative flex-1 max-w-sm">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-outline pointer-events-none">
                <span class="material-symbols-outlined text-[18px]">search</span>
            </span>
            <input
                wire:model.live.debounce.400ms="search"
                type="text"
                id="failed-search"
                aria-label="Search by recipient phone"
                placeholder="Search by recipient phone…"
                class="w-full bg-white border border-outline-variant rounded-lg py-2 pl-10 pr-4 text-body-md focus:ring-2 focus:ring-primary/10 focus:border-primary transition-all"
            />
        </div>
        @if($search)
            <button
                wire:click="$set('search', '')"
                class="text-body-sm text-on-surface-variant hover:text-on-surface flex items-center gap-1"
            >
                <span class="material-symbols-outlined text-[18px]">close</span> Clear
            </button>
        @endif
    </div>

    {{-- Table --}}
    <div class="bg-white border border-outline-variant rounded-lg overflow-hidden shadow-sm">
        <div class="px-6 py-4 border-b border-outline-variant flex justify-between items-center bg-surface-container-low">
            <h3 class="font-headline-md text-on-surface">Failed Messages</h3>
            <span class="font-label-sm text-label-sm text-on-surface-variant">Sorted by most recent</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-surface-container-lowest border-b border-outline-variant">
                    <tr>
                        <th class="px-6 py-3 font-label-md text-label-md text-on-surface-variant uppercase tracking-tighter">ID</th>
                        <th class="px-6 py-3 font-label-md text-label-md text-on-surface-variant uppercase tracking-tighter">Recipient</th>
                        <th class="px-6 py-3 font-label-md text-label-md text-on-surface-variant uppercase tracking-tighter">Failure Reason</th>
                        <th class="px-6 py-3 font-label-md text-label-md text-on-surface-variant uppercase tracking-tighter">Content</th>
                        <th class="px-6 py-3 font-label-md text-label-md text-on-surface-variant uppercase tracking-tighter">Device</th>
                        <th class="px-6 py-3 font-label-md text-label-md text-on-surface-variant uppercase tracking-tighter">Created At</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant">
                    @forelse($messages as $message)
                        <tr class="hover:bg-surface-container transition-colors group">
                            <td class="px-6 py-3 font-label-sm text-label-sm text-on-surface">#{{ $message->id }}</td>
                            <td class="px-6 py-3 font-body-md">{{ $message->to }}</td>
                            <td class="px-6 py-3">
                                @if($message->failure_reason)
                                    <span class="px-2 py-1 bg-error-container text-on-error-container rounded text-body-sm font-bold flex items-center gap-1 w-fit">
                                        <span class="material-symbols-outlined text-[14px]">warning</span>
                                        {{ $message->failure_reason }}
                                    </span>
                                @else
                                    <span class="text-body-sm text-outline">No reason recorded</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-body-sm text-on-surface-variant max-w-[180px] truncate" title="{{ $message->content }}">
                                {{ $message->content }}
                            </td>
                            <td class="px-6 py-3 font-body-sm text-secondary">
                                {{ $message->device ? '#' . $message->device->id : '—' }}
                            </td>
                            <td class="px-6 py-3 font-label-sm text-outline whitespace-nowrap">
                                {{ $message->created_at->format('Y-m-d H:i:s') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-body-sm text-on-surface-variant">
                                @if($search)
                                    No failed messages matching "{{ $search }}".
                                @else
                                    No failed messages.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="px-6 py-4 bg-surface-container-low border-t border-outline-variant flex flex-col sm:flex-row justify-between items-center gap-4">
            <span class="font-body-sm text-on-surface-variant">
                Showing {{ $messages->firstItem() ?? 0 }}–{{ $messages->lastItem() ?? 0 }}
                of {{ $messages->total() }} failed messages
            </span>
            {{ $messages->links() }}
        </div>
    </div>
</div>
