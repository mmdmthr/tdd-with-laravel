<?php

namespace Tests\Feature\Http\Controllers;

use App\Jobs\CheckWebsite;
use App\Models\Site;
use App\Models\User;
use App\Notifications\SiteAdded;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
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
        Bus::fake();

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

        // make sure the CheckWebsite job was dispatched
        Bus::assertDispatched(CheckWebsite::class, function($job) use ($site) {
            return $job->site->id === $site->id;
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

    /** @test */
    public function it_redirects_a_user_to_a_previous_site_if_they_try_to_add_a_duplicate()
    {
        Notification::fake();
        // create a user
        $user = User::factory()->create(); 

        // create a site
        $site = $user->sites()->save(Site::factory()->make());

        // make a post req to a route to create a site 
        $response = $this
            ->actingAs($user)
            ->post(route('sites.store'),
                [
                    'name' => 'Google 2',
                    'url' => $site->url, 
                ]);

        $response->assertRedirect(route('sites.show', $site));
        $response->assertSessionHasErrors(['url' => 'The site you tried to add already exists, we redirected you to it\'s page']);

        Notification::assertNothingSent();
        $this->assertEquals(1, Site::count());
    }

    /** @test */
    public function it_allows_user_to_see_their_sites()
    {
        $user = User::factory()->create();

        $site = $user->sites()->save(Site::factory()->make());

        $response = $this->actingAs($user)->get(route('sites.index'));
        $response->assertStatus(200);
        $response->assertSeeText($site->url);
        $response->assertSeeText($site->name);
        $response->assertSeeText($site->is_online ? 'Online' : 'Offline');
    }

    /** @test */
    public function it_allows_user_to_see_their_site()
    {
        $user = User::factory()->create();

        $site = $user->sites()->save(Site::factory()->make());

        $response = $this->actingAs($user)->get(route('sites.show', $site));
        $response->assertStatus(200);
        $response->assertSeeText($site->url);
        $response->assertSeeText($site->name);
        $response->assertSeeText($site->is_online ? 'Your site is online' : 'Your site is offline');
    }

    /** @test */
    public function it_allows_a_user_to_edit_the_webhook_url() 
    {
        $this->withoutExceptionHandling();

        // create a user
        $user = User::factory()->create();
        $site = $user->sites()->save(Site::factory()->make([
            'is_online' => false,
            'webhook_url' => null,
        ]));
        $webhookUrl = 'https://tddwithlaravel.com/webhook';

        // make a PUT req to a route to create a site 
        $response = $this
            ->followingRedirects()
            ->actingAs($user)
            ->put(route('sites.update', $site),
                [
                    'name' => 'Google',
                    'webhook_url' => $webhookUrl,
                ]);

        $site->refresh();
        $this->assertEquals('Google', $site->name);
        $this->assertEquals($webhookUrl, $site->webhook_url);
        $this->assertFalse($site->is_online);
        $this->assertEquals($user->id, $site->user->id);

        // see site's name on the page
        $response->assertSeeText('Google');
        $response->assertSeeText($webhookUrl);
        $this->assertEquals(route('sites.show', $site), url()->current());
    }
}
