<?php

namespace Jerive\Bundle\ContainerExplorerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Jerive\Bundle\ContainerExplorerBundle\DataCollector\ContainerDataCollector;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return new Response(file_get_contents(
            $this->container->getParameter('kernel.cache_dir') .
            ContainerDataCollector::CACHE_FILENAME
        ), 200, array('Content-Type' => 'application/json'));
    }
}
