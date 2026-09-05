<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\AccessToken\AccessTokenExtractorInterface;

final class HeaderOrCookieAccessTokenExtractor implements AccessTokenExtractorInterface
{
    public function __construct(
        private readonly string $headerName,
        private readonly string $cookieName,
    ) {
    }

    public function extractAccessToken(Request $request): ?string
    {
        if ($request->headers->has($this->headerName)) {
            return $request->headers->get($this->headerName);
        }

        return $request->cookies->get($this->cookieName);
    }
}
