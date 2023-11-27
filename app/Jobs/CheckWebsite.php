<?php

namespace App\Jobs;

use App\Models\Site;
use App\Notifications\SiteIsDown;
use App\Notifications\SiteIsUp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CheckWebsite implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $site;

    public $elapsedTime;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Site $site)
    {
        $this->site = $site;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (!$this->site->isCurrentlyResolving()) {
            $this->site->update([
                'is_resolving' => false,
                'is_online' => false,
            ]);

            return;
        }

        $response = $this->measureTime(fn () => Http::get($this->site->url));

        $check = $this->site->checks()->create([
            'response_status' => $response->status(),
            'response_content' => Str::limit($response->body(), 500, ''),
            'elapsed_time' => $this->elapsedTime,
        ]);

        if ($check->failed() && $this->site->is_online) {
            $this->site->user->notify(new SiteIsDown($this->site, $check));
        } elseif($check->successful() && !$this->site->is_online) {
            $this->site->user->notify(new SiteIsUp($this->site, $check));
        }

        $this->site->update([
            'is_online' => $check->successful(),
            'is_resolving' => true,
        ]);
    }

    protected function measureTime($closure)
    {
        $startTime = microtime(true);

        return tap($closure(), function () use ($startTime) {
            $endTime = microtime(true);
            $elapsedTime = ($endTime - $startTime) * 1000;
            $this->elapsedTime = (int) $elapsedTime;
        });
    }
}
