<?php

namespace Tests\Feature\Jobs;

use App\Jobs\CheckWebsite;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class CheckWebsiteTest extends TestCase
{
    use RefreshDatabase;
    
    /** @test */
    public function it_properly_checks_a_website()
    {
        $user = User::factory()->create();

        $site = $user->sites()->save(Site::factory()->make([
                'url' => 'https://google.com',
        ]));

        Http::fake(function($request) {
            usleep(200 * 1000);
            return Http::response($this->bigResponse(), 200);
        });

        $this->assertEquals(0, $site->checks()->count());

        $job = new CheckWebsite($site);
        $job->handle();

        $site->refresh();
        $check = $site->checks()->first();
        $this->assertEquals(200, $check->response_status);
        $this->assertEquals(Str::limit($this->bigResponse(), 500, ''), $check->response_content);
        $this->assertTrue($check->elapsed_time >= 200);
        $this->assertTrue($site->is_online);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://google.com';
        });
    }

    /** 
     * @test
     * @dataProvider failureCodes
     */
    public function it_handles_failures($failureCode)
    {
        $user = User::factory()->create();

        $site = $user->sites()->save(Site::factory()->make([
                'url' => 'https://google.com',
        ]));

        Http::fake(function($request) use ($failureCode) {
            usleep(200 * 1000);
            return Http::response('<h1>Failure</h1>', $failureCode);
        });

        $this->assertEquals(0, $site->checks()->count());

        $job = new CheckWebsite($site);
        $job->handle();

        $site->refresh();
        $check = $site->checks()->first();
        $this->assertEquals($failureCode, $check->response_status);
        $this->assertEquals('<h1>Failure</h1>', $check->response_content);
        $this->assertTrue($check->elapsed_time >= 20);
        $this->assertFalse($site->is_online);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://google.com';
        });
    }

    /** @test */
    public function it_handles_hosts_that_do_not_resolve()
    {
        $user = User::factory()->create();

        $randomUrl = 'https://' . Str::random(12) . '.com';

        $site = $user->sites()->save(Site::factory()->make([
                'url' => $randomUrl,
        ]));
    
        $this->assertEquals(0, $site->checks()->count());

        $job = new CheckWebsite($site);
        $job->handle();

        $site->refresh();
        $this->assertEquals(0, $site->checks()->count());
        $this->assertFalse($site->is_online);
        $this->assertFalse($site->is_resolving);
    }

    /** @test */
    public function it_updates_the_resolving_status_of_a_site_that_was_not_resolving()
    {
        $user = User::factory()->create();

        $site = $user->sites()->save(Site::factory()->make([
                'url' => 'https://google.com',
                'is_resolving' => false,
        ]));

        Http::fake(function($request) {
            usleep(200 * 1000);
            return Http::response('<h1>Success</h1>', 200);
        });

        $this->assertEquals(0, $site->checks()->count());

        $job = new CheckWebsite($site);
        $job->handle();

        $site->refresh();
        $check = $site->checks()->first();
        $this->assertEquals(200, $check->response_status);
        $this->assertEquals('<h1>Success</h1>', $check->response_content);
        $this->assertTrue($check->elapsed_time >= 200);
        $this->assertTrue($site->is_online);
        $this->assertTrue($site->is_resolving);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://google.com';
        });
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
}
