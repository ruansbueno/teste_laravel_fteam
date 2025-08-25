<?php

namespace App\Console\Commands;

use App\Jobs\SyncFakeStoreJob;
use Illuminate\Console\Command;

class SyncFakeStoreCommand extends Command
{
    protected $signature = 'sync:fakestore {--now : Executa inline em vez de enfileirar}';
    protected $description = 'Sincroniza dados da FakeStore (enqueue ou inline)';

    public function handle(): int
    {
        if ($this->option('now')) {
            dispatch_sync(new SyncFakeStoreJob());
            $this->info('Sync executado inline.');
        } else {
            SyncFakeStoreJob::dispatch();
            $this->info('Job de sync enfileirado.');
        }
        return self::SUCCESS;
    }
}
