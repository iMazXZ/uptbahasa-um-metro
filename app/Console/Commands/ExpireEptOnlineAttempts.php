<?php

namespace App\Console\Commands;

use App\Support\EptOnlineAttemptFinalizer;
use Illuminate\Console\Command;

class ExpireEptOnlineAttempts extends Command
{
    protected $signature = 'ept-online:expire-attempts {--limit=250 : Maximum attempts to process in one run}';

    protected $description = 'Finalize EPT Online attempts whose section deadlines already passed.';

    public function handle(EptOnlineAttemptFinalizer $finalizer): int
    {
        $count = $finalizer->finalizeExpiredAttempts((int) $this->option('limit'));

        $this->info("Finalized expired EPT Online attempts: {$count}");

        return self::SUCCESS;
    }
}
