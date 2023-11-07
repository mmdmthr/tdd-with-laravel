<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public function failureHttpCodes(): Collection {
        return collect(json_decode(file_get_contents('tests/http_status_codes.json'), true))
                ->map(fn ($code) => $code['code'])
                ->reject(fn ($code) => Str::contains($code, 'xx'))
                ->reject(fn ($code) => $code >= 200 && $code < 300)
                ->map(fn ($code) => (int) $code)
                ->values();
    }
}
