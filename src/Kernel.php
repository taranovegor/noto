<?php

namespace App;

use App\Component\Ai\Store\DependencyInjection as AiStore;
use App\Component\Broadcaster\DependencyInjection as Broadcaster;
use App\Component\Searcher\DependencyInjection as Searcher;
use App\DependencyInjection as App;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    protected function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new AiStore\IndexableCompilerPass());
        $container->addCompilerPass(new Broadcaster\BroadcastableCompilerPass());
        $container->addCompilerPass(new Searcher\NelmioApiDocCompilerPass());
        $container->addCompilerPass(new Searcher\FilterCompilerPass());
        $container->addCompilerPass(new App\ReferenceableEntityPass());
        $container->addCompilerPass(new App\ReferenceableEventPass());
    }
}
