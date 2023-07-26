<?php

namespace App\Console\Commands;

use App\Http\Services\Mailer;
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
    protected $description = 'Check if failedJobs are more than 100 and send email to developers';

    private $failedJobRepository;
    private $mailer;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(FailedJobRepository $failedJobRepository, Mailer $mailer)
    {
        parent::__construct();
        $this->failedJobRepository = $failedJobRepository;
        $this->mailer = $mailer;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $failedJobsCount = $this->failedJobRepository->count();
        if ($failedJobsCount >= 100) {
            $this->mailer->dispatchEmail(view('email')->with(['count' => $failedJobsCount])->render());
        }

        return 0;
    }
}
