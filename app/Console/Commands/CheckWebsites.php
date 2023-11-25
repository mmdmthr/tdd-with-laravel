<?php

namespace App\Console\Commands;

use App\Jobs\CheckWebsite;
use App\Models\Site;
use Illuminate\Console\Command;

class CheckWebsites extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sites:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs a check for all sites';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $sites = Site::all();
        
        foreach ($sites as $site) {
            CheckWebsite::dispatch($site);
        }
    }
}
