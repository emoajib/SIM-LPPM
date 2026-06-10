<?php

namespace App\Console\Commands;

use App\Models\Proposal;
use App\Models\User;
use App\Notifications\ReviewCompleted;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class BenchmarkNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:benchmark-notifications {count=1000 : Number of notifications to simulate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simulate load test for notification queue';

    /**
     * Execute the console command.
     */
    public function handle(NotificationService $notificationService): int
    {
        $count = (int) $this->argument('count');
        $this->info("🚀 Menyiapkan simulasi load test untuk {$count} dosen...");

        // Get a dummy proposal for context
        $proposal = Proposal::first() ?? Proposal::factory()->create(['title' => 'Proposal Simulasi Load Test']);
        $reviewer = User::role('reviewer')->first() ?? User::factory()->create();

        // Generate dummy recipients (in-memory for speed, though real tests use DB)
        $this->comment('Membuat '.number_format($count).' objek user dummy...');
        $recipients = User::factory()->count($count)->make();

        $startTime = microtime(true);
        $notificationService->send($recipients, new ReviewCompleted($proposal, $reviewer, true));
        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000; // convert to ms

        $this->newLine();
        $this->table(
            ['Metrik', 'Hasil'],
            [
                ['Jumlah Target', number_format($count).' Dosen'],
                ['Total Waktu Dispatch', number_format($duration, 2).' ms'],
                ['Rata-rata per Notifikasi', number_format($duration / $count, 4).' ms'],
                ['Kapasitas Dispatch (H) ', number_format(($count / ($duration / 1000)) * 3600).' msg/hour'],
            ]
        );

        $this->info('✅ Dispatch selesai. Semua pekerjaan telah masuk ke antrean (Queue).');
        $this->warn("💡 Pastikan 'php artisan queue:work' sedang berjalan untuk melihat proses konsumsi data secara real-time.");

        return self::SUCCESS;
    }
}
