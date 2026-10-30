<?php

namespace App\Console\Commands;

use App\Http\Services\Mailer;
use App\Http\Services\FailedJobService;
use Illuminate\Console\Command;
use App\Http\Repositories\FailedJobRepository;

class TooManyFailedJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:failed-jobs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'If failedJobs are at least 100, send to each subscriber an email with the failed jobs count for each of its channels';

    private $failedJobRepository;
    private $failedJobService;
    private $mailer;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(FailedJobRepository $failedJobRepository, FailedJobService $failedJobService, Mailer $mailer)
    {
        parent::__construct();
        $this->failedJobRepository = $failedJobRepository;
        $this->failedJobService = $failedJobService;
        $this->mailer = $mailer;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if ($this->failedJobRepository->count() < 100) {
            return 0;
        }

        foreach ($this->failedJobService->failedJobsBySubscriber() as $email => $channels) {
            $this->mailer->dispatchEmail(view('email')->with(['channels' => $channels])->render(), explode(',', $email));
        }

        return 0;
    }
}
