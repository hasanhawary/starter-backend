<?php

namespace App\Console\Commands;

use App\Enum\Subscription\SubscriptionStatusEnum;
use App\Models\Central\Subscription;
use Illuminate\Console\Command;

class CheckExpiredSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:check-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and update expired subscriptions';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking for expired subscriptions...');

        $expiredCount = Subscription::where('status', 'active')
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', now())
            ->update(['status' => SubscriptionStatusEnum::Expired->value]);

        $this->info("Updated {$expiredCount} expired subscription(s).");

        // Check for subscriptions expiring soon (within 7 days)
        $expiringSoon = Subscription::where('status', 'active')
            ->whereNotNull('ends_at')
            ->whereBetween('ends_at', [now(), now()->addDays(7)])
            ->count();

        if ($expiringSoon > 0) {
            $this->warn("{$expiringSoon} subscription(s) will expire within 7 days.");
        }

        return Command::SUCCESS;
    }
}
