<?php

namespace App\Component\OpenApi\RouteDescriber;

use Nelmio\ApiDocBundle;
use OpenApi\Annotations\OpenApi;
use Symfony\Component\Routing\Route;

if (class_exists(ApiDocBundle\RouteDescriber\RouteDescriberTrait::class)) {
    trait RouteDescriberTrait
    {
        use ApiDocBundle\RouteDescriber\RouteDescriberTrait;
    }
} else {
    trait RouteDescriberTrait
    {
        private function getOperations(OpenApi $api, Route $route): array
        {
            throw new \RuntimeException(__METHOD__.' requires nelmio/api-doc-bundle');
        }

        private function normalizePath(string $path): string
        {
            throw new \RuntimeException(__METHOD__.' requires nelmio/api-doc-bundle');
        }
    }
}
