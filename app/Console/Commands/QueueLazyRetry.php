<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use App\Http\Repositories\FailedJobRepository;

class QueueLazyRetry extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:lazy-retry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retry up to 1000 failed jobs, 50 jobs every 30 seconds';

    private $failedJobRepository;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(FailedJobRepository $failedJobRepository)
    {
        parent::__construct();
        $this->failedJobRepository = $failedJobRepository;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $jobsBatches = $this->failedJobRepository->all(null, "id", "ASC", 1000)->pluck("id")->chunk(50);
        $delay = 0;
        foreach ($jobsBatches as $batch) {
            Artisan::queue('queue:retry ' . $batch->implode(" "))->onQueue($this->getQueue())->delay($delay);
            $delay += 30;
        }

        return 0;
    }

    private function getQueue(): string
    {
        if (config('queue.default') === "redis") return "{default}";
        return "default";
    }
}
