<?php

namespace App\DependencyInjection;

use App\Event\ReferenceableEventInterface;
use App\Service\ReferenceableEventRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\Finder;

final class ReferenceableEventPass implements CompilerPassInterface
{
    private const string EVENT_DIR = 'src/Event';
    private const string EVENT_NAMESPACE = 'App\\Event';

    public function process(ContainerBuilder $container): void
    {
        $projectDir = $container->getParameter('kernel.project_dir');

        $map = [];

        foreach ((new Finder())->files()->name('*.php')->in($projectDir.'/'.self::EVENT_DIR) as $file) {
            $relativePath = $file->getRelativePath();
            $namespace = self::EVENT_NAMESPACE;
            if ($relativePath) {
                $namespace .= '\\'.str_replace('/', '\\', $relativePath);
            }
            $className = $namespace.'\\'.$file->getFilenameWithoutExtension();

            if (!class_exists($className)) {
                continue;
            }

            $reflection = $container->getReflectionClass($className);

            if ($reflection->isAbstract() || $reflection->isTrait() || $reflection->isInterface()) {
                continue;
            }

            if (!$reflection->implementsInterface(ReferenceableEventInterface::class)) {
                continue;
            }

            $map[$className::getRefType()->value] = $className;
        }

        $container->findDefinition(ReferenceableEventRegistry::class)
            ->setArgument('$map', $map);
    }
}
