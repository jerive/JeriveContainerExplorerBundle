<?php

namespace Jerive\Bundle\ContainerExplorerBundle\DataCollector;

use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of ContainerDataCollector
 *
 * @author jerome
 */
class ContainerDataCollector extends DataCollector
{
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->data = array(
            'toto' => 'otoo',
        );
    }

    public function getContainerEntries()
    {
        
    }

    public function getName()
    {
        return 'container';
    }
}
