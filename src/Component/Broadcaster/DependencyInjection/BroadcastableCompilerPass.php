<?php

namespace App\Component\Broadcaster\DependencyInjection;

use App\Component\Broadcaster\Attribute\Broadcastable;
use App\Component\Broadcaster\Config\BroadcastableConfig;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\Finder;

final class BroadcastableCompilerPass implements CompilerPassInterface
{
    private const string ENTITY_DIR = 'src/Entity';
    private const string ENTITY_NAMESPACE = 'App\\Entity';

    public function process(ContainerBuilder $container): void
    {
        $projectDir = $container->getParameter('kernel.project_dir');
        $namespaces = $this->buildNamespaces($container, $projectDir.'/'.self::ENTITY_DIR);

        $container->getDefinition(BroadcastableConfig::class)
            ->setArgument('$config', $namespaces);
    }

    /**
     * @return array<class-string, string>
     *
     * @throws \ReflectionException
     */
    private function buildNamespaces(ContainerBuilder $container, string $entityDir): array
    {
        $namespaces = [];

        foreach (new Finder()->files()->name('*.php')->in($entityDir) as $file) {
            $className = self::ENTITY_NAMESPACE.'\\'.$file->getFilenameWithoutExtension();

            if (!class_exists($className)) {
                continue;
            }

            $reflection = $container->getReflectionClass($className);
            $attributes = $reflection->getAttributes(Broadcastable::class);

            if ([] === $attributes) {
                continue;
            }

            /** @var Broadcastable $broadcastable */
            $broadcastable = $attributes[0]->newInstance();
            $namespaces[$className] = $broadcastable->namespace;
        }

        return $namespaces;
    }
}
