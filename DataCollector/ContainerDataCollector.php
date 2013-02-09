<?php

namespace Jerive\Bundle\ContainerExplorerBundle\DataCollector;

use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Jerive\Bundle\ContainerExplorerBundle\DependencyInjection\Dumper\JsonDumper;

/**
 * Description of ContainerDataCollector
 *
 * @author jerome
 */
class ContainerDataCollector extends DataCollector implements CompilerPassInterface, ContainerAwareInterface
{
    const CACHE_FILENAME = '/container.json.dump';

    /**
     * @var \Symfony\Component\DependencyInjection\IntrospectableContainerInterface
     */
    protected $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Symfony\Component\HttpFoundation\Response $response
     * @param \Exception $exception
     * @return array
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $filename = $this->container->getParameter('kernel.cache_dir') . self::CACHE_FILENAME;
        $nodes = json_decode(file_get_contents($filename), true)['nodes'];

        foreach($nodes as $key => $params) {
            if ($this->container->initialized($params['id'])) {
                $this->data[] = $params['id'];
            }
        }
    }

    public function getName()
    {
        return 'container';
    }

    public function getUsedServices()
    {
        return $this->data;
    }

    public function process(ContainerBuilder $container)
    {
        $container->getParameterBag()->resolve();
        $dumper   = new JsonDumper($container);
        $filename = $container->getParameter('kernel.cache_dir') . self::CACHE_FILENAME;

        file_put_contents($filename, $dumper->dump());
    }
}
