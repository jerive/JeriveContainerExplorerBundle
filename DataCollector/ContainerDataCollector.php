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
class ContainerDataCollector extends DataCollector implements
        CompilerPassInterface,
        ContainerAwareInterface
{
    const CACHE_FILENAME = '/container.json.dump';

    /**
     * @var \Symfony\Component\DependencyInjection\IntrospectableContainerInterface
     */
    protected $container;

    protected $cachedir;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
        $this->cachedir  = $this->container->getParameter('kernel.cache_dir');
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
        $nodes = json_decode(file_get_contents($this->getFilename()), true)['nodes'];
        $this->data['cachedir'] = $this->container->getParameter('kernel.cache_dir');

        foreach($nodes as $params) {
            if ($this->container->initialized($params['id'])) {
                $this->data['container'][] = $params['id'];
            }
        }
    }

    public function getUsedServices()
    {
        return $this->data['container'];
    }

    public function getContainerData()
    {
        return file_get_contents($this->getFilename());
    }

    public function process(ContainerBuilder $container)
    {
        $this->setContainer($container);
        $container->getParameterBag()->resolve();
        $dumper   = new JsonDumper($container);

        file_put_contents($this->getFilename(), $dumper->dump());
    }

    protected function getFilename()
    {
        if (isset($this->cachedir)) {
            return $this->cachedir . self::CACHE_FILENAME;
        } else {
            return $this->data['cachedir'] . self::CACHE_FILENAME;
        }
    }

    public function getName()
    {
        return 'container';
    }
}
