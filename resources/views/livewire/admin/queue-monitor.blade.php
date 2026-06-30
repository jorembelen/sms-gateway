<div class="space-y-stack_gap_lg" wire:poll.8000ms>

    {{-- Flash message --}}
    @if($flash)
        <div
            x-data="{ show: true }"
            x-init="setTimeout(() => show = false, 4000)"
            x-show="show"
            x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="flex items-center gap-3 bg-[#D1FAE5] border border-[#6EE7B7] text-[#065F46] rounded-xl px-5 py-3"
        >
            <span class="material-symbols-outlined text-[18px]">check_circle</span>
            <span class="font-body-md text-body-md">{{ $flash }}</span>
        </div>
    @endif

    {{-- Stats row --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-stack_gap_md">
        {{-- Pending --}}
        <div class="bg-surface border border-outline-variant rounded-xl p-5 flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-[#DBEAFE] flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined text-[#1E40AF]">pending</span>
            </div>
            <div>
                <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">Pending</p>
                <p class="font-headline-lg text-headline-lg text-on-surface">{{ $this->pendingCount }}</p>
            </div>
        </div>

        {{-- Running --}}
        <div class="bg-surface border border-outline-variant rounded-xl p-5 flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-[#FEF3C7] flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined text-[#92400E]">sync</span>
            </div>
            <div>
                <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">Running</p>
                <p class="font-headline-lg text-headline-lg text-on-surface">{{ $this->runningCount }}</p>
            </div>
        </div>

        {{-- Failed --}}
        <div class="bg-surface border border-outline-variant rounded-xl p-5 flex items-center gap-4">
            <div @class([
                'w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0',
                'bg-error-container' => $this->failedCount > 0,
                'bg-[#F3F4F6]'       => $this->failedCount === 0,
            ])>
                <span @class([
                    'material-symbols-outlined',
                    'text-error'     => $this->failedCount > 0,
                    'text-[#6B7280]' => $this->failedCount === 0,
                ])>error</span>
            </div>
            <div>
                <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">Failed</p>
                <p @class([
                    'font-headline-lg text-headline-lg',
                    'text-error'      => $this->failedCount > 0,
                    'text-on-surface' => $this->failedCount === 0,
                ])>{{ $this->failedCount }}</p>
            </div>
        </div>
    </div>

    {{-- Tab bar --}}
    <div class="flex items-center gap-2 border-b border-outline-variant">
        <button
            wire:click="switchTab('failed')"
            @class([
                'px-4 py-2.5 font-label-md text-label-md border-b-2 -mb-px transition-colors',
                'border-primary text-primary'                     => $tab === 'failed',
                'border-transparent text-on-surface-variant hover:text-on-surface' => $tab !== 'failed',
            ])
        >
            Failed Jobs
            @if($this->failedCount > 0)
                <span class="ml-1.5 px-1.5 py-0.5 rounded-full bg-error text-white text-[10px] font-bold">{{ $this->failedCount }}</span>
            @endif
        </button>
        <button
            wire:click="switchTab('pending')"
            @class([
                'px-4 py-2.5 font-label-md text-label-md border-b-2 -mb-px transition-colors',
                'border-primary text-primary'                     => $tab === 'pending',
                'border-transparent text-on-surface-variant hover:text-on-surface' => $tab !== 'pending',
            ])
        >
            Pending / Running
            @if($this->pendingCount > 0)
                <span class="ml-1.5 px-1.5 py-0.5 rounded-full bg-[#1E40AF] text-white text-[10px] font-bold">{{ $this->pendingCount }}</span>
            @endif
        </button>

        <div class="ml-auto flex items-center gap-1 text-on-surface-variant">
            <span class="material-symbols-outlined text-[14px]">autorenew</span>
            <span class="font-body-sm text-body-sm">Auto-refreshes every 8s</span>
        </div>
    </div>

    {{-- ── FAILED JOBS TAB ── --}}
    @if($tab === 'failed')
        <div class="bg-surface border border-outline-variant rounded-xl overflow-hidden shadow-sm">
            <div class="px-gutter py-4 border-b border-outline-variant flex justify-between items-center bg-surface-container-low">
                <h3 class="font-headline-md text-headline-md text-on-surface">Failed Jobs</h3>
                @if($this->failedCount > 0)
                    <div class="flex items-center gap-2">
                        <button
                            wire:click="retryAll"
                            wire:loading.attr="disabled"
                            wire:confirm="Re-queue all {{ $this->failedCount }} failed jobs?"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-primary text-white rounded-lg font-label-sm text-label-sm hover:bg-primary/90 transition-colors disabled:opacity-60"
                        >
                            <span class="material-symbols-outlined text-[15px]">replay</span>
                            Retry All
                        </button>
                        <button
                            wire:click="clearFailed"
                            wire:loading.attr="disabled"
                            wire:confirm="Permanently delete all failed jobs? This cannot be undone."
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-error/30 text-error rounded-lg font-label-sm text-label-sm hover:bg-error/10 transition-colors disabled:opacity-60"
                        >
                            <span class="material-symbols-outlined text-[15px]">delete_sweep</span>
                            Clear All
                        </button>
                    </div>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-surface-container-low border-b border-outline-variant">
                        <tr>
                            <th class="px-gutter py-3 font-label-md text-label-md text-on-surface-variant uppercase">Job</th>
                            <th class="px-gutter py-3 font-label-md text-label-md text-on-surface-variant uppercase">Queue</th>
                            <th class="px-gutter py-3 font-label-md text-label-md text-on-surface-variant uppercase">Failed At</th>
                            <th class="px-gutter py-3 font-label-md text-label-md text-on-surface-variant uppercase">Exception</th>
                            <th class="px-gutter py-3 font-label-md text-label-md text-on-surface-variant uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant">
                        @forelse($failedJobs as $job)
                            @php
                                $payload   = json_decode($job->payload, true);
                                $className = $payload['displayName'] ?? 'Unknown';
                                $shortName = class_basename($className);
                                $jobData   = isset($payload['data']['command'])
                                    ? @unserialize($payload['data']['command'])
                                    : null;
                                $messageId = $jobData?->messageId ?? null;
                                $excLines  = explode("\n", $job->exception);
                                $excFirst  = trim($excLines[0] ?? '');
                            @endphp
                            <tr
                                class="hover:bg-surface-container-low transition-colors"
                                x-data="{ expanded: false }"
                            >
                                <td class="px-gutter py-3">
                                    <p class="font-label-md text-label-md text-on-surface">{{ $shortName }}</p>
                                    @if($messageId)
                                        <p class="font-body-sm text-body-sm text-on-surface-variant">Message #{{ $messageId }}</p>
                                    @endif
                                    <p class="font-label-sm text-label-sm text-outline font-mono">{{ $job->uuid }}</p>
                                </td>
                                <td class="px-gutter py-3 font-label-sm text-label-sm text-on-surface-variant">
                                    {{ $job->queue }}
                                </td>
                                <td class="px-gutter py-3 text-body-sm text-on-surface-variant whitespace-nowrap">
                                    {{ \Carbon\Carbon::parse($job->failed_at)->format('M d, Y H:i') }}
                                    <br>
                                    <span class="text-outline">{{ \Carbon\Carbon::parse($job->failed_at)->diffForHumans() }}</span>
                                </td>
                                <td class="px-gutter py-3 max-w-xs">
                                    <p
                                        class="font-body-sm text-body-sm text-error cursor-pointer"
                                        @click="expanded = !expanded"
                                        title="Click to expand"
                                    >
                                        <span x-show="!expanded" class="line-clamp-2">{{ $excFirst }}</span>
                                        <span x-show="expanded" class="whitespace-pre-wrap break-words font-mono text-[11px]">{{ $job->exception }}</span>
                                    </p>
                                </td>
                                <td class="px-gutter py-3">
                                    <div class="flex items-center gap-2">
                                        <button
                                            wire:click="retryJob('{{ $job->uuid }}')"
                                            wire:loading.attr="disabled"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-primary text-white rounded-lg font-label-sm text-label-sm hover:bg-primary/90 transition-colors disabled:opacity-60"
                                        >
                                            <span class="material-symbols-outlined text-[14px]">replay</span>
                                            Retry
                                        </button>
                                        <button
                                            wire:click="deleteJob('{{ $job->uuid }}')"
                                            wire:loading.attr="disabled"
                                            wire:confirm="Delete this failed job permanently?"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 border border-error/30 text-error rounded-lg font-label-sm text-label-sm hover:bg-error/10 transition-colors disabled:opacity-60"
                                        >
                                            <span class="material-symbols-outlined text-[14px]">delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-gutter py-12 text-center">
                                    <span class="material-symbols-outlined text-[40px] text-green-500 block mb-2">check_circle</span>
                                    <p class="font-body-md text-body-md text-on-surface-variant">No failed jobs — everything looks healthy.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($failedJobs && $failedJobs->hasPages())
                <div class="px-gutter py-4 bg-surface border-t border-outline-variant flex flex-col sm:flex-row justify-between items-center gap-4">
                    <span class="text-body-sm text-on-surface-variant">
                        Showing <span class="font-semibold text-on-surface">{{ $failedJobs->firstItem() ?? 0 }}–{{ $failedJobs->lastItem() ?? 0 }}</span>
                        of <span class="font-semibold text-on-surface">{{ $failedJobs->total() }}</span>
                    </span>
                    {{ $failedJobs->links() }}
                </div>
            @endif
        </div>
    @endif

    {{-- ── PENDING / RUNNING JOBS TAB ── --}}
    @if($tab === 'pending')
        <div class="bg-surface border border-outline-variant rounded-xl overflow-hidden shadow-sm">
            <div class="px-gutter py-4 border-b border-outline-variant flex justify-between items-center bg-surface-container-low">
                <h3 class="font-headline-md text-headline-md text-on-surface">Pending & Running Jobs</h3>
                <span class="font-label-sm text-label-sm text-on-surface-variant">{{ $this->pendingCount }} total</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-surface-container-low border-b border-outline-variant">
                        <tr>
                            <th class="px-gutter py-3 font-label-md text-label-md text-on-surface-variant uppercase">Job</th>
                            <th class="px-gutter py-3 font-label-md text-label-md text-on-surface-variant uppercase">Queue</th>
                            <th class="px-gutter py-3 font-label-md text-label-md text-on-surface-variant uppercase">State</th>
                            <th class="px-gutter py-3 font-label-md text-label-md text-on-surface-variant uppercase">Attempts</th>
                            <th class="px-gutter py-3 font-label-md text-label-md text-on-surface-variant uppercase">Available At</th>
                            <th class="px-gutter py-3 font-label-md text-label-md text-on-surface-variant uppercase">Queued At</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant">
                        @forelse($pendingJobs as $job)
                            @php
                                $payload   = json_decode($job->payload, true);
                                $className = $payload['displayName'] ?? 'Unknown';
                                $shortName = class_basename($className);
                                $jobData   = isset($payload['data']['command'])
                                    ? @unserialize($payload['data']['command'])
                                    : null;
                                $messageId = $jobData?->messageId ?? null;
                                $isRunning = ! is_null($job->reserved_at);
                                $availableAt = \Carbon\Carbon::createFromTimestamp($job->available_at);
                                $createdAt   = \Carbon\Carbon::createFromTimestamp($job->created_at);
                            @endphp
                            <tr class="hover:bg-surface-container-low transition-colors">
                                <td class="px-gutter py-3">
                                    <p class="font-label-md text-label-md text-on-surface">{{ $shortName }}</p>
                                    @if($messageId)
                                        <p class="font-body-sm text-body-sm text-on-surface-variant">Message #{{ $messageId }}</p>
                                    @endif
                                </td>
                                <td class="px-gutter py-3 font-label-sm text-label-sm text-on-surface-variant">
                                    {{ $job->queue }}
                                </td>
                                <td class="px-gutter py-3">
                                    @if($isRunning)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold uppercase bg-[#FEF3C7] text-[#92400E]">
                                            <span class="w-1.5 h-1.5 rounded-full bg-yellow-500 inline-block animate-pulse"></span>
                                            Running
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold uppercase bg-[#DBEAFE] text-[#1E40AF]">
                                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500 inline-block"></span>
                                            Waiting
                                        </span>
                                    @endif
                                </td>
                                <td class="px-gutter py-3 font-label-md text-label-md text-on-surface">
                                    {{ $job->attempts }}
                                </td>
                                <td class="px-gutter py-3 text-body-sm text-on-surface-variant whitespace-nowrap">
                                    {{ $availableAt->diffForHumans() }}
                                </td>
                                <td class="px-gutter py-3 text-body-sm text-on-surface-variant whitespace-nowrap">
                                    {{ $createdAt->format('M d, Y H:i') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-gutter py-12 text-center">
                                    <span class="material-symbols-outlined text-[40px] text-on-surface-variant/30 block mb-2">inbox</span>
                                    <p class="font-body-md text-body-md text-on-surface-variant">Queue is empty.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($pendingJobs && $pendingJobs->hasPages())
                <div class="px-gutter py-4 bg-surface border-t border-outline-variant flex flex-col sm:flex-row justify-between items-center gap-4">
                    <span class="text-body-sm text-on-surface-variant">
                        Showing <span class="font-semibold text-on-surface">{{ $pendingJobs->firstItem() ?? 0 }}–{{ $pendingJobs->lastItem() ?? 0 }}</span>
                        of <span class="font-semibold text-on-surface">{{ $pendingJobs->total() }}</span>
                    </span>
                    {{ $pendingJobs->links() }}
                </div>
            @endif
        </div>
    @endif

</div>
