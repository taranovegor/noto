<?php

namespace App\Tests\Unit\Security;

use App\Security\HeaderOrCookieAccessTokenExtractor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class HeaderOrCookieAccessTokenExtractorTest extends TestCase
{
    private HeaderOrCookieAccessTokenExtractor $extractor;

    protected function setUp(): void
    {
        $this->extractor = new HeaderOrCookieAccessTokenExtractor('Cf-Access-Jwt-Assertion', 'CF_Authorization');
    }

    public function testExtractsTokenFromHeader(): void
    {
        $request = Request::create('/api/users/me', server: ['HTTP_CF_ACCESS_JWT_ASSERTION' => 'header-token']);

        $this->assertSame('header-token', $this->extractor->extractAccessToken($request));
    }

    public function testExtractsTokenFromCookieWhenHeaderAbsent(): void
    {
        $request = Request::create('/api/users/me');
        $request->cookies->set('CF_Authorization', 'cookie-token');

        $this->assertSame('cookie-token', $this->extractor->extractAccessToken($request));
    }

    public function testHeaderTakesPriorityOverCookie(): void
    {
        $request = Request::create('/api/users/me', server: ['HTTP_CF_ACCESS_JWT_ASSERTION' => 'header-token']);
        $request->cookies->set('CF_Authorization', 'cookie-token');

        $this->assertSame('header-token', $this->extractor->extractAccessToken($request));
    }

    public function testReturnsNullWhenNeitherHeaderNorCookiePresent(): void
    {
        $request = Request::create('/api/users/me');

        $this->assertNull($this->extractor->extractAccessToken($request));
    }
}
