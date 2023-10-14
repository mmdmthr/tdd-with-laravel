<?php

namespace Tests\Feature\Http\Requests;

use App\Http\Requests\StoreSiteRequest;
use App\Rules\ValidProtocol;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class StoreSiteRequestTest extends TestCase
{
    /** @test */
    public function it_has_correct_rules()
    {
        $expectedRules = [
            'name' => ['required', 'string'],
            'url' => ['required', 'string', new ValidProtocol],
        ];

        $request = new StoreSiteRequest();

        $this->assertEquals($expectedRules, $request->rules());
    }

    /** @test */
    public function it_authorizes_every_users()
    {
        $request = new StoreSiteRequest();

        $this->assertTrue($request->authorize());
    }
}
