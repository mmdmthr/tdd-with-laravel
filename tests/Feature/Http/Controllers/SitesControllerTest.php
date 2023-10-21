<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Site;
use App\Models\User;
use App\Notifications\SiteAdded;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SitesControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_create_sites_and_sends_a_notification_to_the_user() 
    {
        $this->withoutExceptionHandling();
        Notification::fake();

        // create a user
        $user = User::factory()->create(); 

        // make a post req to a route to create a site 
        $response = $this
            ->followingRedirects()
            ->actingAs($user)
            ->post(route('sites.store'),
                [
                    'name' => 'Google',
                    'url' => 'https://google.com', 
                ]);

        // make sure the sites exists within the database
        $site = Site::first();
        $this->assertEquals(1, Site::count());
        $this->assertEquals('Google', $site->name);
        $this->assertEquals('https://google.com', $site->url);
        $this->assertNull($site->is_online);
        $this->assertEquals($user->id, $site->user->id);

        // see site's name on the page
        $response->assertSeeText('Google');
        $this->assertEquals(route('sites.show', $site), url()->current());

        // make sure notification was sent
        Notification::assertSentTo($user, SiteAdded::class, function($notification) use ($site) {
            return $notification->site->id === $site->id;
        });
    }

    /** @test */
    public function it_only_allows_authenticated_user_to_create_sites()
    {
        Notification::fake();
        // make a post req to a route to create a site 
        $response = $this
            ->followingRedirects()
            ->post(route('sites.store'),
                [
                    'name' => 'Google',
                    'url' => 'https://google.com', 
                ]);

        // make sure no sites exists in the database
        $this->assertEquals(0, Site::count());

        // see site's name on the page
        $response->assertSeeText('Log in');
        $this->assertEquals(route('login'), url()->current());
        Notification::assertNothingSent();
    }

    /** @test */
    public function it_requires_all_fields_to_be_present()
    {
        Notification::fake();
        // create a user
        $user = User::factory()->create(); 

        // make a post req to a route to create a site 
        $response = $this
            ->actingAs($user)
            ->post(route('sites.store'),
                [
                    'name' => '',
                    'url' => '', 
                ]);

        // make sure no sites exists within the database
        $this->assertEquals(0, Site::count());

        $response->assertSessionHasErrors(['name', 'url']);
        Notification::assertNothingSent();
    }

    /** @test */
    public function it_requires_the_url_to_have_valid_protocol()
    {
        Notification::fake();
        // create a user
        $user = User::factory()->create(); 

        // make a post req to a route to create a site 
        $response = $this
            ->actingAs($user)
            ->post(route('sites.store'),
                [
                    'name' => 'Google',
                    'url' => 'google.com', 
                ]);

        // make sure no sites exists within the database
        $this->assertEquals(0, Site::count());

        $response->assertSessionHasErrors(['url']);
        Notification::assertNothingSent();
    }
}
