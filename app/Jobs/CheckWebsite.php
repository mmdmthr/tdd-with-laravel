<?php

namespace App\Jobs;

use App\Models\Check;
use App\Models\Site;
use App\Notifications\SiteStatusChanged;
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

        if ($this->shouldSendNotification($check)) {
            $this->site->user->notify(new SiteStatusChanged($this->site, $check));
        }

        if ($check->failed() && $this->site->webhook_url) {
            Http::post($this->site->webhook_url, [
                'site' => $this->site->url,
                'status_code' => $check->response_status,
                'content' => $check->response_content,
                'message' => 'A check to your site failed.',
                'happened_at' => now()->toDateTimeString(),
            ]);
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

    protected function shouldSendNotification(Check $check)
    {
        return ($check->failed() && $this->site->is_online)
            || ($check->successful() && !$this->site->is_online);
    }
}
