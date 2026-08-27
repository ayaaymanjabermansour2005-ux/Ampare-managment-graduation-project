<?php

namespace Tests\Unit\Support;

use App\Support\PerPageResolver;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class PerPageResolverTest extends TestCase
{
    public function test_uses_default_when_per_page_not_provided(): void
    {
        $request = Request::create('/x', 'GET');

        $this->assertSame(15, PerPageResolver::resolve($request));
        $this->assertSame(25, PerPageResolver::resolve($request, default: 25));
    }

    public function test_uses_client_supplied_value_within_bounds(): void
    {
        $request = Request::create('/x', 'GET', ['per_page' => 40]);

        $this->assertSame(40, PerPageResolver::resolve($request));
    }

    public function test_caps_value_above_max_at_max(): void
    {
        $request = Request::create('/x', 'GET', ['per_page' => 999999]);

        $this->assertSame(100, PerPageResolver::resolve($request));
    }

    public function test_respects_custom_max(): void
    {
        $request = Request::create('/x', 'GET', ['per_page' => 500]);

        $this->assertSame(200, PerPageResolver::resolve($request, max: 200));
    }

    public function test_floors_value_below_one_at_one(): void
    {
        $request = Request::create('/x', 'GET', ['per_page' => 0]);
        $this->assertSame(1, PerPageResolver::resolve($request));

        $request = Request::create('/x', 'GET', ['per_page' => -50]);
        $this->assertSame(1, PerPageResolver::resolve($request));
    }
}
