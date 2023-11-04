<?php

namespace Tests\Feature\Jobs;

use App\Jobs\CheckWebsite;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CheckWebsiteTest extends TestCase
{
    use RefreshDatabase;
    /** @test */
    public function it_properly_checks_a_website()
    {
        $user = User::factory()->create();

        $site = $user->sites()->save(Site::factory()->make());

        $this->assertEquals(0, $site->checks()->count());

        $job = new CheckWebsite($site);
        $job->handle();

        $site->refresh();
        $check = $site->checks()->first();
        $this->assertEquals(200, $check->response_status);
        $this->assertNotNull($check->response_content);
        $this->assertTrue($check->elapsed_time > 1);
        $this->assertTrue($site->is_online);
        dd($check->elapsed_time);
    }
}
