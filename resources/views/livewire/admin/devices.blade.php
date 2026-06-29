<div class="space-y-stack_gap_lg">

    {{-- Table --}}
    <div class="bg-surface border border-outline-variant rounded-xl overflow-hidden shadow-sm">
        <div class="px-gutter py-4 border-b border-outline-variant flex justify-between items-center bg-surface-container-low">
            <h3 class="font-headline-md text-headline-md text-on-surface">Registered Devices</h3>
            <span class="font-label-sm text-label-sm text-on-surface-variant">{{ $devices->total() }} total</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-surface-container-low border-b border-outline-variant">
                    <tr>
                        <th class="px-gutter py-3 font-label-md text-label-md text-on-surface-variant uppercase">ID</th>
                        <th class="px-gutter py-3 font-label-md text-label-md text-on-surface-variant uppercase">Status</th>
                        <th class="px-gutter py-3 font-label-md text-label-md text-on-surface-variant uppercase">FCM Token</th>
                        <th class="px-gutter py-3 font-label-md text-label-md text-on-surface-variant uppercase">Last Seen</th>
                        <th class="px-gutter py-3 font-label-md text-label-md text-on-surface-variant uppercase">Messages</th>
                        <th class="px-gutter py-3 font-label-md text-label-md text-on-surface-variant uppercase">Registered</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant">
                    @forelse($devices as $device)
                        <tr class="hover:bg-surface-container-low transition-colors group">
                            <td class="px-gutter py-3 font-label-sm text-label-sm text-outline">#{{ $device->id }}</td>
                            <td class="px-gutter py-3">
                                @if($device->status === 'active')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold uppercase bg-[#D1FAE5] text-[#065F46]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500 inline-block"></span>
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold uppercase bg-[#F3F4F6] text-[#374151]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-gray-400 inline-block"></span>
                                        Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="px-gutter py-3">
                                @if($device->fcm_token)
                                    <div
                                        class="flex items-center gap-2"
                                        x-data="{ copied: false }"
                                        data-token="{{ $device->fcm_token }}"
                                    >
                                        <span class="font-label-sm text-label-sm text-on-surface-variant font-mono">
                                            {{ substr($device->fcm_token, 0, 12) }}…{{ substr($device->fcm_token, -6) }}
                                        </span>
                                        <button
                                            @click.stop="
                                                navigator.clipboard.writeText($el.closest('[data-token]').dataset.token);
                                                copied = true;
                                                setTimeout(() => copied = false, 2000);
                                            "
                                            class="flex-shrink-0 p-1 rounded hover:bg-surface-container-high transition-colors text-on-surface-variant hover:text-primary"
                                            :title="copied ? 'Copied!' : 'Copy full token'"
                                        >
                                            <span class="material-symbols-outlined text-[16px]" x-text="copied ? 'check' : 'content_copy'"></span>
                                        </button>
                                        <span
                                            x-show="copied"
                                            x-transition:enter="transition ease-out duration-150"
                                            x-transition:enter-start="opacity-0"
                                            x-transition:enter-end="opacity-100"
                                            x-transition:leave="transition ease-in duration-100"
                                            x-transition:leave-start="opacity-100"
                                            x-transition:leave-end="opacity-0"
                                            class="text-[11px] text-green-600 font-bold"
                                        >Copied!</span>
                                    </div>
                                @else
                                    <span class="text-body-sm text-outline">—</span>
                                @endif
                            </td>
                            <td class="px-gutter py-3 text-body-sm text-on-surface-variant">
                                {{ $device->last_seen_at ? $device->last_seen_at->diffForHumans() : '—' }}
                            </td>
                            <td class="px-gutter py-3 font-label-md text-label-md text-on-surface">
                                {{ number_format($device->messages_count) }}
                            </td>
                            <td class="px-gutter py-3 text-body-sm text-on-surface-variant whitespace-nowrap">
                                {{ $device->created_at->format('M d, Y') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-gutter py-10 text-center text-body-sm text-on-surface-variant">
                                No devices registered yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="px-gutter py-4 bg-surface border-t border-outline-variant flex flex-col sm:flex-row justify-between items-center gap-4">
            <span class="text-body-sm text-on-surface-variant">
                Showing <span class="font-semibold text-on-surface">{{ $devices->firstItem() ?? 0 }}–{{ $devices->lastItem() ?? 0 }}</span>
                of <span class="font-semibold text-on-surface">{{ $devices->total() }}</span>
            </span>
            {{ $devices->links('pagination::tailwind') }}
        </div>
    </div>
</div>
