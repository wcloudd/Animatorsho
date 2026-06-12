<?php

namespace App\Console\Commands;

use App\Models\SecurityEvent;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('security:prune-events {--days= : Override configured retention days for this run} {--dry-run : Show how many records would be deleted without deleting them}')]
#[Description('Delete security_events records older than the configured retention window')]
class PruneSecurityEvents extends Command
{
    public function handle(): int
    {
        $days = $this->resolveRetentionDays();

        if ($days === null) {
            $this->warn('Retention days must be a positive integer. No security events were deleted.');

            return self::FAILURE;
        }

        $cutoff = now()->subDays($days);
        $query = SecurityEvent::query()->where('occurred_at', '<', $cutoff);
        $count = (clone $query)->count();

        if ($count === 0) {
            $this->info("No security events older than {$days} days were found.");

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info("Dry run: {$count} security event(s) would be deleted (older than {$days} days, before {$cutoff->toDateTimeString()}).");

            return self::SUCCESS;
        }

        $deleted = $query->delete();

        $this->info("Deleted {$deleted} security event(s) older than {$days} days.");

        return self::SUCCESS;
    }

    private function resolveRetentionDays(): ?int
    {
        $override = $this->option('days');

        if ($override !== null && $override !== '') {
            if (! is_numeric($override)) {
                return null;
            }

            $days = (int) $override;

            return $days > 0 ? $days : null;
        }

        $configured = config('security.logging.database_retention_days');

        if ($configured === null || ! is_numeric($configured)) {
            return null;
        }

        $days = (int) $configured;

        return $days > 0 ? $days : null;
    }
}
