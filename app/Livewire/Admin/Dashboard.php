<?php

namespace App\Livewire\Admin;

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

        // 7-day volume — single query then map into daily buckets
        $sevenDaysAgo = Carbon::today()->subDays(6);
        $dailyCounts = Message::where('created_at', '>=', $sevenDaysAgo)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $chartData[] = [
                'label' => $date->format('d M'),
                'count' => (int) ($dailyCounts[$date->format('Y-m-d')] ?? 0),
            ];
        }

        $recentMessages = Message::with('device')->latest()->limit(10)->get();

        return view('livewire.admin.dashboard', compact('stats', 'chartData', 'recentMessages'))
            ->layout('layouts.admin', ['pageTitle' => 'Dashboard Overview']);
    }
}
