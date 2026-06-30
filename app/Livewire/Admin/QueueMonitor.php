<?php

namespace App\Livewire\Admin;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class QueueMonitor extends Component
{
    use WithPagination;

    public string $tab = 'failed';
    public ?string $flash = null;

    public function switchTab(string $tab): void
    {
        $this->tab = $tab;
        $this->resetPage();
    }

    public function retryJob(string $uuid): void
    {
        Artisan::call('queue:retry', ['id' => [$uuid]]);
        $this->flash = "Job re-queued successfully.";
    }

    public function retryAll(): void
    {
        $count = DB::table('failed_jobs')->count();
        Artisan::call('queue:retry', ['id' => ['all']]);
        $this->flash = "All {$count} failed " . ($count === 1 ? 'job' : 'jobs') . " re-queued.";
    }

    public function deleteJob(string $uuid): void
    {
        Artisan::call('queue:forget', ['id' => $uuid]);
        $this->flash = "Failed job removed.";
    }

    public function clearFailed(): void
    {
        Artisan::call('queue:flush');
        $this->flash = "All failed jobs cleared.";
    }

    #[Computed]
    public function pendingCount(): int
    {
        return DB::table('jobs')->count();
    }

    #[Computed]
    public function runningCount(): int
    {
        return DB::table('jobs')->whereNotNull('reserved_at')->count();
    }

    #[Computed]
    public function failedCount(): int
    {
        return DB::table('failed_jobs')->count();
    }

    public function render(): \Illuminate\View\View
    {
        $pendingJobs = null;
        $failedJobs  = null;

        if ($this->tab === 'pending') {
            $pendingJobs = DB::table('jobs')
                ->orderByRaw('reserved_at IS NULL ASC')
                ->orderBy('available_at')
                ->paginate(20);
        } else {
            $failedJobs = DB::table('failed_jobs')
                ->orderByDesc('failed_at')
                ->paginate(20);
        }

        return view('livewire.admin.queue-monitor', [
            'pendingJobs' => $pendingJobs,
            'failedJobs'  => $failedJobs,
        ])->layout('layouts.admin', ['pageTitle' => 'Queue Monitor']);
    }
}
