<?php

namespace App\DependencyInjection;

use App\Entity\ReferenceableInterface;
use App\Service\ReferenceableRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\Finder;

final class ReferenceableEntityPass implements CompilerPassInterface
{
    private const string ENTITY_DIR = 'src/Entity';
    private const string ENTITY_NAMESPACE = 'App\\Entity';

    public function process(ContainerBuilder $container): void
    {
        $projectDir = $container->getParameter('kernel.project_dir');

        $map = [];

        foreach ((new Finder())->files()->name('*.php')->in($projectDir.'/'.self::ENTITY_DIR) as $file) {
            $className = self::ENTITY_NAMESPACE.'\\'.$file->getFilenameWithoutExtension();

            if (!class_exists($className)) {
                continue;
            }

            $reflection = $container->getReflectionClass($className);

            if ($reflection->isAbstract() || $reflection->isTrait() || $reflection->isInterface()) {
                continue;
            }

            if (!$reflection->implementsInterface(ReferenceableInterface::class)) {
                continue;
            }

            $map[$className::getRefType()->value] = $className;
        }

        $container->findDefinition(ReferenceableRegistry::class)
            ->setArgument('$map', $map);
    }
}
