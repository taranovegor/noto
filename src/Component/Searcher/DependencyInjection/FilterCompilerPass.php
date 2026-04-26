<?php

declare(strict_types=1);

namespace App\Component\Searcher\DependencyInjection;

use App\Component\Searcher\Definition\FilterHandlerInterface;
use App\Component\Searcher\Definition\FilterInputTransformerInterface;
use App\Component\Searcher\DoctrineSearcher;
use App\Component\Searcher\Resolver\AbstractSearchDtoResolver;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class FilterCompilerPass implements CompilerPassInterface
{
    private const string INPUT_TRANSFORMER_TAG = 'searcher.input_transformer';
    private const string FILTER_HANDLER_TAG = 'searcher.filter_handler';

    public function process(ContainerBuilder $container): void
    {
        $this->tagAutoconfiguredServices($container, FilterInputTransformerInterface::class, self::INPUT_TRANSFORMER_TAG);
        $this->tagAutoconfiguredServices($container, FilterHandlerInterface::class, self::FILTER_HANDLER_TAG);

        $this->injectContainerIntoServices($container);
    }

    private function tagAutoconfiguredServices(ContainerBuilder $container, string $interface, string $tag): void
    {
        foreach ($container->getDefinitions() as $definition) {
            if (!$definition->isAutoconfigured()) {
                continue;
            }

            $class = $definition->getClass();

            if (!$class) {
                continue;
            }

            try {
                $interfaces = class_implements($class);

                if ($interfaces === false) {
                    continue;
                }

                if (in_array($interface, $interfaces, true)) {
                    $definition->addTag($tag);
                    $definition->setPublic(true);
                }
            } catch (\Throwable) {
                continue;
            }
        }
    }

    private function injectContainerIntoServices(ContainerBuilder $container): void
    {
        if ($container->has(DoctrineSearcher::class)) {
            $definition = $container->getDefinition(DoctrineSearcher::class);
            $definition->setArgument('$container', new Reference('service_container'));
        }

        foreach ($container->getDefinitions() as $definition) {
            if (!$definition->isAutoconfigured()) {
                continue;
            }

            $class = $definition->getClass();

            if (!$class) {
                continue;
            }

            try {
                $parents = class_parents($class);

                if ($parents === false) {
                    continue;
                }

                if (in_array(AbstractSearchDtoResolver::class, $parents, true)) {
                    $definition->setArgument('$container', new Reference('service_container'));
                }
            } catch (\Throwable) {
                continue;
            }
        }
    }
}
