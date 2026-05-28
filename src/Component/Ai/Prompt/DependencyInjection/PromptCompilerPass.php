<?php

namespace App\Component\Ai\Prompt\DependencyInjection;

use App\Component\Ai\Prompt\PromptProvider;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

final class PromptCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $dir = $container->getParameter('kernel.project_dir').'/config/prompts';

        if (!is_dir($dir)) {
            $container->getDefinition(PromptProvider::class)->setArgument('$prompts', []);

            return;
        }

        $prompts = [];

        foreach (new Finder()->files()->in($dir)->name('*.yaml') as $file) {
            $type = $file->getBasename('.yaml');
            $data = Yaml::parseFile($file->getRealPath());

            if (!\is_array($data) || !isset($data['system'], $data['user'])) {
                throw new \RuntimeException(\sprintf('Invalid prompt file "%s": expected keys "system" and "user", got %s.', $file->getRealPath(), \is_array($data) ? implode(', ', array_keys($data)) : \gettype($data)));
            }

            if (!\is_string($data['system']) || !\is_string($data['user'])) {
                throw new \RuntimeException(\sprintf('Invalid prompt file "%s": "system" and "user" must be strings.', $file->getRealPath()));
            }

            $prompts[$type] = ['system' => $data['system'], 'user' => $data['user']];

            $container->addResource(new FileResource($file->getRealPath()));
        }

        $container->addResource(new DirectoryResource($dir, '/\.yaml$/'));

        $container
            ->getDefinition(PromptProvider::class)
            ->setArgument('$prompts', $prompts);
    }
}
