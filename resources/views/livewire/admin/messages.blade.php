<div class="space-y-stack_gap_lg">

    {{-- Filters --}}
    <section class="bg-surface border border-outline-variant rounded-xl p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-5 gap-4">

            {{-- Phone search --}}
            <div class="col-span-1 lg:col-span-2">
                <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1 uppercase tracking-wider">
                    Search Phone
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

            {{-- Status --}}
            <div>
                <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1 uppercase tracking-wider">
                    Status
                </label>
                <select
                    wire:model.live="status"
                    class="w-full bg-white border border-outline-variant rounded-lg py-2 px-3 text-body-md focus:ring-2 focus:ring-primary/10 focus:border-primary cursor-pointer"
                >
                    <option value="">All Statuses</option>
                    @foreach($statuses as $s)
                        <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Date From --}}
            <div>
                <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1 uppercase tracking-wider">
                    Date From
                </label>
                <input
                    wire:model.live="dateFrom"
                    type="date"
                    class="w-full bg-white border border-outline-variant rounded-lg py-2 px-3 text-body-md focus:ring-2 focus:ring-primary/10 focus:border-primary"
                />
            </div>

            {{-- Date To + clear --}}
            <div>
                <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1 uppercase tracking-wider">
                    Date To
                </label>
                <div class="flex gap-2">
                    <input
                        wire:model.live="dateTo"
                        type="date"
                        class="flex-1 bg-white border border-outline-variant rounded-lg py-2 px-3 text-body-md focus:ring-2 focus:ring-primary/10 focus:border-primary"
                    />
                    @if($search || $status || $dateFrom || $dateTo)
                        <button
                            wire:click="$set('search', ''); $set('status', ''); $set('dateFrom', ''); $set('dateTo', '')"
                            class="px-3 py-2 border border-outline-variant rounded-lg text-on-surface-variant hover:bg-surface-container-high transition-colors text-body-sm"
                            title="Clear filters"
                        >
                            <span class="material-symbols-outlined text-[18px]">close</span>
                        </button>
                    @endif
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
                        <th class="px-gutter py-3 font-label-md text-label-md text-on-surface-variant uppercase">ID</th>
                        <th class="px-gutter py-3 font-label-md text-label-md text-on-surface-variant uppercase">To (Phone)</th>
                        <th class="px-gutter py-3 font-label-md text-label-md text-on-surface-variant uppercase">Content</th>
                        <th class="px-gutter py-3 font-label-md text-label-md text-on-surface-variant uppercase">Status</th>
                        <th class="px-gutter py-3 font-label-md text-label-md text-on-surface-variant uppercase">Failure Reason</th>
                        <th class="px-gutter py-3 font-label-md text-label-md text-on-surface-variant uppercase">Device</th>
                        <th class="px-gutter py-3 font-label-md text-label-md text-on-surface-variant uppercase">Created At</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant">
                    @forelse($messages as $message)
                        <tr
                            class="hover:bg-surface-container-low transition-colors cursor-pointer group"
                            x-data="{ expanded: false }"
                            @click="expanded = !expanded"
                        >
                            <td class="px-gutter py-3 font-label-sm text-label-sm text-outline">#{{ $message->id }}</td>
                            <td class="px-gutter py-3 font-label-md text-label-md">{{ $message->to }}</td>
                            <td class="px-gutter py-3 text-body-md text-on-surface">
                                <div x-show="!expanded" class="max-w-[200px] truncate">{{ $message->content }}</div>
                                <div x-show="expanded" class="max-w-xs whitespace-pre-wrap break-words">{{ $message->content }}</div>
                            </td>
                            <td class="px-gutter py-3">
                                @php
                                    $badge = match($message->status) {
                                        'delivered' => 'bg-[#D1FAE5] text-[#065F46]',
                                        'sent'      => 'bg-[#DBEAFE] text-[#1E40AF]',
                                        'failed'    => 'bg-[#FEE2E2] text-[#991B1B]',
                                        'pending'   => 'bg-[#F3F4F6] text-[#374151]',
                                        default     => 'bg-gray-100 text-gray-700',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badge }}">
                                    {{ ucfirst($message->status) }}
                                </span>
                            </td>
                            <td class="px-gutter py-3 text-body-sm {{ $message->failure_reason ? 'text-error font-medium' : 'text-outline' }}">
                                {{ $message->failure_reason ?? '—' }}
                            </td>
                            <td class="px-gutter py-3 font-label-sm text-label-sm text-primary">
                                {{ $message->device ? '#' . $message->device->id : '—' }}
                            </td>
                            <td class="px-gutter py-3 text-body-sm text-on-surface-variant whitespace-nowrap">
                                {{ $message->created_at->format('M d, Y H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-gutter py-10 text-center text-body-sm text-on-surface-variant">
                                No messages found.
                                @if($search || $status || $dateFrom || $dateTo)
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
            {{ $messages->links('pagination::tailwind') }}
        </div>
    </div>

    <p class="text-body-sm text-on-surface-variant">
        Click any row to expand/collapse the full message content.
    </p>
</div>
