<?php

namespace Tests\Feature\Jobs;

use App\Jobs\CheckWebsite;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Http;
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
        dd($check->elapsed_time);
    }
}
