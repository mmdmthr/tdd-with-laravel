<?php

namespace Tests\Feature\Jobs;

use App\Jobs\SendWebhook;
use App\Models\Check;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SendWebhookTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_sends_a_failure_webhook_to_a_given_url()
    {
        Http::fake();

        $user = User::factory()->create();

        $site = $user->sites()->save(Site::factory()->make([
            'url' => 'https://google.com',
            'is_online' => false,
            'webhook_url' => 'https://tddwithlaravel.com/webhook',
        ]));

        $check = $site->checks()->save(Check::factory()->make([
            'response_status' => 500,
            'response_content' => 'Foo',
            'elapsed_time' => 20,
        ]));

        $job = new SendWebhook($check);
        $job->handle();

        // assert there is a webhook call
        $webhookCall = $check->webhookCalls()->first();
        $this->assertNotNull($webhookCall);

        // assert the webhook call url is equal to the webhook url
        $this->assertEquals($site->webhook_url, $webhookCall->url);

        // assert that the data send is as expected
        $expectedData = [
            'site' => $site->url,
            'status_code' => 500,
            'content' => $check->response_content,
            'message' => 'A check to your site failed.',
            'happened_at' => now()->toDateTimeString(), // 2020-11-19 00:00:00
        ];
        $this->assertEquals($expectedData, $webhookCall->data);

        // assert that the http call was made

        Http::assertSent(function ($request) use ($site, $check) {
            return $request->url() === $site->webhook_url
                && $request['site'] === $site->url
                && $request['status_code'] === 500
                && $request['content'] === $check->response_content
                && $request['message'] === 'A check to your site failed.'
                && $request['happened_at'] === now()->toDateTimeString();
        });
    }
}
