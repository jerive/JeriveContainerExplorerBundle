<?php

namespace Jerive\Bundle\ContainerExplorerBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Dumper\GraphvizDumper;

class JsonDumper extends GraphvizDumper
{
    public function dump(array $options = array())
    {
        $this->nodes = $this->findNodes();

        $this->edges = array();
        foreach ($this->container->getDefinitions() as $id => $definition) {
            $this->edges[$id] = array_merge(
                $this->findEdges($id, $definition->getArguments(), true, ''),
                $this->findEdges($id, $definition->getProperties(), false, '')
            );

            foreach ($definition->getMethodCalls() as $call) {
                $this->edges[$id] = array_merge(
                    $this->edges[$id],
                    $this->findEdges($id, $call[1], false, $call[0].'()')
                );
            }
        }

        return json_encode($this->edges);
    }
}