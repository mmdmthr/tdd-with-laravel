<?php

namespace Tests\Feature\Jobs;

use App\Jobs\CheckWebsite;
use App\Jobs\SendWebhook;
use App\Models\Check;
use App\Models\Site;
use App\Models\User;
use App\Notifications\SiteStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class CheckWebsiteTest extends TestCase
{
    use RefreshDatabase;
    
    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Bus::fake();
    }
    /** @test */
    public function it_properly_checks_a_website()
    {
        [$user, $site] = $this->createUserAndSite();

        Http::fake(function($request) {
            usleep(200 * 1000);
            return Http::response($this->bigResponse(), 200);
        });

        $this->assertEquals(0, $site->checks()->count());

        (new CheckWebsite($site))->handle();

        $site->refresh();
        $check = $site->checks()->first();
        $this->assertEquals(200, $check->response_status);
        $this->assertEquals(Str::limit($this->bigResponse(), 500, ''), $check->response_content);
        $this->assertTrue($check->elapsed_time >= 200);
        $this->assertTrue($site->is_online);

        // assert we sent an http call to google.com
        Http::assertSent(function ($request) {
            return $request->url() === 'https://google.com';
        });

        // no webhook as sent back
        Http::assertNotSent(function ($request) use ($site) {
            return $request->url() === $site->webhook_url;
        });

        Notification::assertNothingSent();

        // assert no webhook job was dispatched
        Bus::assertNotDispatched(SendWebhook::class);
    }

    /** @test */
    public function it_sends_a_notification_once_a_site_comes_back_online()
    {
        [$user, $site] = $this->createUserAndSite('https://google.com', false);

        Http::fake(function($request) {
            usleep(200 * 1000);
            return Http::response($this->bigResponse(), 200);
        });

        $this->assertEquals(0, $site->checks()->count());

        (new CheckWebsite($site))->handle();

        $site->refresh();
        $check = $site->checks()->first();
        $this->assertEquals(200, $check->response_status);
        $this->assertEquals(Str::limit($this->bigResponse(), 500, ''), $check->response_content);
        $this->assertTrue($check->elapsed_time >= 200);
        $this->assertTrue($site->is_online);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://google.com';
        });

        Notification::assertSentTo($user, SiteStatusChanged::class, function($notification) use ($site, $check) {
            return 
                $notification->site->id === $site->id
                && $notification->check->id === $check->id;
        });

        // assert no webhook job was dispatched
        Bus::assertNotDispatched(SendWebhook::class);
    }

    /** 
     * @test
     * @dataProvider failureCodes
     */
    public function it_handles_failures($failureCode)
    {
        [$user, $site] = $this->createUserAndSite('https://google.com', true, null);

        Http::fake(function($request) use ($failureCode) {
            usleep(200 * 1000);
            return Http::response('<h1>Failure</h1>', $failureCode);
        });

        $this->assertEquals(0, $site->checks()->count());

        (new CheckWebsite($site))->handle();

        $site->refresh();
        $check = $site->checks()->first();
        $this->assertEquals($failureCode, $check->response_status);
        $this->assertEquals('<h1>Failure</h1>', $check->response_content);
        $this->assertTrue($check->elapsed_time >= 20);
        $this->assertFalse($site->is_online);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://google.com';
        });

        Notification::assertSentTo($user, SiteStatusChanged::class, function ($notification) use ($site, $check) {
            return 
                $notification->site->id === $site->id
                && $notification->check->id === $check->id;
        });

        // assert no webhook job was dispatched
        Bus::assertNotDispatched(SendWebhook::class);
    }

    /** @test */
    public function it_does_not_send_a_failure_notification_multiple_times()
    {
        [$user, $site] = $this->createUserAndSite('https://google.com', false, null);

        $firstCheck = $site->checks()->save(Check::factory()->make([
            'response_status' => 500,
            'response_content' => 'Foo',
            'elapsed_time' => 20,
        ]));

        Http::fake(function($request) {
            usleep(1000);
            return Http::response('<h1>Failure</h1>', 500);
        });

        $this->assertEquals(1, $site->checks()->count());

        (new CheckWebsite($site))->handle();

        $site->refresh();
        $check = $site->checks()->latest('id')->first();
        $this->assertEquals(500, $check->response_status);
        $this->assertEquals('<h1>Failure</h1>', $check->response_content);
        $this->assertTrue($check->elapsed_time >= 1);
        $this->assertFalse($site->is_online);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://google.com';
        });

        Notification::assertNothingSent();

        // assert no webhook job was dispatched
        Bus::assertNotDispatched(SendWebhook::class);
    }

    /** @test */
    public function it_handles_hosts_that_do_not_resolve()
    {
        $randomUrl = 'https://' . Str::random(12) . '.com';
        [$user, $site] = $this->createUserAndSite($randomUrl, true, null);
    
        $this->assertEquals(0, $site->checks()->count());

        (new CheckWebsite($site))->handle();

        $site->refresh();
        $this->assertEquals(0, $site->checks()->count());
        $this->assertFalse($site->is_online);
        $this->assertFalse($site->is_resolving);

        // assert no webhook job was dispatched
        Bus::assertNotDispatched(SendWebhook::class);
    }

    /** @test */
    public function it_updates_the_resolving_status_of_a_site_that_was_not_resolving()
    {
        [$user, $site] = $this->createUserAndSite('https://google.com', false, null);

        Http::fake(function($request) {
            usleep(200 * 1000);
            return Http::response('<h1>Success</h1>', 200);
        });

        $this->assertEquals(0, $site->checks()->count());

        (new CheckWebsite($site))->handle();

        $site->refresh();
        $check = $site->checks()->first();
        $this->assertEquals(200, $check->response_status);
        $this->assertEquals('<h1>Success</h1>', $check->response_content);
        $this->assertTrue($check->elapsed_time >= 200);
        $this->assertTrue($site->is_online);
        $this->assertTrue($site->is_resolving);

        // assert no webhook job was dispatched
        Bus::assertNotDispatched(SendWebhook::class);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://google.com';
        });
    }

    /** @test */
    public function it_sends_a_webhook_callback_on_failures()
    {
        [$user, $site] = $this->createUserAndSite('https://google.com', false, 'https://tddwithlaravel.com/webhook');

        $firstCheck = $site->checks()->save(Check::factory()->make([
            'response_status' => 500,
            'response_content' => 'Foo',
            'elapsed_time' => 20,
        ]));

        Http::fake(function($request) {
            usleep(1000);
            return Http::response('<h1>Failure</h1>', 500);
        });

        $this->assertEquals(1, $site->checks()->count());

        (new CheckWebsite($site))->handle();

        $site->refresh();
        $check = $site->checks()->latest('id')->first();
        $this->assertEquals(500, $check->response_status);
        $this->assertEquals('<h1>Failure</h1>', $check->response_content);
        $this->assertTrue($check->elapsed_time >= 1);
        $this->assertFalse($site->is_online);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://google.com';
        });

        Bus::assertDispatched(SendWebhook::class, function ($job) use ($check) {
            return $job->check->is($check);
        });

        Notification::assertNothingSent();
    }

    protected function bigResponse()
    {
        return file_get_contents(base_path('tests/Fixture/laravel_com_response.txt'));
    }

    public function failureCodes(): array
    {
        return $this->failureHttpCodes()
                ->map(fn ($code) => [$code])
                ->toArray();
    }

    protected function createUserAndSite(
        $url = 'https://google.com',
        $isOnline = true,
        $webhookUrl = null
    ) {
        $user = User::factory()->create();

        $site = $user->sites()->save(Site::factory()->make([
            'url' => $url,
            'is_online' => $isOnline,
            'webhook_url' => $webhookUrl,
        ]));

        return [$user, $site];
    }
}
