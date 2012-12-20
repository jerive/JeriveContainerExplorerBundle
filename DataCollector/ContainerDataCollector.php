<?php

namespace Jerive\Bundle\ContainerExplorerBundle\DataCollector;

use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Jerive\Bundle\ContainerExplorerBundle\DependencyInjection\Dumper\JsonDumper;

/**
 * Description of ContainerDataCollector
 *
 * @author jerome
 */
class ContainerDataCollector extends DataCollector implements CompilerPassInterface
{
    const CACHE_FILENAME = '/container.json.dump';

    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->data = array(
            'toto' => 'otoo',
        );
    }

    public function getName()
    {
        return 'container';
    }

    public function process(ContainerBuilder $container)
    {
        $container->getParameterBag()->resolve();
        $dumper   = new JsonDumper($container);
        $filename = $container->getParameter('kernel.cache_dir') . self::CACHE_FILENAME;

        file_put_contents($filename, $dumper->dump());
    }
}
