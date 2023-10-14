<?php

namespace Tests\Unit\Rules;

use App\Rules\ValidProtocol;
use PHPUnit\Framework\TestCase;

class ValidProtocolTest extends TestCase
{
    /** @test */
    public function it_only_allows_http_or_https()
    {
        $validProtocol = new ValidProtocol;

        $this->assertTrue($validProtocol->passes('url', 'https://google.com'));
        $this->assertTrue($validProtocol->passes('url', 'http://google.com'));
        $this->assertFalse($validProtocol->passes('url', 'httpsgoogle.com'));
        $this->assertFalse($validProtocol->passes('url', 'https:google.com'));
        $this->assertFalse($validProtocol->passes('url', 'ftp://google.com'));
        $this->assertFalse($validProtocol->passes('url', 'https:/google.com'));
    }

    /** @test */
    public function it_returns_proper_message()
    {
        $validProtocol = new ValidProtocol;
        $properMessage = 'The URL must include the protocol, e.g: http:// or https://.';

        $this->assertEquals($properMessage, $validProtocol->message());
    }
}
