<?php

namespace Tests\Feature\Notifications;

use App\Models\Site;
use App\Models\User;
use App\Notifications\SiteAdded;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SiteAddedTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_sends_the_correct_message()
    {
        $user = User::factory()->create();

        $site = $user->sites()->save(Site::factory()->make());

        $notification = new SiteAdded($site);
        $message = $notification->toMail($user);

        $this->assertEquals("Hello {$user->name},", $message->introLines[0]);
        $this->assertEquals("We are just informing that the site {$site->url} was added to your account.", $message->introLines[1]);
        $this->assertEquals('See Site', $message->actionText);
        $this->assertEquals(route('sites.show', $site), $message->actionUrl);
        $this->assertEquals('New site added to your account', $message->subject);
    }

    /** @test */
    public function it_only_sends_it_by_mail()
    {
        $user = User::factory()->create();

        $site = $user->sites()->save(Site::factory()->make());

        $notification = new SiteAdded($site);
        $this->assertEquals(['mail'], $notification->via($user));
    }
}
