<?php

namespace App\Livewire\Admin;

use App\Models\IncomingMessage;
use App\Models\Message;
use Illuminate\Support\Carbon;
use Livewire\Component;

class Dashboard extends Component
{
    public function render(): \Illuminate\View\View
    {
        $today = Carbon::today();

        $counts = Message::whereDate('created_at', $today)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $total = (int) array_sum($counts);
        $successCount = ($counts['sent'] ?? 0) + ($counts['delivered'] ?? 0);
        $successRate = $total > 0 ? round($successCount / $total * 100, 1) : 0;

        $stats = [
            'total'        => $total,
            'sent'         => (int) ($counts['sent'] ?? 0),
            'delivered'    => (int) ($counts['delivered'] ?? 0),
            'failed'       => (int) ($counts['failed'] ?? 0),
            'pending'      => (int) ($counts['pending'] ?? 0),
            'success_rate' => $successRate,
        ];

        $receivedToday = IncomingMessage::whereDate('received_at', $today)->count();

        // 7-day volume — outbound and received, mapped into daily buckets
        $sevenDaysAgo = Carbon::today()->subDays(6);

        $dailyCounts = Message::where('created_at', '>=', $sevenDaysAgo)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

        $receivedDailyCounts = IncomingMessage::where('received_at', '>=', $sevenDaysAgo)
            ->selectRaw('DATE(received_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $chartData[] = [
                'label'    => $date->format('d M'),
                'count'    => (int) ($dailyCounts[$date->format('Y-m-d')] ?? 0),
                'received' => (int) ($receivedDailyCounts[$date->format('Y-m-d')] ?? 0),
            ];
        }

        // Merge outbound and incoming into a unified recent activity feed
        $recentOutbound = Message::with('device')->latest()->limit(10)->get()
            ->map(fn ($m) => [
                'type'       => 'outbound',
                'phone'      => $m->to,
                'status'     => $m->status,
                'time'       => $m->created_at,
                'time_human' => $m->created_at->diffForHumans(),
                'device'     => $m->device,
            ]);

        $recentIncoming = IncomingMessage::with('device')->latest('received_at')->limit(10)->get()
            ->map(fn ($m) => [
                'type'       => 'incoming',
                'phone'      => $m->sender,
                'status'     => null,
                'time'       => $m->received_at,
                'time_human' => $m->received_at->diffForHumans(),
                'device'     => $m->device,
            ]);

        $recentActivity = $recentOutbound->concat($recentIncoming)
            ->sortByDesc('time')
            ->take(10)
            ->values();

        return view('livewire.admin.dashboard', compact('stats', 'receivedToday', 'chartData', 'recentActivity'))
            ->layout('layouts.admin', ['pageTitle' => 'Dashboard Overview']);
    }
}
