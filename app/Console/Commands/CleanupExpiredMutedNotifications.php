<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FriendMutedNotification;

class CleanupExpiredMutedNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:cleanup-muted';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired muted notifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting cleanup of expired muted notifications...');
        
        $cleaned = FriendMutedNotification::cleanupExpired();
        
        $this->info("Cleaned up {$cleaned} expired muted notifications.");
        
        return Command::SUCCESS;
    }
}
