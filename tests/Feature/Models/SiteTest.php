<?php

namespace Tests\Feature\Models;

use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use Tests\TestCase;

class SiteTest extends TestCase
{
    /** @test */
    public function it_determines_whether_the_host_is_resolved()
    {
        $site = new Site();

        $site->url = 'https://google.com';
        $this->assertTrue($site->isCurrentlyResolving());

        $site->url = 'https://' . Str::random(12) . '.com';
        $this->assertFalse($site->isCurrentlyResolving());
    }
}
