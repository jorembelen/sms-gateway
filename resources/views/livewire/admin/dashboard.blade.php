<div class="space-y-stack_gap_lg">

    {{-- Stat Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-stack_gap_md">

        <div class="bg-surface border border-outline-variant p-4 rounded-lg flex flex-col gap-1 relative overflow-hidden">
            <div class="absolute top-4 right-4 w-10 h-10 bg-primary/10 flex items-center justify-center rounded">
                <span class="material-symbols-outlined text-primary" style="font-variation-settings:'FILL' 1">dataset</span>
            </div>
            <span class="font-body-sm text-body-sm text-on-surface-variant uppercase tracking-wider">Total Today</span>
            <span class="font-display-sm text-display-sm">{{ number_format($stats['total']) }}</span>
            <div class="flex items-center gap-1 text-[11px] text-on-surface-variant">
                <span>All statuses combined</span>
            </div>
        </div>

        <div class="bg-surface border border-outline-variant p-4 rounded-lg flex flex-col gap-1 relative overflow-hidden">
            <div class="absolute top-4 right-4 w-10 h-10 bg-blue-100 flex items-center justify-center rounded">
                <span class="material-symbols-outlined text-blue-600" style="font-variation-settings:'FILL' 1">send</span>
            </div>
            <span class="font-body-sm text-body-sm text-on-surface-variant uppercase tracking-wider">Sent</span>
            <span class="font-display-sm text-display-sm">{{ number_format($stats['sent']) }}</span>
            <div class="flex items-center gap-1 text-[11px] text-on-surface-variant">
                @if($stats['total'] > 0)
                    <span>{{ round($stats['sent'] / $stats['total'] * 100, 1) }}% of total</span>
                @else
                    <span>No messages today</span>
                @endif
            </div>
        </div>

        <div class="bg-surface border border-outline-variant p-4 rounded-lg flex flex-col gap-1 relative overflow-hidden">
            <div class="absolute top-4 right-4 w-10 h-10 bg-error/10 flex items-center justify-center rounded">
                <span class="material-symbols-outlined text-error" style="font-variation-settings:'FILL' 1">error</span>
            </div>
            <span class="font-body-sm text-body-sm text-on-surface-variant uppercase tracking-wider">Failed</span>
            <span class="font-display-sm text-display-sm">{{ number_format($stats['failed']) }}</span>
            @if($stats['failed'] > 0)
                <div class="flex items-center gap-1 text-[11px] text-error font-bold">
                    <span class="material-symbols-outlined text-[14px]">warning</span>
                    <span>Needs attention</span>
                </div>
            @else
                <div class="text-[11px] text-green-600 font-bold">No failures today</div>
            @endif
        </div>

        <div class="bg-surface border border-outline-variant p-4 rounded-lg flex flex-col gap-1 relative overflow-hidden">
            <div class="absolute top-4 right-4 w-10 h-10 bg-secondary-container flex items-center justify-center rounded">
                <span class="material-symbols-outlined text-primary" style="font-variation-settings:'FILL' 1">schedule</span>
            </div>
            <span class="font-body-sm text-body-sm text-on-surface-variant uppercase tracking-wider">Pending</span>
            <span class="font-display-sm text-display-sm">{{ number_format($stats['pending']) }}</span>
            <div class="flex items-center gap-1 text-[11px] text-on-surface-variant">
                <span>Queue processing</span>
            </div>
        </div>

        <div class="bg-surface border border-outline-variant p-4 rounded-lg flex flex-col gap-1 relative overflow-hidden">
            <div class="absolute top-4 right-4 w-10 h-10 bg-primary/10 flex items-center justify-center rounded">
                <span class="material-symbols-outlined text-primary" style="font-variation-settings:'FILL' 1">verified</span>
            </div>
            <span class="font-body-sm text-body-sm text-on-surface-variant uppercase tracking-wider">Success Rate</span>
            <span class="font-display-sm text-display-sm">{{ $stats['success_rate'] }}%</span>
            <div class="flex items-center gap-1 text-[11px] {{ $stats['success_rate'] >= 90 ? 'text-green-600' : 'text-error' }} font-bold">
                <span>{{ $stats['success_rate'] >= 90 ? 'Above threshold' : 'Below threshold' }}</span>
            </div>
        </div>
    </div>

    {{-- 7-Day Volume Chart --}}
    <div class="bg-surface border border-outline-variant rounded-lg p-gutter shadow-sm">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h3 class="font-headline-md text-headline-md">Message Volume</h3>
                <p class="font-body-sm text-body-sm text-on-surface-variant">Activity trends over the last 7 days</p>
            </div>
        </div>

        <div
            class="h-64 w-full relative"
            x-data="{
                chart: null,
                init() {
                    const labels = @js(array_column($chartData, 'label'));
                    const counts = @js(array_column($chartData, 'count'));
                    this.chart = new Chart(this.$refs.canvas, {
                        type: 'line',
                        data: {
                            labels,
                            datasets: [{
                                data: counts,
                                borderColor: '#3525cd',
                                backgroundColor: 'rgba(53,37,205,0.08)',
                                borderWidth: 3,
                                tension: 0.4,
                                fill: true,
                                pointBackgroundColor: '#3525cd',
                                pointRadius: 4,
                                pointHoverRadius: 6,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                x: {
                                    grid: { color: '#E2E8F0', drawBorder: false },
                                    ticks: { font: { family: 'JetBrains Mono', size: 11 }, color: '#464555' }
                                },
                                y: {
                                    beginAtZero: true,
                                    grid: { color: '#E2E8F0', drawBorder: false },
                                    ticks: { font: { family: 'JetBrains Mono', size: 11 }, color: '#464555', precision: 0 }
                                }
                            }
                        }
                    });
                }
            }"
            x-init="init()"
        >
            <canvas x-ref="canvas" class="w-full h-full"></canvas>
        </div>
    </div>

    {{-- Recent Activity --}}
    <div class="bg-surface border border-outline-variant rounded-lg overflow-hidden shadow-sm">
        <div class="p-4 border-b border-outline-variant flex justify-between items-center">
            <h3 class="font-headline-md text-headline-md">Recent Activity</h3>
            <a
                href="{{ route('admin.messages') }}"
                class="text-primary font-label-md text-label-md flex items-center gap-1 hover:underline"
            >
                View all <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-surface-container-low">
                    <tr>
                        <th class="px-gutter py-3 font-label-sm text-label-sm text-on-surface-variant border-b border-outline-variant uppercase">ID</th>
                        <th class="px-gutter py-3 font-label-sm text-label-sm text-on-surface-variant border-b border-outline-variant uppercase">Recipient</th>
                        <th class="px-gutter py-3 font-label-sm text-label-sm text-on-surface-variant border-b border-outline-variant uppercase">Status</th>
                        <th class="px-gutter py-3 font-label-sm text-label-sm text-on-surface-variant border-b border-outline-variant uppercase">Created At</th>
                        <th class="px-gutter py-3 font-label-sm text-label-sm text-on-surface-variant border-b border-outline-variant uppercase">Device</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant">
                    @forelse($recentMessages as $message)
                        <tr class="hover:bg-surface-container-low transition-colors">
                            <td class="px-gutter py-3 font-label-sm text-label-sm text-on-surface">#{{ $message->id }}</td>
                            <td class="px-gutter py-3 font-body-md text-body-md">{{ $message->to }}</td>
                            <td class="px-gutter py-3">
                                @php
                                    $badge = match($message->status) {
                                        'delivered' => 'bg-green-100 text-green-700',
                                        'sent'      => 'bg-blue-100 text-blue-700',
                                        'failed'    => 'bg-red-100 text-red-700',
                                        'pending'   => 'bg-amber-100 text-amber-700',
                                        default     => 'bg-gray-100 text-gray-700',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold uppercase {{ $badge }}">
                                    {{ $message->status }}
                                </span>
                            </td>
                            <td class="px-gutter py-3 font-body-sm text-body-sm text-on-surface-variant">
                                {{ $message->created_at->diffForHumans() }}
                            </td>
                            <td class="px-gutter py-3 font-label-sm text-label-sm text-primary">
                                {{ $message->device ? '#' . $message->device->id : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-gutter py-8 text-center text-body-sm text-on-surface-variant">
                                No messages yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
