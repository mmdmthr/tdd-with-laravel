<?php

namespace App\Jobs;

use App\Models\Check;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class SendWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $check;

    public $site;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Check $check)
    {
        $this->check = $check;
        $this->site = $check->site;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $data = [
            'site' => $this->site->url,
            'status_code' => $this->check->response_status,
            'content' => $this->check->response_content,
            'message' => 'A check to your site failed.',
            'happened_at' => now()->toDateTimeString(),
        ];

        Http::post($this->site->webhook_url, $data);

        $this->check->webhookCalls()->create([
            'url' => $this->site->webhook_url,
            'data' => $data,
        ]);
    }
}
