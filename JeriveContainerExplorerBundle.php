<?php

namespace Jerive\Bundle\ContainerExplorerBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Jerive\Bundle\ContainerExplorerBundle\DataCollector\ContainerDataCollector;

class JeriveContainerExplorerBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ContainerDataCollector());
    }
}
