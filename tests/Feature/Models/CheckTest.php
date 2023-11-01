<?php

namespace Tests\Feature\Models;

use App\Models\Check;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CheckTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_determines_wether_a_check_was_successful()
    {
        $check = Check::factory()->make([
            'response_status' => 200,
        ]);

        $this->assertTrue($check->successful());

        $check->response_status = 299;
        $this->assertTrue($check->successful());

        $check->response_status = 300;
        $this->assertFalse($check->successful());

        $check->response_status = 399;
        $this->assertFalse($check->successful());
    
        $check->response_status = 400;
        $this->assertFalse($check->successful());

        $check->response_status = 499;
        $this->assertFalse($check->successful());
    
        $check->response_status = 500;
        $this->assertFalse($check->successful());

        $check->response_status = 599;
        $this->assertFalse($check->successful());
    }
}
