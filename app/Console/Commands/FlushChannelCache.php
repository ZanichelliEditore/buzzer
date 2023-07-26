<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use App\Http\Repositories\ChannelRepository;

class FlushChannelCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reset-cache:channel-keys';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all channel keys from cache';

    private $channelRepository;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->channelRepository = new ChannelRepository();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {               
        Log::info("Delete all channel keys from cache");
        $channels = $this->channelRepository->getAllNames();
        foreach($channels as $channel) {
            Cache::forget(Config::get('cache.channel_key_prefix') . $channel);
        }
        Log::info("Cache cleaned up");
    }
}
