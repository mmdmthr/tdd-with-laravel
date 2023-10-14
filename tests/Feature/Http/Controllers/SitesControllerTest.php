<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SitesControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_create_sites() 
    {
        $this->withoutExceptionHandling();

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
    }

    /** @test */
    public function it_only_allows_authenticated_user_to_create_sites()
    {
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
    }

    /** @test */
    public function it_requires_all_fields_to_be_present()
    {
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
    }
}
