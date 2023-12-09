<?php

namespace Tests\Feature\Notifications;

use App\Models\Check;
use App\Models\Site;
use App\Models\User;
use App\Notifications\SiteStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SiteStatusChangedTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_sends_the_correct_message_for_when_the_site_came_back_up()
    {
        $user = User::factory()->create();

        $site = $user->sites()->save(Site::factory()->make());
        $check = $site->checks()->save(Check::factory()->make(['response_status' => 200]));

        $notification = new SiteStatusChanged($site, $check);
        $message = $notification->toMail($user);

        $this->assertEquals("Hello {$user->name},", $message->introLines[0]);
        $this->assertEquals("We are just informing that just now, {$check->created_at}, the site {$site->url} is now online.", $message->introLines[1]);
        $this->assertEquals("See Site", $message->actionText);
        $this->assertEquals(route('sites.show', $site), $message->actionUrl);
        $this->assertEquals("Your site {$site->url} is online again", $message->subject);
    }

    public function it_sends_the_correct_message_for_when_the_site_goes_down()
    {
        $user = User::factory()->create();

        $site = $user->sites()->save(Site::factory()->make());
        $check = $site->checks()->save(Check::factory()->make(['response_status' => 500]));

        $notification = new SiteStatusChanged($site, $check);
        $message = $notification->toMail($user);

        $this->assertEquals("Hello {$user->name},", $message->introLines[0]);
        $this->assertEquals("We are just informing that just now, {$check->created_at}, the site {$site->url} went online.", $message->introLines[1]);
        $this->assertEquals("See Site", $message->actionText);
        $this->assertEquals(route('sites.show', $site), $message->actionUrl);
        $this->assertEquals("Your site {$site->url} is online again", $message->subject);
    }

    /** @test */
    public function it_only_sends_it_by_mail()
    {
        $user = User::factory()->create();

        $site = $user->sites()->save(Site::factory()->make());
        $check = $site->checks()->save(Check::factory()->make());

        $notification = new SiteStatusChanged($site, $check);
        $this->assertEquals(['mail'], $notification->via($user));
    }
}
