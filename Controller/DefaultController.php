<?php

namespace Jerive\Bundle\ContainerExplorerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;

use Jerive\Bundle\ContainerExplorerBundle\DependencyInjection\JsonDumper;

class DefaultController extends Controller
{
    public function indexAction()
    {
        $dumper   = new JsonDumper($this->getContainerBuilder());
        $response = new Response($dumper->dump());
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    protected function getContainerBuilder()
    {
        if (!is_file($cachedFile = $this->container->getParameter('debug.container.dump'))) {
            throw new \LogicException(sprintf('Debug information about the container could not be found. Please clear the cache and try again.'));
        }

        $container = new ContainerBuilder();

        $loader = new XmlFileLoader($container, new FileLocator());
        $loader->load($cachedFile);

        return $container;
    }
}
