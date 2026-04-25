<?php

namespace App\Component\Ai\Store\DependencyInjection;

use App\Component\Ai\Store\Attribute\Indexable;
use App\Component\Ai\Store\Config\IndexableConfig;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\Finder;

final class IndexableCompilerPass implements CompilerPassInterface
{
    private const string ENTITY_DIR = 'src/Entity';
    private const string ENTITY_NAMESPACE = 'App\\Entity';

    public function process(ContainerBuilder $container): void
    {
        $projectDir = $container->getParameter('kernel.project_dir');
        $config = $this->buildConfig($container, $projectDir.'/'.self::ENTITY_DIR);

        $container->getDefinition(IndexableConfig::class)
            ->setArgument('$config', $config);
    }

    /**
     * @return array<class-string, array{fields: list<string>, identifierField: string}>
     *
     * @throws \ReflectionException
     */
    private function buildConfig(ContainerBuilder $container, string $entityDir): array
    {
        $config = [];

        foreach (new Finder()->files()->name('*.php')->in($entityDir) as $file) {
            $className = self::ENTITY_NAMESPACE.'\\'.$file->getFilenameWithoutExtension();

            if (!class_exists($className)) {
                continue;
            }

            $reflection = $container->getReflectionClass($className);
            $attributes = $reflection->getAttributes(Indexable::class);

            if ([] === $attributes) {
                continue;
            }

            /** @var Indexable $indexable */
            $indexable = $attributes[0]->newInstance();
            $config[$className] = [
                'identifierField' => $indexable->identifierField,
                'fields' => $indexable->fields,
            ];
        }

        return $config;
    }
}
