<?php

declare(strict_types=1);

namespace App\Component\Searcher\DependencyInjection;

use App\Component\Searcher\OpenApi\SearchCriteriaDescriber;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class NelmioApiDocCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $bundles = $container->getParameter('kernel.bundles');

        if (!isset($bundles['NelmioApiDocBundle'])) {
            return;
        }

        $container
            ->register('searcher.route_describers.search_criteria', SearchCriteriaDescriber::class)
            ->setAutoconfigured(true)
            ->addTag('nelmio_api_doc.route_describer');
    }
}
